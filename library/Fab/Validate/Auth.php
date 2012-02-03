<?php

class Fab_Validate_Auth extends Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const MISSING_AUTH_ADAPTER = 'missingAuthAdapter';
    const MISSING_IDENTITY = 'missingIdentity';
    const AUTH_FAILURE = 'authFailure';

    /**
     * Error messages
     * @var array
     */
    protected $_messageTemplates = array(
        self::MISSING_AUTH_ADAPTER  => 'No authentication adapter was provided to authenticate against',
        self::MISSING_IDENTITY      => 'No identity was provided to authenticate as',
        self::AUTH_FAILURE          => 'Authentication failure, please check your credentials',
    );
    
    /** @var Zend_Auth_Adapter_Interface */
    protected $_authAdapter;
    
    /** @var string */
    protected $_identity;
    
    /**
     * Constructor.
     * @param array $options 
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }
    
    /**
     * Set options for this validator.
     * @param array $options
     * @return self
     */
    public function setOptions(array $options = array())
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }
    
    /**
     * Get the authentication adapter.
     * @return Zend_Auth_Adapter_Interface
     */
    public function getAuthAdapter()
    {
        return $this->_authAdapter;
    }
    
    /**
     * Set the authentication adapter.
     * @param Zend_Auth_Adapter_Interface $authAdapter
     * @return self
     */
    public function setAuthAdapter($authAdapter)
    {
        $this->_authAdapter = $authAdapter;
        return $this;
    }
    
    /**
     * Get the identity to authenticate as.
     * @return string
     */
    public function getIdentity()
    {
        return $this->_identity;
    }
    
    /**
     * Set the identity to authenticate as.
     * @param string $identity
     * @return self
     */
    public function setIdentity($identity)
    {
        $this->_identity = $identity;
        return $this;
    }
    
    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @param  array $context
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue((string) $value);
        
        $authAdapter = $this->getAuthAdapter();
        if ($authAdapter === null) {
            $this->_error(self::MISSING_AUTH_ADAPTER);
            return false;
        }
        
        $identity = $this->getIdentity();
        if ($identity === null) {
            $this->_error(self::MISSING_IDENTITY);
            return false;
        }
        
        $authAdapter->setIdentity($identity);
        $authAdapter->setCredential($value);
        $result = $authAdapter->authenticate();
        if ($result->getCode() != Zend_Auth_Result::SUCCESS) {
            $this->_error(self::AUTH_FAILURE);
            return false;
        }
        
        return true;
    }
}
