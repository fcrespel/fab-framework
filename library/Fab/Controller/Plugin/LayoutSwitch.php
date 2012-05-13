<?php

class Fab_Controller_Plugin_LayoutSwitch extends Zend_Controller_Plugin_Abstract
{
    /** @var string */
    protected $_layoutPathParam = 'theme';
    
    /** @var string */
    protected $_layoutParam = 'layout';

    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOptions();
        $moduleName = $request->getModuleName();

        if (isset($config[$moduleName]['resources']['layout']['layoutPath'])) {
            // Module-specific layout path
            $layoutPath = $config[$moduleName]['resources']['layout']['layoutPath'];
            $moduleDir = Zend_Controller_Front::getInstance()->getModuleDirectory();
            Zend_Layout::getMvcInstance()->setLayoutPath($moduleDir . DIRECTORY_SEPARATOR . $layoutPath);
        } else if ($request->getParam($this->_layoutPathParam) != null) {
            // Request-specific layout path
            $layoutPath = APPLICATION_PATH . '/layouts/' . $request->getParam($this->_layoutPathParam) . '/scripts/';
            if (file_exists($layoutPath))
                Zend_Layout::getMvcInstance()->setLayoutPath($layoutPath);
        }

        if (isset($config[$moduleName]['resources']['layout']['layout'])) {
            // Module-specific layout script
            $layoutScript = $config[$moduleName]['resources']['layout']['layout'];
            Zend_Layout::getMvcInstance()->setLayout($layoutScript);
        } else if ($request->getParam($this->_layoutParam) != null) {
            // Request-specific layout script
            $layoutScript = $request->getParam($this->_layoutParam);
            $layoutScriptPath = Zend_Layout::getMvcInstance()->getLayoutPath() . '/' . $layoutScript . '.' . Zend_Layout::getMvcInstance()->getViewSuffix();
            if (file_exists($layoutScriptPath))
                Zend_Layout::getMvcInstance()->setLayout($layoutScript);
        }
    }
}
