<?php

class Fab_Controller_Plugin_Maintenance extends Zend_Controller_Plugin_Abstract
{
    /** @var boolean */
    protected $_enabled = false;
    
    /** @var string */
    protected $_message;
    
    /**
     * Load options from the bootstrap. 
     */
    protected function _loadOptions()
    {
        $options = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOptions();
        if (isset($options['maintenance'])) {
            $options = $options['maintenance'];
            if (isset($options['enabled']))
                $this->setEnabled($options['enabled']);
            if (isset($options['message']))
                $this->setMessage($options['message']);
        }
    }
    
    /**
     * Check if maintenance is enabled.
     * @return boolean 
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }
    
    /**
     * Set whether maintenance is enabled.
     * @param boolean $enabled 
     */
    public function setEnabled($enabled)
    {
        $this->_enabled = $enabled;
    }
    
    /**
     * Get the maintenance message.
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }
    
    /**
     * Set the maintenance message.
     * @param string $message 
     */
    public function setMessage($message)
    {
        $this->_message = $message;
    }

    /**
     * Called before Zend_Controller_Front enters its dispatch loop.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->_loadOptions();
        if ($this->isEnabled()) {
            $msg = $this->getMessage();
            if (empty($msg))
                $msg = 'This service is currently unavailable due to maintenance. Please try again later.';

            $request->setParam('maintenance_msg', $msg)
                    ->setModuleName('default')
                    ->setControllerName('error')
                    ->setActionName('maintenance');
        }
    }
}
