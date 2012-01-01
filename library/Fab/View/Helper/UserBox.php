<?php

class Fab_View_Helper_UserBox extends Zend_View_Helper_Abstract
{
    /** @var mixed */
    protected $_identity = null;
    
    /** @var string */
    protected $_loginUrl = null;
    
    /** @var string */
    protected $_logoutUrl = null;
    
    /** @var string */
    protected $_loginMessage = '<a href="%1$s">Login</a>';
    
    /** @var string */
    protected $_logoutMessage = 'Logged in as <strong>%2$s</strong> - <a href="%1$s">Logout</a>';

    /**
     * User login/logout box helper.
     * @param array $options
     * @return self
     */
    public function userBox($options = array())
    {
        $this->setOptions($options);
        return $this;
    }
    
    /**
     * Render a user login/logout message.
     * @return string
     */
    public function __toString()
    {
        if ($this->getIdentity() === null) {
            return sprintf($this->getLoginMessage(), $this->getLoginUrl());
        } else {
            return sprintf($this->getLogoutMessage(), $this->getLogoutUrl(), $this->getIdentity());
        }
    }
    
    /**
     * Get the value of an option identified by a key.
     * @param string $key
     * @return mixed
     */
    public function getOption($key)
    {
        $method = 'get' . ucfirst($key);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return null;
    }
    
    /**
     * Set the value of an option identified by a key.
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setOption($key, $value)
    {
        $method = 'set' . ucfirst($key);
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
        return $this;
    }
    
    /**
     * Set the value of an array of options.
     * @param array $options
     * @return self
     */
    public function setOptions($options = array())
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
        return $this;
    }
    
    /**
     * Get the identity of the user currently logged in.
     * By default, this is the identity known by Zend_Auth.
     * @return mixed
     */
    public function getIdentity()
    {
        if ($this->_identity === null && Zend_Auth::getInstance()->hasIdentity()) {
            $this->_identity = Zend_Auth::getInstance()->getIdentity();
        }
        return $this->_identity;
    }
    
    /**
     * Set the identity of the user currently logged in.
     * @param mixed $identity
     * @return self
     */
    public function setIdentity($identity)
    {
        $this->_identity = $identity;
        return $this;
    }

    /**
     * Get the login URL.
     * By default, this is the 'login' action of the 'auth' controller.
     * @return string
     */
    public function getLoginUrl()
    {
        if ($this->_loginUrl === null) {
            $this->_loginUrl = $this->view->url(array('controller' => 'auth', 'action' => 'login'), null, true);
        }
        return $this->_loginUrl;
    }
    
    /**
     * Set the login URL.
     * @param string $loginUrl
     * @return self
     */
    public function setLoginUrl($loginUrl)
    {
        $this->_loginUrl = $loginUrl;
        return $this;
    }

    /**
     * Get the logout URL.
     * By default, this is the 'logout' action of the 'auth' controller.
     * @return string
     */
    public function getLogoutUrl()
    {
        if ($this->_logoutUrl === null) {
            $this->_logoutUrl = $this->view->url(array('controller' => 'auth', 'action' => 'logout'), null, true);
        }
        return $this->_logoutUrl;
    }
    
    /**
     * Set the logout URL.
     * @param string $logoutUrl
     * @return self
     */
    public function setLogoutUrl($logoutUrl)
    {
        $this->_logoutUrl = $logoutUrl;
        return $this;
    }
    
    /**
     * Get the login message.
     * @return string
     */
    public function getLoginMessage()
    {
        return $this->_loginMessage;
    }
    
    /**
     * Set the login message.
     * @param string $loginMessage
     * @return self
     */
    public function setLoginMessage($loginMessage)
    {
        $this->_loginMessage = $loginMessage;
        return $this;
    }
    
    /**
     * Get the logout message.
     * @return string
     */
    public function getLogoutMessage()
    {
        return $this->_logoutMessage;
    }

    /**
     * Set the logout message.
     * @param string $logoutMessage
     * @return self
     */
    public function setLogoutMessage($logoutMessage)
    {
        $this->_logoutMessage = $logoutMessage;
        return $this;
    }
}
