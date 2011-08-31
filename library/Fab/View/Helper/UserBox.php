<?php

class Fab_View_Helper_UserBox extends Zend_View_Helper_Abstract
{
    protected $_identity = null;

    public function userBox()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $this->_identity = $auth->getIdentity();
        }
        return $this;
    }
    
    public function __toString()
    {
        $items = array();
        if ($this->_identity !== null) {
            $items[] = 'Logged in as <strong>' . $this->_identity . '</strong>';
            $items[] = '<a href="' . $this->_getLoginUrl() . '">Logout</a>';
        } else {
            $items[] = '<a href="' . $this->_getLogoutUrl() . '">Login</a>';
        }
        
        return implode(' - ', $items);
    }

    protected function _getLoginUrl()
    {
        return $this->view->url(array('controller' => 'auth', 'action' => 'logout'), null, true);
    }

    protected function _getLogoutUrl()
    {
        return $this->view->url(array('controller' => 'auth', 'action' => 'login'), null, true);
    }
}
