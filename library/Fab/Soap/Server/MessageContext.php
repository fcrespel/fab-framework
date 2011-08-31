<?php

class Fab_Soap_Server_MessageContext
{
    
    /** @var Fab_Soap_Server SOAP server */
    protected $_server;

    /** @var mixed service object */
    protected $_serviceObject;

    /** @var string method name */
    protected $_methodName;

    /** @var array method arguments */
    protected $_methodArgs;

    /** @var mixed method return value */
    protected $_methodReturn;

    /** @var array SOAP headers */
    protected $_headers = array();

    public function __construct($server, $serviceObject)
    {
        $this->_server = $server;
        $this->_serviceObject = $serviceObject;
    }

    /**
     * Get the SOAP server.
     * @return Fab_Soap_Server
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * Get service object.
     * @return object
     */
    public function getServiceObject()
    {
        return $this->_serviceObject;
    }

    /**
     * Get method name.
     * @return string
     */
    public function getMethodName()
    {
        return $this->_methodName;
    }

    /**
     * Set method name.
     * @param string $methodName method name
     * @return Fab_Soap_Server_MessageContext
     */
    public function setMethodName($methodName)
    {
        $this->_methodName = $methodName;
        return $this;
    }

    /**
     * Get method arguments.
     * @return array
     */
    public function getMethodArgs()
    {
        return $this->_methodArgs;
    }

    /**
     * Set method arguments.
     * @param array $methodArgs method arguments
     * @return Fab_Soap_Server_MessageContext
     */
    public function setMethodArgs(array $methodArgs)
    {
        $this->_methodArgs = $methodArgs;
        return $this;
    }

    /**
     * Get method return value.
     * @return mixed
     */
    public function getMethodReturn()
    {
        return $this->_methodReturn;
    }

    /**
     * Set method return value.
     * @param mixed $methodReturn
     * @return Fab_Soap_Server_MessageContext
     */
    public function setMethodReturn($methodReturn)
    {
        $this->_methodReturn = $methodReturn;
        return $this;
    }

    /**
     * Get SOAP headers.
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Get a specific SOAP header.
     * @param string $name header name
     * @return mixed header value
     */
    public function getHeader($name)
    {
        if (isset($this->_headers[$name])) {
            return $this->_headers[$name];
        } else {
            return null;
        }
    }

    /**
     * Set a SOAP header.
     * @param string $name header name
     * @param mixed $value header value
     */
    public function setHeader($name, $value)
    {
        $this->_headers[$name] = $value;
    }

}
