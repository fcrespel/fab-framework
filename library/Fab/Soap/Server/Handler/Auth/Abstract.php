<?php

abstract class Fab_Soap_Server_Handler_Auth_Abstract implements Fab_Soap_Server_Handler
{
    /** @var Zend_Auth_Adapter_Interface */
    protected $_authAdapter = null;

    /**
     * Get the authentication adapter to use.
     * @return Zend_Auth_Adapter_Interface
     */
    public function getAuthAdapter()
    {
        return $this->_authAdapter;
    }

    /**
     * Set the authentication adapter to use.
     * @param Zend_Auth_Adapter_Interface $authAdapter
     * @return self
     */
    public function setAuthAdapter($authAdapter)
    {
        $this->_authAdapter = $authAdapter;
        return $this;
    }

    public function postInvoke(Fab_Soap_Server_MessageContext $context)
    {
        // Unimplemented by default
    }
}
