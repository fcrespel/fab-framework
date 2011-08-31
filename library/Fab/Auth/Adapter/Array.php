<?php

class Fab_Auth_Adapter_Array extends Fab_Auth_Adapter_Abstract
{
    protected $_userMap = array();

    public function __construct($userMap = null)
    {
        $this->setUserMap($userMap);
    }

    /**
     * Get the username/password map.
     * @return array
     */
    public function getUserMap()
    {
        return $this->_userMap;
    }

    /**
     * Set the username/password map.
     * @param array $userMap
     * @return self
     */
    public function setUserMap($userMap)
    {
        $this->_userMap = $userMap;
        return $this;
    }

    /**
     * Authenticate the user.
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_validateParams();

        $messages = array();
        $messages[0] = ''; // reserved
        $messages[1] = ''; // reserved

        $username = $this->getIdentity();
        $password = $this->getCredential();

        // Check if username exists
        if (!isset($this->_userMap[$username])) {
            $code = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $messages[0] = "Account not found: $username";
            return new Zend_Auth_Result($code, '', $messages);
        }

        // Check if passwords match
        if ($this->_userMap[$username] !== $password) {
            $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            $messages[0] = 'Invalid credentials';
            return new Zend_Auth_Result($code, '', $messages);
        }

        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $username);
    }

}
