<?php

class Fab_Soap_Wsdl_Strategy_DoctrineRecord extends Fab_Soap_Wsdl_Strategy_Decorator
{
    protected $_inProcess = array();
    
    /**
     * Construct a new strategy decorator with an optional default strategy to
     * fallback to if the type to add is not a valid Doctrine_Record.
     * @param string|Zend_Soap_Wsdl_Strategy_Interface|null $defaultStrategy 
     */
    public function __construct($defaultStrategy = null)
    {
        parent::__construct($defaultStrategy);
    }
    
    /**
     * Add a complex type by using all columns for the Doctrine table.
     *
     * @param  string $type Doctrine record class name
     * @return string tns:xsd-type
     */
    public function addComplexType($type)
    {
        $mappedType = $this->getContext()->getMappedType($type);
        
        if (in_array($type, $this->_inProcess)) {
            return "tns:" . $mappedType;
        }
        $this->_inProcess[$type] = $type;
        
        if (!class_exists($type)) {
            throw new Fab_Soap_Wsdl_Exception(sprintf(
                "Cannot add a complex type %s that is not an object or where ".
                "class could not be found in 'DoctrineRecord' strategy.", $type
            ));
        }
        
        if (!is_subclass_of($type, 'Doctrine_Record')) {
            $defaultStrategy = $this->getDefaultComplexTypeStrategy();
            $defaultStrategy->setContext($this->getContext());
            return $defaultStrategy->addComplexType($type);
        }
        
        $table = Doctrine::getTable($type);

        $dom = $this->getContext()->toDomDocument();
        
        // Create a complexType
        $complexType = $dom->createElement('xsd:complexType');
        $complexType->setAttribute('name', $mappedType);

        $all = $dom->createElement('xsd:all');

        // Add all table columns
        foreach ($table->getColumns() as $name => $def) {
            $columnName = $table->getColumnName($name);
            $fieldName = $table->getFieldName($columnName);
            switch ($def['type']) {
                case 'enum':
                case 'text':
                    $columnType = 'xsd:string';
                    break;
                case 'decimal':
                    $columnType = 'xsd:decimal';
                    break;
                case 'timestamp':
                    // Incompatible with xsd:dateTime (format is not IS08601)
                    $columnType = 'xsd:string';
                    break;
                case 'time':
                    $columnType = 'xsd:time';
                    break;
                case 'date':
                    $columnType = 'xsd:date';
                    break;
                default:
                    $columnType = $this->getContext()->getType($def['type']);
                    break;
            }
            
            $element = $dom->createElement('xsd:element');
            $element->setAttribute('name', $fieldName);
            $element->setAttribute('type', $columnType);
            if (!isset($def['notnull']) || $def['notnull'] !== true) {
                $element->setAttribute('nillable', 'true');
            }
            $all->appendChild($element);
        }
        
        // Add relations
        foreach ($table->getRelations() as $relation) {
            if ($relation->isRefClass())
                continue;
            
            $relationType = $relation->getClass();
            if (!$relation->isOneToOne())
                $relationType .= '[]';
            
            $element = $dom->createElement('xsd:element');
            $element->setAttribute('name', $relation->getAlias());
            $element->setAttribute('type', $this->getContext()->getType($relationType));
            $element->setAttribute('nillable', 'true');
            $all->appendChild($element);
        }
        
        // Finalize the complexType and add it to the XML Schema
        $complexType->appendChild($all);
        $this->getContext()->getSchema()->appendChild($complexType);
        $this->getContext()->addType($type);

        unset($this->_inProcess[$type]);
        return "tns:$mappedType";
    }
}
