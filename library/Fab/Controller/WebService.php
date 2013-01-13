<?php

abstract class Fab_Controller_WebService extends Fab_Controller_WebService_Abstract
{

    /** @var Zend_Uri */
    protected $_soapUri;

    /** @var Zend_Uri */
    protected $_wsdlUri;

    /**
     * Get the SOAP service URI.
     * @return Zend_Uri
     */
    protected function _getSoapUri()
    {
        if (null === $this->_soapUri) {
            $uri = $this->_getAutoDiscover()->getUri();
            $uri->setPath($this->view->url(array('action' => 'soap')));
            $this->_soapUri = $uri;
        }
        return $this->_soapUri;
    }

    /**
     * Get the WSDL document URI.
     * @return Zend_Uri
     */
    protected function _getWsdlUri()
    {
        if (null === $this->_wsdlUri) {
            $uri = $this->_getAutoDiscover()->getUri();
            $uri->setPath($this->view->url(array('action' => 'wsdl')));
            $this->_wsdlUri = $uri;
        }
        return $this->_wsdlUri;
    }

    /**
     * Controller initialization.
     */
    public function init() {
        // Switch relevant actions to XML
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        $contextSwitch->setActionContext('wsdl', 'xml')
                      ->setActionContext('soap', 'xml')
                      ->setActionContext('rest', 'xml')
                      ->initContext('xml');
    }

    /**
     * Code executed before dispatching the action.
     * Used here to invalidate the cache in any action by specifying ?invalidateCache in the URL.
     */
    public function preDispatch()
    {
        if ($this->getRequest()->getParam('invalidateCache') !== null) {
            $this->_invalidateCache();
        }
    }

    /**
     * Landing page.
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->getParam('wsdl') !== null) {
            $this->_forward('wsdl');
        } else if ($request->isPost()) {
            $this->_forward('soap');
        } else {
            $this->_forward('list');
        }
    }

    /**
     * Web Service API page.
     */
    public function listAction()
    {
        $operations = array();
        $functions = $this->_getSoapServer()->getOperations();
        foreach ($functions as $function) {
            $params = $function->getParameters();
            $args = array();
            foreach ($params as $param) {
                $args[] = $param->getName();
            }
            $operations[] = $function->getName() . '(' . implode(', ', $args) . ')';
        }
        $this->view->operations = $operations;
        $this->view->headTitle(ucfirst($this->getRequest()->getControllerName()) . ' API');
    }

    /**
     * Output the WSDL document.
     */
    public function wsdlAction()
    {
        $wsdl = $this->_getWsdl();
        
        // Bypass view renderer for performance optimization
        $this->_helper->viewRenderer->setNoRender();
        echo $wsdl;
    }

    /**
     * SOAP Web Service requests handling.
     */
    public function soapAction()
    {
        // Let Zend_Soap_Server handle the request
        $this->_getSoapServer()->handle($this->_getRequestBody());
        
        // Content-Type header already added by ext_soap, avoid duplicate
        $this->getResponse()->clearHeader('Content-Type');
        
        // Bypass view renderer for performance optimization
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * REST Web Service requests handling.
     */
    public function restAction()
    {
        // Let Zend_Rest_Server handle the request
        $this->_getRestServer()->handle();
        
        // Content-Type header already added by REST server, avoid duplicate
        $this->getResponse()->clearHeader('Content-Type');
        
        // Bypass view renderer for performance optimization
        $this->_helper->viewRenderer->setNoRender();
    }

}
