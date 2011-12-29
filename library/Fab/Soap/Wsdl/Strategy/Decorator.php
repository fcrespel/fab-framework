<?php

abstract class Fab_Soap_Wsdl_Strategy_Decorator extends Zend_Soap_Wsdl_Strategy_Abstract
{
    /** @var Zend_Soap_Wsdl_Strategy_Interface */
    protected $_defaultStrategy;
    
    /**
     * Construct a new strategy decorator with an optional default strategy to
     * decorate (or fallback to).
     * @param string|Zend_Soap_Wsdl_Strategy_Interface|null $defaultStrategy 
     */
    public function __construct($defaultStrategy = null)
    {
        $this->setDefaultComplexTypeStrategy($defaultStrategy);
    }
    
    /**
     * Get the default strategy for complex type detection and handling.
     * 
     * @return Zend_Soap_Wsdl_Strategy_Interface
     */
    public function getDefaultComplexTypeStrategy()
    {
        return $this->_defaultStrategy;
    }

    /**
     * Set a default strategy for complex type detection and handling.
     * 
     * @param boolean|string|Zend_Soap_Wsdl_Strategy_Interface|null $strategy
     * @return self
     */
    public function setDefaultComplexTypeStrategy($strategy = true)
    {
        if ($strategy === true || $strategy === null) {
            $strategy = new Fab_Soap_Wsdl_Strategy_DefaultComplexType();
        } else if ($strategy === false) {
            $strategy = new Zend_Soap_Wsdl_Strategy_AnyType();
        } else if (is_string($strategy)) {
            if (class_exists($strategy)) {
                $strategy = new $strategy();
            } else {
                throw new Fab_Soap_Wsdl_Exception(
                    sprintf("Strategy with name '%s does not exist.", $strategy
                ));
            }
        }

        if (!($strategy instanceof Zend_Soap_Wsdl_Strategy_Interface)) {
            throw new Fab_Soap_Wsdl_Exception("Set a strategy that is not of type 'Zend_Soap_Wsdl_Strategy_Interface'");
        }
        $this->_defaultStrategy = $strategy;
        return $this;
    }
}
