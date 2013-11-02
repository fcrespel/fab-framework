<?php

class Fab_Soap_Wsdl extends Zend_Soap_Wsdl
{
    /** @var array */
    protected $_classmap = array();

    /**
     * Get the current classmap used to associate XSD complexType names to PHP class names.
     * @return array
     */
    public function getClassmap()
    {
        return $this->_classmap;
    }

    /**
     * Set the classmap to use to associate XSD complexType names to PHP class names.
     * @param array $classmap 
     */
    public function setClassmap($classmap)
    {
        $this->_classmap = $classmap;
    }

    /**
     * Get the mapped type (XSD complexType name) for a given PHP class name.
     * @param string $type PHP class name
     * @return string mapped type (if any - otherwise the original type is returned unchanged)
     */
    public function getMappedType($type)
    {
        if (($mappedType = array_search($type, $this->_classmap)) !== false)
            return $mappedType;
        else
            return $type;
    }
    
    /**
     * Get an XSD type for the given PHP type.
     * @param string $type PHP type to get the XSD type for
     * @return string
     */
    public function getType($type) {
        switch (strtolower($type)) {
            case 'zend_date':
                return 'xsd:dateTime';
            default:
                return parent::getType($type);
        }
    }

    /**
     * Add a {@link http://www.w3.org/TR/wsdl#_types types} data type definition.
     * @param string $type Name of the class to be specified
     * @return string XSD Type for the given PHP type
     */
    public function addComplexType($type)
    {
        $mappedType = $this->getMappedType($type);
        if (in_array($mappedType, $this->getTypes())) {
            return "tns:$mappedType";
        }
        $this->addSchemaTypeSection();

        $strategy = $this->getComplexTypeStrategy();
        $strategy->setContext($this);
        // delegates the detection of a complex type to the current strategy
        return $strategy->addComplexType($type);
    }
}
