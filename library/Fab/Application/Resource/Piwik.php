<?php

class Fab_Application_Resource_Piwik extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var Fab_View_Helper_Piwik
     */
    protected $_piwik;

    /**
     * @var Zend_View
     */
    protected $_view;

    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return Fab_View_Helper_Piwik
     */
    public function init()
    {
        return $this->getPiwik();
    }

    /**
     * Retrieve Piwik View Helper
     *
     * @return Fab_View_Helper_Piwik
     */
    public function getPiwik()
    {
        if (null === $this->_piwik) {
            $this->getBootstrap()->bootstrap('view');
            $this->_view = $this->getBootstrap()->view;
            $this->_view->getHelper('piwik')->setOptions($this->getOptions());
            $this->_piwik = $this->_view->getHelper('piwik');
        }

        return $this->_piwik;
    }
}
