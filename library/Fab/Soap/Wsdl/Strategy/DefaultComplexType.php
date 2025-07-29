<?php

class Fab_Soap_Wsdl_Strategy_DefaultComplexType extends Zend_Soap_Wsdl_Strategy_Abstract
{
    /**
     * Add a complex type by recursivly using all the class properties fetched via Reflection.
     *
     * @param  string $type Name of the class to be specified
     * @return string XSD Type for the given PHP type
     */
    public function addComplexType($type)
    {
        $mappedType = $this->getContext()->getMappedType($type);
        
        if(!class_exists($type)) {
            throw new Zend_Soap_Wsdl_Exception(sprintf(
                "Cannot add a complex type %s that is not an object or where ".
                "class could not be found in 'DefaultComplexType' strategy.", $type
            ));
        }
        
        $class = new ReflectionClass($type);
        $defaultProperties = $class->getDefaultProperties();
        $properties = array();
        
        // Enumerate @property annotations on this class (not parent classes)
        if (preg_match_all('/@property\s+(\S+)\s+\$(\S+)/m', $class->getDocComment(), $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $propertyName = $match[2];
                $propertyTypes = explode('|', $match[1]);
                $properties[$propertyName] = array('name' => $propertyName);
                foreach ($propertyTypes as $propertyType) {
                    if ($propertyType == 'null') {
                        $properties[$propertyName]['minOccurs'] = '0';
                    } else if (!isset($properties[$propertyName]['type'])) {
                        $properties[$propertyName]['type'] = $this->getContext()->getType($propertyType);
                    }
                }
            }
        }
        
        // Enumerate class properties (including inherited ones)
        foreach ($class->getProperties() as $property) {
            if ($property->isPublic() && preg_match_all('/@var\s+([^\s]+)/m', $property->getDocComment(), $matches)) {
                // Check if a property with the same name has already been added
                $propertyName = $property->getName();
                if (isset($properties[$propertyName]))
                    continue;
                
                // Add this property to the list
                $properties[$propertyName] = array(
                    'name' => $property->getName(),
                    'type' => $this->getContext()->getType(trim($matches[1][0])),
                );

                // If the default value is null, then this property is nillable.
                if ($defaultProperties[$propertyName] === null) {
                    $properties[$propertyName]['nillable'] = 'true';
                }
            }
        }

        $dom = $this->getContext()->toDomDocument();
        
        // Create a complexType
        $complexType = $dom->createElement('xsd:complexType');
        $complexType->setAttribute('name', $mappedType);

        // Add all properties as elements of an <all> structure
        $all = $dom->createElement('xsd:all');
        foreach ($properties as $property) {
            $element = $dom->createElement('xsd:element');
            foreach ($property as $attrName => $attrValue) {
                $element->setAttribute($attrName, $attrValue);
            }
            $all->appendChild($element);
        }

        // Finalize the complexType and add it to the XML Schema
        $complexType->appendChild($all);
        $this->getContext()->getSchema()->appendChild($complexType);
        $this->getContext()->addType($mappedType);

        return "tns:$mappedType";
    }
}
