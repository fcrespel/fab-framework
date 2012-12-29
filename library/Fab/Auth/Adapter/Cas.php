<?php

class Fab_Auth_Adapter_Cas extends Zend_Auth_Adapter_Cas
{
     /** @var string */
    protected $_identity = null;
    
    /** @var array */
    protected $_attributes = array();
    
    /**
     * Create an instance of the CAS authentication adapter.
     * @param mixed $options an array or Zend_Config object with adapter parameters
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
    }
    
    /**
     * Perform Zend_Auth authentication.
     * @return Zend_Auth_Result
     */ 
    public function authenticate()
    {
        $result = $this->validateTicket($this->getTicket(), $this->getService());
        if ($result) {
            $this->_identity = $result['user'];
            $this->_attributes = $this->_parseAttributes($result);
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $result['user'], $result);
        }  else {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, $this->_errors);
        }
    }

    /**
     * Get the authenticated identity (username).
     * @return string
     */
    public function getIdentity()
    {
        return $this->_identity;
    }
    
    /**
     * Get the authenticated account object, if available.
     * This object is populated with attributes returned by the CAS server.
     * @param  array $returnAttribs
     * @param  array $omitAttribs
     * @return object|boolean
     */
    public function getAccountObject(array $returnAttribs = array(), array $omitAttribs = array())
    {
        if (!isset($this->_identity) || !is_array($this->_attributes))
            return false;
        
        $returnObject = new stdClass();
        $returnAttribs = array_map('strtolower', $returnAttribs);
        $omitAttribs = array_map('strtolower', $omitAttribs);
        
        foreach ($this->_attributes as $key => $value) {
            $key = strtolower($key);
            if ((count($returnAttribs) == 0 || in_array($key, $returnAttribs)) && !in_array($key, $omitAttribs)) {
                $returnObject->$key = $value;
            }
        }
        
        return $returnObject;
    }
    
    /**
     * Parse attributes from a successful CAS validation response.
     * @param array $result
     * @return array
     */
    protected function _parseAttributes($result)
    {
        if (is_array($result) && isset($result['attributes']) && is_object($result['attributes']))
            return get_object_vars($result['attributes']);
        else
            return array();
    }
}
