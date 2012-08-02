<?php

class Fab_Soap_Server_Handler_Auth_Http extends Fab_Soap_Server_Handler_Auth_Abstract
{
    
    /** @var Zend_Controller_Request_Http */
    protected $_request;

    /** @var Zend_Controller_Response_Http */
    protected $_response;

    /** @var string */
    protected $_realm = 'SOAP';

    /**
     * HTTP authentication handler.
     * @param Zend_Auth_Adapter_Interface $authAdapter
     */
    public function __construct($authAdapter = null)
    {
        $this->setAuthAdapter($authAdapter);
    }

    /**
     * Get the HTTP request.
     * @return Zend_Controller_Request_Http
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set the HTTP request.
     * @param Zend_Controller_Request_Http $request
     * @return self
     */
    public function setRequest($request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Get the HTTP response.
     * @return Zend_Controller_Response_Http
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Set the HTTP response.
     * @param Zend_Controller_Response_Http $response
     * @return self
     */
    public function setResponse($response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Get the authentication realm.
     * @return string
     */
    public function getRealm()
    {
        return $this->_realm;
    }

    /**
     * Set the authentication realm.
     * @param string $realm
     * @return self
     */
    public function setRealm($realm)
    {
        $this->_realm = $realm;
        return $this;
    }

    /**
     * Method called before invoking the actual method.
     * @param Fab_Soap_Server_MessageContext $context message context
     */
    public function preInvoke(Fab_Soap_Server_MessageContext $context)
    {
        // Parse the HTTP Authorization header
        list($username, $password) = $this->_parseHeader();

        // Fill the authentication adapter
        $authAdapter = $this->getAuthAdapter();
        $authAdapter->setIdentity($username);
        $authAdapter->setCredential($password);

        // Perform authentication
        $result = $authAdapter->authenticate();
        if (!$result->isValid()) {
            $messages = $result->getMessages();
            $this->_challengeClient();
            throw new SoapFault('Receiver', 'HTTP authentication failed (' . $messages[0] . ')');
        } else {
            $this->_storeIdentity($result->getIdentity());
        }
    }

    /**
     * Parse the HTTP Authorization header.
     * @return array
     */
    protected function _parseHeader()
    {
        $authHeader = $this->getRequest()->getHeader('Authorization');
        if (!$authHeader) {
            $this->_challengeClient();
            throw new SoapFault('Receiver', 'HTTP Basic Authorization required');
        }

        list($clientScheme) = explode(' ', $authHeader);
        $clientScheme = strtolower($clientScheme);
        if ($clientScheme != 'basic') {
            $this->_challengeClient();
            throw new SoapFault('Receiver', 'HTTP Basic Authorization required');
        }

        $auth = substr($authHeader, strlen('Basic '));
        $auth = base64_decode($auth);
        if (!$auth || !ctype_print($auth)) {
            throw new SoapFault('Receiver', 'Invalid HTTP Basic Authorization header');
        }

        $creds = array_filter(explode(':', $auth));
        if (count($creds) != 2) {
            $this->_challengeClient();
            throw new SoapFault('Receiver', 'Invalid HTTP Basic Authorization header');
        }

        return $creds;
    }

    /**
     * Challenge the client to specify credentials.
     */
    protected function _challengeClient()
    {
        $response = $this->getResponse();
        $response->setHttpResponseCode(401);
        $response->setHeader('WWW-Authenticate', 'Basic realm="' . $this->getRealm() . '"');
    }

}
