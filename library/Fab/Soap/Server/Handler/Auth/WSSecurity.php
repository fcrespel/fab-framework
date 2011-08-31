<?php

class Fab_Soap_Server_Handler_Auth_WSSecurity extends Fab_Soap_Server_Handler_Auth_Abstract
{
    
    /**
     * WS-Security header handler implementation, supporting the UsernameToken
     * profile with a PasswordText-typed Password element.
     * The Username and Password supplied in the UsernameToken will be used
     * with a Zend_Auth_Adapter_Interface to authenticate the user.
     * @param Zend_Auth_Adapter_Interface $authAdapter 
     */
    public function __construct($authAdapter = null)
    {
        $this->setAuthAdapter($authAdapter);
    }

    public function preInvoke(Fab_Soap_Server_MessageContext $context)
    {
        // Parse the Security header
        $security = $context->getHeader('Security');
        $usernameToken = $this->_getUsernameToken($security);
        $username = $this->_getUsername($usernameToken);
        $password = $this->_getPassword($usernameToken);

        // Fill the authentication adapter
        $authAdapter = $this->getAuthAdapter();
        $authAdapter->setIdentity($username);
        $authAdapter->setCredential($password);

        // Perform authentication
        $result = $authAdapter->authenticate();
        if (!$result->isValid()) {
            $messages = $result->getMessages();
            throw new SoapFault('wsse:FailedAuthentication', 'The security token could not be authenticated or authorized (' . $messages[0] . ')');
        }
    }

    /**
     * Get a UsernameToken object from the <wsse:Security> header.
     * @param mixed $security
     * @return mixed
     */
    protected function _getUsernameToken($security)
    {
        // Check input
        if (!isset($security) || !is_object($security)) {
            throw new SoapFault('wsse:InvalidSecurity', 'An error was discovered processing the <wsse:Security> header');
        }
        
        // Try to find the UsernameToken element (depends on PHP XML parsing)
        $usernameToken = null;
        if (isset($security->UsernameToken) && is_object($security->UsernameToken)) {
            $usernameToken = $security->UsernameToken;
        } else if (isset($security->any) && is_array($security->any) && isset($security->any['UsernameToken']) && is_object($security->any['UsernameToken'])) {
            $usernameToken = $security->any['UsernameToken'];
        } else {
            throw new SoapFault('wsse:UnsupportedSecurityToken', 'An unsupported token was provided');
        }

        return $usernameToken;
    }

    /**
     * Get the username specified in the UsernameToken element.
     * @param mixed $usernameToken
     * @return string
     */
    protected function _getUsername($usernameToken)
    {
        // Check input
        if (!isset($usernameToken->Username)) {
            throw new SoapFault('wsse:InvalidSecurityToken', 'An invalid security token was provided (missing Username element)');
        }

        // Try to find the Username element (depends on PHP XML parsing)
        $username = null;
        if (is_string($usernameToken->Username)) {
            $username = $usernameToken->Username;
        } else if (is_object($usernameToken->Username) && isset($usernameToken->Username->_) && is_string($usernameToken->Username->_)) {
            $username = $usernameToken->Username->_;
        } else {
            throw new SoapFault('wsse:InvalidSecurityToken', 'An invalid security token was provided (invalid Username element)');
        }

        // Ensure the value is not empty
        if (empty($username)) {
            throw new SoapFault('wsse:InvalidSecurityToken', 'An invalid security token was provided (missing value for Username element)');
        }

        return $username;
    }

    /**
     * Get the password specified in the UsernameToken element.
     * @param mixed $usernameToken
     * @return string
     */
    protected function _getPassword($usernameToken)
    {
        // Check input
        $passwordElement = null;
        if (isset($usernameToken->Password)) {
            $passwordElement = $usernameToken->Password;
        } else if (isset($usernameToken->any) && is_array($usernameToken->any) && isset($usernameToken->any['Password']) && is_object($usernameToken->any['Password'])) {
            $passwordElement = $usernameToken->any['Password'];
        } else {
            throw new SoapFault('wsse:InvalidSecurityToken', 'An invalid security token was provided (missing Password element)');
        }

        // Try to find the Password element (depends on PHP XML parsing)
        $password = null;
        if (is_string($passwordElement)) {
            $password = $passwordElement;
        } else if (is_object($passwordElement) && isset($passwordElement->_) && is_string($passwordElement->_)) {
            $password = $passwordElement->_;
        } else {
            throw new SoapFault('wsse:InvalidSecurityToken', 'An invalid security token was provided (invalid Password element)');
        }

        // Ensure the value is not empty
        if (empty($password)) {
            throw new SoapFault('wsse:InvalidSecurityToken', 'An invalid security token was provided (missing value for Password element)');
        }

        return $password;
    }

}
