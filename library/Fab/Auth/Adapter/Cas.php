<?php

class Fab_Auth_Adapter_Cas extends Zend_Auth_Adapter_Cas
{
     /** @var string */
    protected $_identity = null;
    
    /** @var array */
    protected $_attributes = array();
    
    /** @var string */
    protected $_ticketsApiPath = 'api/rest/tickets/';
    
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
    
    /**
     * Get the tickets API URL path.
     * @return string
     */
    public function getTicketsApiPath()
    {
        return $this->_ticketsApiPath;
    }

    /**
     * Set the tickets API URL path.
     * @param string $ticketsApiPath
     */
    public function setTicketsApiPath($ticketsApiPath)
    {
        $this->_ticketsApiPath = $ticketsApiPath;
    }

    /**
     * Get the full tickets API URL.
     * @param string $ticket optional ticket
     * @return string
     */
    public function getTicketsApiURL($ticket = '')
    {
        $url = $this->getUrl();
        $url .= (substr($url, -1) == '/') ? '' : '/';
        $url .= $this->_ticketsApiPath;
        $url .= (substr($url, -1) == '/') ? '' : '/';
        $url .= $ticket;
        return $url;
    }
    
    /**
     * Get a Ticket Granting Ticket (TGT) from the CAS REST API.
     * @param string $username
     * @param string $password
     * @return string Ticket Granting Ticket (TGT)
     */
    public function getTicketGrantingTicket($username, $password)
    {
        if (! $this->_clientAdapter instanceof Zend_Http_Client_Adapter_Interface) {
            $this->setClientAdapter();
        }

        $client = new Zend_Http_Client($this->getTicketsApiURL(), array('adapter' => $this->_clientAdapter));
        $client->setParameterPost('username', $username);
        $client->setParameterPost('password', $password);
        $client->setEncType(Zend_Http_Client::ENC_URLENCODED);

        $response = $client->request(Zend_Http_Client::POST);
        if ($response->getStatus() == 201) {
            $tgt = $response->getHeader('Location');
            if (($pos = strrpos($tgt, '/')) !== false) {
                $tgt = substr($tgt, $pos + 1);
            }
            return $tgt;
        } else {
            throw new Zend_Auth_Adapter_Exception('Failed to get Ticket Granting Ticket from CAS (status code ' . $response->getStatus() . ')');
        }
    }
    
    /**
     * Get a Service Ticket (ST) from the CAS REST API.
     * @param string $tgt Ticket Granting Ticket (TGT)
     * @param string $service service to get a ticket for
     * @return string Service Ticket (ST)
     */
    public function getServiceTicket($tgt, $service)
    {
        if (! $this->_clientAdapter instanceof Zend_Http_Client_Adapter_Interface) {
            $this->setClientAdapter();
        }

        $client = new Zend_Http_Client($this->getTicketsApiURL($tgt), array('adapter' => $this->_clientAdapter));
        $client->setParameterPost('service', $service);
        $client->setEncType(Zend_Http_Client::ENC_URLENCODED);

        $response = $client->request(Zend_Http_Client::POST);
        if ($response->getStatus() == 200) {
            return $response->getBody();
        } else {
            throw new Zend_Auth_Adapter_Exception('Failed to get Service Ticket from CAS (status code ' . $response->getStatus() . ')');
        }
    }
    
    /**
     * Destroy a Ticket Granting Ticket (TGT) from the CAS REST API.
     * @param string $tgt Ticket Granting Ticket (TGT)
     * @return boolean true if the TGT was properly destroyed, false otherwise
     */
    public function destroyTicketGrantingTicket($tgt)
    {
        if (! $this->_clientAdapter instanceof Zend_Http_Client_Adapter_Interface) {
            $this->setClientAdapter();
        }

        $client = new Zend_Http_Client($this->getTicketsApiURL($tgt), array('adapter' => $this->_clientAdapter));

        $response = $client->request(Zend_Http_Client::DELETE);
        if ($response->getStatus() == 200) {
            return true;
        } else {
            return false;
        }
    }
}
