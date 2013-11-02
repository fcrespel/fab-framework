<?php

class Fab_Soap_Client_Wrapped extends Zend_Soap_Client
{
    /**
     * Constructor.
     * @param string $wsdl
     * @param array $options
     */
    public function __construct($wsdl = null, $options = null)
    {
        // Use SOAP 1.1 as default
        $this->setSoapVersion(SOAP_1_1);
        parent::__construct($wsdl, $options);
    }

    /**
     * Check whether arguments conform to wrapped style.
     * @param array $arguments
     * @throws Zend_Soap_Client_Exception
     */
    protected function _preProcessArguments($arguments)
    {
        if (count($arguments) > 1 || (count($arguments) == 1 && !is_array(reset($arguments)))) {
            throw new Zend_Soap_Client_Exception('Wrapped webservice arguments have to be grouped into array: array(\'a\' => $a, \'b\' => $b, ...).');
        }
        return $arguments;
    }

    /**
     * Extract result object from response.
     * @param array $result
     */
    protected function _preProcessResult($result)
    {
        $resultProperty = $this->getLastMethod() . 'Result';
        if (isset($result->$resultProperty)) {
            return $result->$resultProperty;
        } else {
            return $result;
        }
    }
}
