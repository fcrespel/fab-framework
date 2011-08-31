<?php

class Fab_Soap_AutoDiscover extends Zend_Soap_AutoDiscover
{
    /** @var string Web service name */
    protected $_serviceName;

    /**
     * Constructor.
     *
     * @param boolean|string|Zend_Soap_Wsdl_Strategy_Interface $strategy
     * @param string|Zend_Uri $uri
     * @param string $wsdlClass
     */
    public function __construct($strategy = true, $uri = null, $wsdlClass = null)
    {
        parent::__construct($strategy === null || $strategy === true ? 'Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence' : $strategy, $uri, $wsdlClass);
        $this->setBindingStyle(array('style' => 'document'));
        $this->setOperationBodyStyle(array('use' => 'literal'));
    }

    /**
     * Get options for all the binding operations soap:body elements.
     *
     * @return array
     */
    public function getOperationBodyStyle()
    {
        return $this->_operationBodyStyle;
    }

    /**
     * Get Binding soap:binding style.
     *
     * @return array
     */
    public function getBindingStyle()
    {
        return $this->_bindingStyle;
    }

    /**
     * Get the service name to use when generating the WSDL.
     * @return string
     */
    public function getServiceName()
    {
        return $this->_serviceName;
    }

    /**
     * Set the service name to use when generating the WSDL.
     * This must be called before the setClass() or addFunction() methods.
     * @param string $serviceName
     * @return self
     */
    public function setServiceName($serviceName)
    {
        $this->_serviceName = $serviceName;
        return $this;
    }

    /**
     * Set the Class the SOAP server will use
     *
     * @param string $class Class Name
     * @param string $namespace Class Namespace - Not Used
     * @param array $argv Arguments to instantiate the class - Not Used
     * @return self
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        $uri = $this->getUri();
        $name = $this->getServiceName();
        if ($name === null) {
            $name = $class;
            $this->setServiceName($class);
        }

        $wsdl = new $this->_wsdlClass($name, $uri, $this->_strategy);

        // The wsdl:types element must precede all other elements (WS-I Basic Profile 1.1 R2023)
        $wsdl->addSchemaTypeSection();

        $port = $wsdl->addPortType($name . 'Port');
        $binding = $wsdl->addBinding($name . 'Binding', 'tns:' .$name. 'Port');

        $wsdl->addSoapBinding($binding, $this->_bindingStyle['style'], $this->_bindingStyle['transport']);
        $wsdl->addService($name . 'Service', $name . 'Port', 'tns:' . $name . 'Binding', $uri);
        foreach ($this->_reflection->reflectClass($class)->getMethods() as $method) {
            $this->_addFunctionToWsdl($method, $wsdl, $port, $binding);
        }
        $this->_wsdl = $wsdl;

        return $this;
    }

    /**
     * Add a Single or Multiple Functions to the WSDL
     *
     * @param string $function Function Name
     * @param string $namespace Function namespace - Not Used
     * @return self
     */
    public function addFunction($function, $namespace = '')
    {
        static $port;
        static $operation;
        static $binding;

        if (!is_array($function)) {
            $function = (array) $function;
        }

        $uri = $this->getUri();

        if (!($this->_wsdl instanceof Zend_Soap_Wsdl)) {
            $name = $this->getServiceName();
            if ($name === null) {
                $parts = explode('.', basename($_SERVER['SCRIPT_NAME']));
                $name = $parts[0];
                $this->setServiceName($name);
            }
            $wsdl = new $this->_wsdlClass($name, $uri, $this->_strategy);

            // The wsdl:types element must precede all other elements (WS-I Basic Profile 1.1 R2023)
            $wsdl->addSchemaTypeSection();

            $port = $wsdl->addPortType($name . 'Port');
            $binding = $wsdl->addBinding($name . 'Binding', 'tns:' .$name. 'Port');

            $wsdl->addSoapBinding($binding, $this->_bindingStyle['style'], $this->_bindingStyle['transport']);
            $wsdl->addService($name . 'Service', $name . 'Port', 'tns:' . $name . 'Binding', $uri);
        } else {
            $wsdl = $this->_wsdl;
        }

        foreach ($function as $func) {
            $method = $this->_reflection->reflectFunction($func);
            $this->_addFunctionToWsdl($method, $wsdl, $port, $binding);
        }
        $this->_wsdl = $wsdl;

        return $this;
    }

}
