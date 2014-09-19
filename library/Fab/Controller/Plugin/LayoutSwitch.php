<?php

class Fab_Controller_Plugin_LayoutSwitch extends Zend_Controller_Plugin_Abstract
{
    /** @var string */
    protected $_layoutPathParam = 'theme';
    
    /** @var string */
    protected $_layoutPathCookie = 'theme';
    
    /** @var string */
    protected $_layoutScriptParam = 'layout';
    
    /** @var string */
    protected $_layoutScriptCookie = 'layout';

    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $layoutPath = $this->_getLayoutPath($request);
        if ($layoutPath != null)
            Zend_Layout::getMvcInstance()->setLayoutPath($layoutPath);
        
        $layoutScript = $this->_getLayoutScript($request);
        if ($layoutScript != null)
            Zend_Layout::getMvcInstance()->setLayout($layoutScript);
    }
    
    /**
     * Get the front controller.
     * @return Zend_Controller_Front
     */
    protected function _getFrontController()
    {
        return Zend_Controller_Front::getInstance();
    }
    
    /**
     * Get the application bootstrap.
     * @return Zend_Application_Bootstrap_Bootstrapper
     */
    protected function _getBootstrap()
    {
        return $this->_getFrontController()->getParam('bootstrap');
    }
    
    /**
     * Check if the current user-agent is a mobile device.
     * @param bool $includeTablet whether to include tablets as mobile devices
     * @return boolean true if the current user-agent is a mobile device
     */
    protected function _isMobile($includeTablet = false)
    {
        $bootstrap = $this->_getBootstrap();
        if ($bootstrap->hasResource('useragent')) {
            $userAgent = $bootstrap->getResource('useragent');
            $device = $userAgent->getDevice();
            if ($userAgent->getBrowserType() == 'mobile') {
                if (!$includeTablet && $device->getFeature('istablet')) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Get the layout path to switch to.
     * @param Zend_Controller_Request_Abstract $request
     * @return string|null layout path to switch to
     */
    protected function _getLayoutPath($request)
    {
        $front = $this->_getFrontController();
        $bootstrap = $this->_getBootstrap();
        $config = $bootstrap->getOptions();
        $moduleName = $request->getModuleName();
        $layoutPathCandidates = array();
        
        // Module-specific layout path
        if (isset($config[$moduleName]['resources']['layout']['layoutPath'])) {
            $layoutPathCandidates[] = $front->getModuleDirectory() . DIRECTORY_SEPARATOR . $config[$moduleName]['resources']['layout']['layoutPath'];
        }
        
        // Request-specific layout path (from query)
        if ($request->getParam($this->_layoutPathParam) != null) {
            $layoutPathCandidates[] = APPLICATION_PATH . '/layouts/' . $request->getParam($this->_layoutPathParam) . '/scripts/';
        }
        
        // Request-specific layout path (from cookie)
        if ($request->getCookie($this->_layoutPathCookie) != null) {
            $layoutPathCandidates[] = APPLICATION_PATH . '/layouts/' . $request->getCookie($this->_layoutPathCookie) . '/scripts/';
        }
        
        // Mobile-specific layout path
        if ($this->_isMobile() && $front->getParam('mobileLayoutPath') != null) {
            $layoutPathCandidates[] = $front->getParam('mobileLayoutPath');
        }
        
        // Find the first valid layout path
        foreach ($layoutPathCandidates as $layoutPath) {
            if ($this->_validateLayoutPath($layoutPath)) {
                return $layoutPath;
            }
        }
        
        return null;
    }
    
    /**
     * Validate a layout path.
     * @param string $layoutPath layout path
     * @return bool true if the layout path is valid
     */
    protected function _validateLayoutPath($layoutPath)
    {
        return $layoutPath != null && file_exists($layoutPath);
    }
    
    /**
     * Get the layout script to switch to.
     * @param Zend_Controller_Request_Abstract $request
     * @return string|null layout script to switch to
     */
    protected function _getLayoutScript($request)
    {
        $front = $this->_getFrontController();
        $bootstrap = $this->_getBootstrap();
        $config = $bootstrap->getOptions();
        $moduleName = $request->getModuleName();
        $layoutScriptCandidates = array();
        
        // Module-specific layout script
        if (isset($config[$moduleName]['resources']['layout']['layout'])) {
            $layoutScriptCandidates[] = $config[$moduleName]['resources']['layout']['layout'];
        }
        
        // Request-specific layout script (from query)
        if ($request->getParam($this->_layoutScriptParam) != null) {
            $layoutScriptCandidates[] = $request->getParam($this->_layoutScriptParam);
        }
        
        // Request-specific layout script (from cookie)
        if ($request->getCookie($this->_layoutScriptCookie) != null) {
            $layoutScriptCandidates[] = $request->getCookie($this->_layoutScriptCookie);
        }
        
        // Mobile-specific layout script
        if ($this->_isMobile() && $front->getParam('mobileLayout') != null) {
            $layoutScriptCandidates[] = $front->getParam('mobileLayout');
        }
        
        // Find the first valid layout script
        foreach ($layoutScriptCandidates as $layoutScript) {
            if ($this->_validateLayoutScript($layoutScript)) {
                return $layoutScript;
            }
        }
        
        return null;
    }
    
    /**
     * Validate a layout script.
     * @param string $layoutScript layout script
     * @return bool true if the layout script is valid
     */
    protected function _validateLayoutScript($layoutScript)
    {
        $layoutScriptPath = Zend_Layout::getMvcInstance()->getLayoutPath() . '/' . $layoutScript . '.' . Zend_Layout::getMvcInstance()->getViewSuffix();
        return $layoutScript != null && file_exists($layoutScriptPath);
    }
}
