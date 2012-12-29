<?php

class Fab_Auth_Adapter_Composite extends Fab_Auth_Adapter_Abstract
{
    /** @var array */
    protected $_adapters = array();
    
    /** @var string */
    protected $_successAdapterName = null;

    /** @var Zend_Auth_Adapter_Interface */
    protected $_successAdapter = null;

    /**
     * Get the associative array of adapters to use for authentication.
     * @return array
     */
    public function getAdapters()
    {
        return $this->_adapters;
    }

    /**
     * Set the associative array of adapters to use for authentication.
     * @param array $adapters
     */
    public function setAdapters(array $adapters)
    {
        $this->_adapters = $adapters;
    }

    /**
     * Add an adapter to use for authentication.
     * @param string $name
     * @param Zend_Auth_Adapter_Interface $adapter
     */
    public function addAdapter($name, $adapter)
    {
        $this->_adapters[$name] = $adapter;
    }
    
    /**
     * Get the authentication source adapter (only in case of success).
     * @return Zend_Auth_Adapter_Interface|null
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
        return $this->_successAdapterName;
    }

    /**
     * Get the authenticated account object, if available.
     * The exact return value depends on the backing authentication adapter.
     * @return object|boolean
     */
    public function getAccountObject()
    {
        if ($this->_successAdapter === null)
            return false;

        $methods = array('getAccountObject', 'getResultRowObject');
        foreach ($methods as $method) {
            if (is_callable(array($this->_successAdapter, $method))) {
                return $this->_successAdapter->$method();
            }
        }
        return false;
    }

    /**
     * Authenticate the user.
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_validateParams();
        $result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE, '', array('No authentication adapter provided in ' . get_class()));
        foreach ($this->_adapters as $name => $adapter) {
            $adapter->setIdentity($this->_identity);
            $adapter->setCredential($this->_credential);
            $result = $adapter->authenticate();
            if ($result->getCode() == Zend_Auth_Result::SUCCESS) {
                // Break on success
                $this->_successAdapter = $adapter;
                $this->_successAdapterName = $name;
                break;
            } else if ($result->getCode() != Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND) {
                // Break on failure not due to identity not found
                break;
            }
        }
        return $result;
    }

}
