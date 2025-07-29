<?php

class Fab_Soap_Server extends Zend_Soap_Server
{
    /** @var Fab_Soap_AutoDiscover */
    protected $_autoDiscover = null;

    /** @var Fab_Soap_Server_Proxy */
    protected $_serviceProxy = null;

    /**
     * Return an array of operations handled by this server as ReflectionFunction objects.
     * @return ReflectionFunction[]
     */
    public function getOperations()
    {
        $operations = array();
        
        // Reflect functions
        foreach ($this->_functions as $function) {
            $operations[] = new ReflectionFunction($function);
        }

        // Reflect class methods
        if (!empty($this->_class)) {
            $class = new ReflectionClass($this->_class);
            $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (substr($method->getName(), 0, 1) != '_') {
                    $operations[] = $method;
                }
            }
        }

        return $operations;
    }

    /**
     * Check if this server handles a specific operation.
     * @param string $operation operation name
     */
    public function hasOperation($name)
    {
        $operations = $this->getOperations();
        foreach ($operations as $operation) {
            if ($operation->getName() === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the WSDL AutoDiscover instance.
     * @return Fab_Soap_AutoDiscover|null
     */
    public function getAutoDiscover()
    {
        return $this->_autoDiscover;
    }

    /**
     * Set the WSDL AutoDiscover instance.
     * @param Fab_Soap_AutoDiscover|null $autoDiscover
     */
    public function setAutoDiscover($autoDiscover)
    {
        $this->_autoDiscover = $autoDiscover;
    }

    /**
     * Get the service proxy instance.
     * The proxy will delegate calls to the service class, while letting
     * handlers tamper with the method arguments and return value.
     * @return Fab_Soap_Server_Proxy
     */
    protected function _getServiceProxy()
    {
        if ($this->_serviceProxy === null) {
            $this->_serviceProxy = new Fab_Soap_Server_Proxy($this);
        }
        return $this->_serviceProxy;
    }

    /**
     * Add a handler to the end of the chain.
     * @param Fab_Soap_Server_Handler $handler handler to add
     */
    public function appendHandler(Fab_Soap_Server_Handler $handler)
    {
        $this->_getServiceProxy()->appendHandler($handler);
    }

    /**
     * Add a handler to the beginning of the chain.
     * @param Fab_Soap_Server_Handler $handler handler to add
     */
    public function prependHandler(Fab_Soap_Server_Handler $handler)
    {
        $this->_getServiceProxy()->prependHandler($handler);
    }

    /**
     * Remove a handler from the chain.
     * @param Fab_Soap_Server_Handler $handler
     */
    public function removeHandler(Fab_Soap_Server_Handler $handler)
    {
        $this->_getServiceProxy()->removeHandler($handler);
    }

    /**
     * Get SoapServer object
     *
     * Uses {@link $_wsdl} and return value of {@link getOptions()} to instantiate
     * SoapServer object, and then registers any functions or class with it, as
     * well as peristence.
     *
     * @return SoapServer
     */
    protected function _getSoap()
    {
        $options = $this->getOptions();
        $server  = new SoapServer($this->_wsdl, $options);

        if (!empty($this->_functions)) {
            $server->addFunction($this->_functions);
        }

        if ($this->_autoDiscover !== null) {
            // When using the 'document' style, add the wrapped handler automatically
            $bindingStyle = $this->_autoDiscover->getBindingStyle();
            if (isset($bindingStyle['style']) && $bindingStyle['style'] == 'document') {
                $this->_getServiceProxy()->prependHandler(new Fab_Soap_Server_Handler_Wrapped());
            }
        }

        if (is_object($this->_serviceProxy)) {
            // The service proxy was initialized, use it to redirect calls
            if (!empty($this->_object)) {
                $this->_serviceProxy->setObject($this->_object);
            } else if (!empty($this->_class)) {
                $this->_serviceProxy->setClass($this->_class, $this->_classArgs);
            }
            $server->setObject($this->_serviceProxy);

        } else {
            // The service proxy wasn't initialized, use direct calls
            if (!empty($this->_class)) {
                $args = $this->_classArgs;
                array_unshift($args, $this->_class);
                call_user_func_array(array($server, 'setClass'), $args);
            }

            if (!empty($this->_object)) {
                $server->setObject($this->_object);
            }
        }

        if (null !== $this->_persistence) {
            $server->setPersistence($this->_persistence);
        }

        return $server;
    }

    /**
     * Handle a request
     *
     * Instantiates SoapServer object with options set in object, and
     * dispatches its handle() method.
     *
     * $request may be any of:
     * - DOMDocument; if so, then cast to XML
     * - DOMNode; if so, then grab owner document and cast to XML
     * - SimpleXMLElement; if so, then cast to XML
     * - stdClass; if so, calls __toString() and verifies XML
     * - string; if so, verifies XML
     *
     * If no request is passed, pulls request using php:://input (for
     * cross-platform compatability purposes).
     *
     * @param DOMDocument|DOMNode|SimpleXMLElement|stdClass|string $request Optional request
     * @return void|string
     */
    public function handle($request = null)
    {
        if (null === $request) {
            $request = file_get_contents('php://input');
        }

        // Set Zend_Soap_Server error handler
        $displayErrorsOriginalState = $this->_initializeSoapErrorContext();

        $setRequestException = null;
        /**
         * @see Zend_Soap_Server_Exception
         */
        try {
            $this->_setRequest($request);
        } catch (Zend_Soap_Server_Exception $e) {
            $setRequestException = $e;
        }
        
        $soap = $this->_getSoap();

        $fault = false;
        ob_start();
        if ($setRequestException instanceof Exception) {
            // Create SOAP fault message if we've caught a request exception
            $fault = $this->fault($setRequestException->getMessage(), 'Sender');
        } else {
            try {
                $soap->handle($this->_request);
            } catch (Exception $e) {
                $fault = $this->fault($e);
            }
        }

        // Send a fault, if we have one (fix for ZF-12393)
        if ($fault) {
            $soap->fault($fault->faultcode, $fault->faultstring);
        }

        $this->_response = ob_get_clean();

        // Restore original error handler
        restore_error_handler();
        ini_set('display_errors', $displayErrorsOriginalState);

        if (!$this->_returnResponse) {
            echo $this->_response;
            return;
        }

        return $this->_response;
    }

}
