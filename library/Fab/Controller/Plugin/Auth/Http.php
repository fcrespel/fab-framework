<?php

class Fab_Controller_Plugin_Auth_Http extends Zend_Controller_Plugin_Abstract
{
    /**
     * Called before Zend_Controller_Front enters its dispatch loop.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        // Parse the HTTP Authorization header
        $creds = $this->_parseHeader();
        if ($creds !== null) {
            // Fill the authentication adapter
            $authAdapter = $this->_getAuthAdapter();
            $authAdapter->setIdentity($creds[0]);
            $authAdapter->setCredential($creds[1]);

            // Perform authentication
            $result = $authAdapter->authenticate();
            if ($result->isValid()) {
                $this->_storeIdentity($result->getIdentity());
            }
        }
    }

    /**
     * Get the authentication adapter.
     * @return Zend_Auth_Adapter_Interface
     */
    protected function _getAuthAdapter()
    {
        $front = Zend_Controller_Front::getInstance();
        return $front->getParam('bootstrap')->getResource('authAdapter');
    }

    /**
     * Parse the HTTP Authorization header.
     * @return array
     */
    protected function _parseHeader()
    {
        $authHeader = $this->getRequest()->getHeader('Authorization');
        if (!$authHeader) {
            return null;
        }

        list($clientScheme) = explode(' ', $authHeader);
        $clientScheme = strtolower($clientScheme);
        if ($clientScheme == 'basic') {
            // Basic auth
            $auth = substr($authHeader, strlen('Basic '));
            $auth = base64_decode($auth);
            if (!$auth || !ctype_print($auth)) {
                return null;
            }

            $creds = array_filter(explode(':', $auth));
            if (count($creds) != 2) {
                return null;
            }
            return $creds;

        } else if ($clientScheme == 'bearer') {
            // OAuth token
            $auth = substr($authHeader, strlen('Bearer '));
            if (!$auth || !ctype_print($auth)) {
                return null;
            }

            $creds = array('token', $auth);
            return $creds;

        } else {
            // Unknown
            return null;
        }
    }

    /**
     * Store the authenticated identity for Zend_Auth to use.
     * This method automatically sets Zend_Auth storage to non-persistent,
     * to avoid creating useless short-lived sessions.
     * @param mixed $identity 
     */
    protected function _storeIdentity($identity)
    {
        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_NonPersistent());
        Zend_Auth::getInstance()->getStorage()->write($identity);
    }
}
