<?php

abstract class Fab_Controller_WebService extends Fab_Controller_WebService_Abstract
{

    /** @var Zend_Uri */
    protected $_wsdlUri;

    /** @var Zend_Uri */
    protected $_soapUri;

    /** @var Zend_Uri */
    protected $_jsonRpcUri;

    /** @var Zend_Uri */
    protected $_jsonUri;

    /** @var array */
    protected $_actionContextTypes = array(
        'wsdl'    => 'xml',
        'soap'    => 'xml',
        'rest'    => 'xml',
        'smd'     => 'json',
        'jsonrpc' => 'json',
        'openapi' => 'json',
        'json'    => 'json',
    );

    /**
     * Get the WSDL document URI.
     * @return Zend_Uri
     */
    protected function _getWsdlUri()
    {
        if (null === $this->_wsdlUri) {
            $uri = $this->_getSoapAutoDiscover()->getUri();
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
            $uri = $this->_getSoapAutoDiscover()->getUri();
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
            $uri = $this->_getSoapAutoDiscover()->getUri();
            $uri->setPath($this->view->url(array('action' => 'jsonrpc')));
            $this->_jsonRpcUri = $uri;
        }
        return $this->_jsonRpcUri;
    }

    /**
     * Get the JSON service URI.
     * @return Zend_Uri
     */
    protected function _getJsonUri()
    {
        if (null === $this->_jsonUri) {
            $uri = $this->_getSoapAutoDiscover()->getUri();
            $uri->setPath($this->view->url(array('action' => 'json')) . '/invoke');
            $this->_jsonUri = $uri;
        }
        return $this->_jsonUri;
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
            $this->forward('wsdl');
        } else if ($request->getParam('smd') !== null) {
            $this->forward('smd');
        } else if ($request->isPost()) {
            $this->forward('soap');
        } else {
            $this->forward('list');
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
        $this->_setResponseBody($this->_getWsdl());
    }

    /**
     * SOAP service requests handling.
     */
    public function soapAction()
    {
        $this->_setResponseBody($this->_getSoapServer()->handle($this->_getRequestBody()));
    }

    /**
     * REST-XML service requests handling.
     */
    public function restAction()
    {
        $this->_setResponseBody($this->_getRestServer()->handle());
    }

    /**
     * Output the SMD document.
     */
    public function smdAction()
    {
        $this->_setResponseBody($this->_getJsonServer()->getServiceMap()->toJson());
    }

    /**
     * JSON-RPC service requests handling.
     */
    public function jsonrpcAction()
    {
        $jsonRpcRequest = new Zend_Json_Server_Request();
        $jsonRpcRequest->loadJson($this->_getRequestBody());
        $this->_setResponseBody($this->_getJsonServer()->handle($jsonRpcRequest));
    }

    /**
     * Output the OpenAPI document.
     */
    public function openapiAction()
    {
        $this->_setResponseBody($this->_getOpenApi());
    }

    /**
     * JSON service requests handling.
     */
    public function jsonAction()
    {
        try {
            // Parse request
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Zend_Json_Server_Exception("Invalid Request, POST expected", Zend_Json_Server_Error::ERROR_INVALID_REQUEST);
            }
            $method = $request->getParam('invoke');
            if (empty($method)) {
                throw new Zend_Json_Server_Exception("Invalid Request, missing required parameter 'invoke'", Zend_Json_Server_Error::ERROR_INVALID_REQUEST);
            }
            $body = $this->_getRequestBody();
            try {
                $params = Zend_Json::decode($body);
            } catch (Zend_Json_Exception $e) {
                throw new Zend_Json_Server_Exception($e->getMessage(), Zend_Json_Server_Error::ERROR_PARSE);
            }
            if (!is_array($params)) {
                $params = array();
            }

            // Process request with Zend_Json_Server
            $jsonRpcRequest = new Zend_Json_Server_Request();
            $jsonRpcRequest->setMethod($method);
            $jsonRpcRequest->setParams($params);
            $jsonRpcResponse = $this->_getJsonServer()->handle($jsonRpcRequest);
            if ($jsonRpcResponse->isError()) {
                throw new Zend_Json_Server_Exception($jsonRpcResponse->getError()->getMessage(), $jsonRpcResponse->getError()->getCode(), $jsonRpcResponse->getError()->getData());
            } else {
                $this->_setResponseBody(Zend_Json::encode($jsonRpcResponse->getResult()));
            }

        } catch (Exception $e) {
            $error = new Zend_Json_Server_Error($e->getMessage(), $e->getCode(), $e->getPrevious());
            $this->_setResponseBody($error->toJson(), $error->getCode() > -32100 ? 500 : 400);
        }
    }

}
