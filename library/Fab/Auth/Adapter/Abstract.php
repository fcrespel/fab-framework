<?php

abstract class Fab_Auth_Adapter_Abstract implements Zend_Auth_Adapter_Interface
{
     /** @var string */
    protected $_identity = null;

    /** @var string */
    protected $_credential = null;

    /**
     * Get the identity (username) used for authentication.
     * @return string
     */
    public function getIdentity()
    {
        return $this->_identity;
    }

    /**
     * Set the identity (username) used for authentication.
     * @param string $identity
     * @return self
     */
    public function setIdentity($identity)
    {
        $this->_identity = $identity;
        return $this;
    }

    /**
     * Get the credential (password) used for authentication.
     * @return string
     */
    public function getCredential()
    {
        return $this->_credential;
    }

    /**
     * Set the credential (password) used for authentication.
     * @param string $credential
     * @return self
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }

    /**
     * Validate parameters configured on this instance
     * before performing authentication.
     * @throws Zend_Auth_Adapter_Exception
     */
    protected function _validateParams()
    {
        if (empty($this->_identity)) {
            throw new Zend_Auth_Adapter_Exception('An identity value must be supplied for the ' . get_class() . ' authentication adapter.');
        } else if (empty($this->_credential)) {
            throw new Zend_Auth_Adapter_Exception('A credential value must be supplied for the ' . get_class() . ' authentication adapter.');
        }
    }
}
