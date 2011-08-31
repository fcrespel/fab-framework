<?php

class Fab_Auth_Adapter_Composite extends Fab_Auth_Adapter_Abstract
{
    /** @var Zend_Auth_Adapter_Interface[] */
    protected $_adapters = array();

    /** @var Zend_Auth_Adapter_Interface */
    protected $_successAdapter = null;

    /**
     * Get the array of adapters to use for authentication.
     * @return Zend_Auth_Adapter_Interface[]
     */
    public function getAdapters()
    {
        return $this->_adapters;
    }

    /**
     * Get the array of adapters to use for authentication.
     * @param Zend_Auth_Adapter_Interface[] $adapters
     */
    public function setAdapters($adapters)
    {
        $this->_adapters = $adapters;
    }

    /**
     * Add an adapter to use for authentication.
     * @param Zend_Auth_Adapter_Interface $adapter
     */
    public function addAdapter($adapter)
    {
        $this->_adapters[] = $adapter;
    }
    
    /**
     * Get the authentication source adapter (only in case of success).
     * @return Zend_Auth_Adapter_Interface
     */
    public function getAuthSourceAdapter()
    {
        return $this->_successAdapter;
    }

    /**
     * Get the authentication source name (only in case of success).
     * @return string|null
     */
    public function getAuthSourceName()
    {
        return $this->_successAdapter !== null ? get_class($this->_successAdapter) : null;
    }

    /**
     * Get the authenticated account object, if available.
     * The exact return value depends on the backing authentication adapter.
     * @return object|null
     */
    public function getAccountObject()
    {
        if ($this->_successAdapter === null)
            return null;

        $methods = array('getAccountObject', 'getResultRowObject');
        foreach ($methods as $method) {
            if (is_callable(array($this->_successAdapter, $method))) {
                return $this->_successAdapter->$method();
            }
        }
        return null;
    }

    /**
     * Authenticate the user.
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_validateParams();
        $result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE, '', array('No authentication adapter provided in ' . get_class()));
        foreach ($this->_adapters as $adapter) {
            $adapter->setIdentity($this->_identity);
            $adapter->setCredential($this->_credential);
            $result = $adapter->authenticate();
            if ($result->getCode() == Zend_Auth_Result::SUCCESS) {
                // Break on success
                $this->_successAdapter = $adapter;
                break;
            } else if ($result->getCode() != Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND) {
                // Break on failure not due to identity not found
                break;
            }
        }
        return $result;
    }

}
