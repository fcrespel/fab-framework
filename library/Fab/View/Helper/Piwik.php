<?php

class Fab_View_Helper_Piwik extends Zend_View_Helper_Abstract
{
    /** @var array */
    protected static $_enabledView = array();

    /** @var string */
    protected $_piwikBaseUrl;
    
    /** @var int */
    protected $_siteId;

    /**
     * Set the View object.
     * @param Zend_View_Interface $view 
     */
    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);
        $oid = spl_object_hash($view);
        if (!isset(self::$_enabledView[$oid])) {
            $view->addBasePath(dirname(__FILE__) . '/Piwik/views');
            self::$_enabledView[$oid] = true;
        }
    }
    
    /**
     * Set options for this view helper.
     * @param array options
     * @return self
     */
    public function setOptions($options = array())
    {
        if (isset($options['baseUrl']))
            $this->setPiwikBaseUrl($options['baseUrl']);
        if (isset($options['siteId']))
            $this->setSiteId($options['siteId']);
        return $this;
    }
    
    /**
     * Get the Piwik tracking code for the configured Piwik URL and site ID.
     */
    public function piwik($piwikBaseUrl = null, $siteId = null)
    {
        // Get Piwik base URL
        if (empty($piwikBaseUrl))
            $piwikBaseUrl = $this->getPiwikBaseUrl();
        if (empty($piwikBaseUrl))
            throw new Fab_View_Exception('Missing piwikBaseUrl option in ' . get_class());
        
        // Get Piwik site ID
        if (empty($siteId))
            $siteId = $this->getSiteId();
        if (empty($siteId))
            throw new Fab_View_Exception('Missing siteId option in ' . get_class());
        
        // Remove HTTP(S) from base URL and add trailing /
        $piwikBaseUrl = preg_replace('#^https?://#', '', $piwikBaseUrl);
        if (substr($piwikBaseUrl, -1) != '/')
            $piwikBaseUrl .= '/';
        
        // Render the partial
        return $this->view->partial('tracking.phtml', array(
            'piwikBaseUrl'  => $piwikBaseUrl,
            'siteId'        => intval($siteId),
        ));
    }
    
    /**
     * Get the base URL of Piwik.
     * @return string
     */
    public function getPiwikBaseUrl()
    {
        return $this->_piwikBaseUrl;
    }

    /**
     * Set the base URL of Piwik.
     * @param string $piwikBaseUrl
     * @return self
     */
    public function setPiwikBaseUrl($piwikBaseUrl)
    {
        $this->_piwikBaseUrl = $piwikBaseUrl;
        return $this;
    }

    /**
     * Get the site identifier to use for tracking.
     * @return int
     */
    public function getSiteId()
    {
        return $this->_siteId;
    }

    /**
     * Set the site identifier to use for tracking.
     * @param int $siteId
     * @return self
     */
    public function setSiteId($siteId)
    {
        $this->_siteId = $siteId;
        return $this;
    }
}
