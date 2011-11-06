<?php

class Fab_Soap_Wsdl_Strategy_ArrayOfType extends Fab_Soap_Wsdl_Strategy_Decorator
{
    /** @var Zend_Soap_Wsdl_Strategy_Interface */
    protected $_arrayStrategy;
    
    /**
     * Construct a new strategy decorator with an optional default strategy to
     * fallback to if the type to add is not an array.
     * @param string|Zend_Soap_Wsdl_Strategy_Interface|null $defaultStrategy 
     */
    public function __construct($defaultStrategy = null)
    {
        parent::__construct($defaultStrategy);
    }
    
    /**
     * Get the array strategy.
     * @return Zend_Soap_Wsdl_Strategy_Interface
     */
    public function getArrayStrategy()
    {
        if ($this->_arrayStrategy === null) {
            $this->_arrayStrategy = new Fab_Soap_Wsdl_Strategy_ArrayOfTypeSequence();
        }
        return $this->_arrayStrategy;
    }
    
    /**
     * Set the array strategy.
     * @param Zend_Soap_Wsdl_Strategy_Interface $arrayStrategy 
     */
    public function setArrayStrategy($arrayStrategy)
    {
        $this->_arrayStrategy = $arrayStrategy;
    }
    
    /**
     * Add an unbounded ArrayOfType based on the xsd:sequence syntax if type[] is detected in return value doc comment.
     *
     * @param  string $type
     * @return string tns:xsd-type
     */
    public function addComplexType($type)
    {
        $singularType = str_replace('[]', '', $type);
        $mappedType = str_replace($singularType, $this->getContext()->getMappedType($singularType), $type);
        
        if (substr_count($type, "[]") == 0 && !in_array($mappedType, $this->getContext()->getTypes())) {
            // New singular complex type
            $strategy = $this->getDefaultComplexTypeStrategy();
        } else {
            // Existing complex type or array type
            $strategy = $this->getArrayStrategy();
        }
        
        $strategy->setContext($this->getContext());
        return $strategy->addComplexType($type);
    }
}
