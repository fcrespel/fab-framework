<?php

abstract class Fab_Controller_WebService extends Fab_Controller_WebService_Abstract
{

    /** @var Zend_Uri */
    protected $_wsdlUri;

    /** @var Zend_Uri */
    protected $_soapUri;

    /** @var Zend_Uri */
    protected $_jsonRpcUri;
    
    /** @var array */
    protected $_actionContextTypes = array(
        'wsdl'    => 'xml',
        'soap'    => 'xml',
        'rest'    => 'xml',
        'smd'     => 'json',
        'jsonrpc' => 'json',
    );

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
     * Get the JSON-RPC service URI.
     * @return Zend_Uri
     */
    protected function _getJsonRpcUri()
    {
        if (null === $this->_jsonRpcUri) {
            $uri = $this->_getAutoDiscover()->getUri();
            $uri->setPath($this->view->url(array('action' => 'jsonrpc')));
            $this->_jsonRpcUri = $uri;
        }
        return $this->_jsonRpcUri;
    }

    /**
     * Controller initialization.
     */
    public function init() {
        $actionName = $this->getRequest()->getActionName();
        
        // Switch relevant actions to XML or JSON
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        if (isset($this->_actionContextTypes[$actionName])) {
            $actionContextType = $this->_actionContextTypes[$actionName];
            $contextSwitch->setActionContext($actionName, $actionContextType)
                          ->setAutoJsonSerialization(false)
                          ->initContext($actionContextType);
        }
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
        } else if ($request->getParam('smd') !== null) {
            $this->_forward('smd');
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
     * SOAP service requests handling.
     */
    public function soapAction()
    {
        // Let Zend_Soap_Server handle the request
        $this->_setResponseBody($this->_getSoapServer()->handle($this->_getRequestBody()));
        
        // Bypass view renderer for performance optimization
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * REST-XML service requests handling.
     */
    public function restAction()
    {
        // Let Zend_Rest_Server handle the request
        $this->_setResponseBody($this->_getRestServer()->handle());
        
        // Bypass view renderer for performance optimization
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Output the SMD document.
     */
    public function smdAction()
    {
        // Let Zend_Json_Server_Smd handle the request
        $this->_setResponseBody($this->_getJsonServer()->getServiceMap()->toJson());
        
        // Bypass view renderer for performance optimization
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * JSON-RPC service requests handling.
     */
    public function jsonrpcAction()
    {
        // Let Zend_Json_Server handle the request
        $this->_setResponseBody($this->_getJsonServer()->handle());
        
        // Bypass view renderer for performance optimization
        $this->_helper->viewRenderer->setNoRender();
    }

}
