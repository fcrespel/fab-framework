<?php

class Fab_Soap_Server_Proxy
{

    /** @var Fab_Soap_Server SOAP server */
    protected $_server = null;

    /** @var object Service object */
    protected $_serviceObject = null;
    
    /** @var array Handler chain */
    protected $_handlers = array();

    /** @var Fab_Soap_Server_MessageContext */
    protected $_messageContext = null;

    /**
     * This class serves as an intermediate (proxy) class between the server and the
     * actual service class, allowing pre-processing function arguments and return values.
     * @param Fab_Soap_Server $server SOAP server instance
     * @param string $className name or instance of the handling class to proxy
     * @param array $classArgs arguments used to instantiate the handling class
     */
    public function __construct($server, $className = null, $classArgs = array())
    {
        $this->_server = $server;
        if (is_object($className)) {
            $this->setObject($className);
        } else if (is_string($className)) {
            $this->setClass($className, $classArgs);
        }
    }

    /**
     * Set the service class to proxy.
     * @param string $className name of the handling class to proxy.
     * @param array $classArgs arguments used to instantiate the handling class.
     */
    public function setClass($className, $classArgs = array())
    {
        if (!is_string($className)) {
            throw new Zend_Soap_Server_Exception('Invalid class argument (' . gettype($className) . ')');
        }

        if (!class_exists($className)) {
            throw new Zend_Soap_Server_Exception('Class "' . $className . '" does not exist');
        }

        $class = new ReflectionClass($className);
        $constructor = $class->getConstructor();
        if ($constructor === null) {
            $this->setObject($class->newInstance());
        } else {
            $this->setObject($class->newInstanceArgs($classArgs));
        }
    }

    /**
     * Set the service object to proxy.
     * @param object $object
     */
    public function setObject($object)
    {
        if (!is_object($object)) {
            throw new Zend_Soap_Server_Exception('Invalid object argument (' . gettype($object) . ')');
        }

        $this->_serviceObject = $object;
    }

    /**
     * Add a handler to the end of the chain.
     * @param Fab_Soap_Server_Handler $handler handler to add
     */
    public function appendHandler(Fab_Soap_Server_Handler $handler)
    {
        array_push($this->_handlers, $handler);
    }

    /**
     * Add a handler to the beginning of the chain.
     * @param Fab_Soap_Server_Handler $handler handler to add
     */
    public function prependHandler(Fab_Soap_Server_Handler $handler)
    {
        array_unshift($this->_handlers, $handler);
    }

    /**
     * Remove a handler from the chain.
     * @param Fab_Soap_Server_Handler $handler
     */
    public function removeHandler(Fab_Soap_Server_Handler $handler)
    {
        if (($index = array_search($handler, $this->_handlers)) !== false) {
            array_splice($this->_handlers, $index, 1);
        }
    }

    /**
     * __call() magic method used to proxy a call to the original handling class,
     * surrounding it with a chain of handlers.
     * @param $name method name
     * @param $arguments method arguments
     * @return mixed whatever the original method returns
     */
    public function __call($name, $arguments)
    {
        if ($this->_serviceObject === null) {
            throw new Zend_Soap_Server_Exception('No service object has been specified');
        }

        $context = $this->_getMessageContext();
        if (!$this->_server->hasOperation($name)) {
            // Unhandled operation means this is a header
            $context->setHeader($name, $arguments[0]);
            
        } else {
            // Normal operation call
            $context->setMethodName($name)
                    ->setMethodArgs($arguments);

            foreach ($this->_handlers as $handler) {
                $handler->preInvoke($context);
            }

            $result = call_user_func_array(array($this->_serviceObject, $context->getMethodName()), $context->getMethodArgs());
            $context->setMethodReturn($result);

            foreach (array_reverse($this->_handlers) as $handler) {
                $handler->postInvoke($context);
            }

            return $context->getMethodReturn();
        }
    }

    /**
     * Get the message context for the current operation.
     * @return Fab_Soap_Server_MessageContext
     */
    protected function _getMessageContext()
    {
        if ($this->_messageContext === null) {
            $this->_messageContext = new Fab_Soap_Server_MessageContext($this->_server, $this->_serviceObject);
        }
        return $this->_messageContext;
    }

}
