<?php

class Fab_Soap_Wsdl_Strategy_ArrayOfTypeSequence extends Fab_Soap_Wsdl_Strategy_Decorator
{
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
     * Add an unbounded ArrayOfType based on the xsd:sequence syntax if type[] is detected in return value doc comment.
     *
     * @param  string $type
     * @return string tns:xsd-type
     */
    public function addComplexType($type)
    {
        if (substr_count($type, "[]") == 0 && !in_array($type, $this->getContext()->getTypes())) {
            // New singular complex type
            $strategy = $this->getDefaultComplexTypeStrategy();
        } else {
            // Existing complex type or array type
            $strategy = new Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence();
        }
        
        $strategy->setContext($this->getContext());
        return $strategy->addComplexType($type);
    }
}
