<?php

abstract class Fab_Controller_WebService_Abstract extends Zend_Controller_Action
{
    /** @var Fab_Soap_AutoDiscover */
    protected $_soapAutoDiscover;

    /** @var Fab_OpenApi_AutoDiscover */
    protected $_openApiAutoDiscover;

    /** @var array */
    protected $_classmap = array();


    /**
     * Get the WSDL document URI.
     * @return Zend_Uri
     */
    protected abstract function _getWsdlUri();

    /**
     * Get the SOAP service URI.
     * @return Zend_Uri
     */
    protected abstract function _getSoapUri();

    /**
     * Get the JSON-RPC service URI.
     * @return Zend_Uri
     */
    protected abstract function _getJsonRpcUri();

    /**
     * Get the JSON service URI.
     * @return Zend_Uri
     */
    protected abstract function _getJsonUri();

    /**
     * Get the service class name.
     * @return string
     */
    protected abstract function _getServiceClass();

    /**
     * Get the service version.
     * @return string
     */
    protected abstract function _getServiceVersion();

    /**
     * Get the SOAP classmap.
     * @return array
     */
    protected function _getClassmap()
    {
        return $this->_classmap;
    }

    /**
     * Set the SOAP classmap.
     * @param array $classmap
     */
    protected function _setClassmap(array $classmap)
    {
        if ($classmap === null)
            $classmap = array();
        $this->_classmap = $classmap;
    }

    /**
     * Get the WSDL cache.
     * @return Zend_Cache_Core
     */
    protected function _getCache()
    {
        $cachemanager = $this->getInvokeArg('bootstrap')->getResource('cachemanager');
        return $cachemanager->getCache('default');
    }

    /**
     * Get the WSDL cache ID made from the URI.
     * @return string
     */
    protected function _getWsdlCacheId()
    {
        return 'wsdl_' . $this->_getServiceClass() . '_' . sha1($this->_getSoapUri()->getUri());
    }

    /**
     * Get the OpenAPI cache ID made from the URI.
     * @return string
     */
    protected function _getOpenApiCacheId()
    {
        return 'openapi_' . $this->_getServiceClass() . '_' . sha1($this->_getJsonUri()->getUri());
    }

    /**
     * Invalidate the entire content of the cache.
     */
    protected function _invalidateCache()
    {
        $this->_getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('WS'));
    }

    /**
     * Get the SOAP AutoDiscover instance.
     * @return Fab_Soap_AutoDiscover
     */
    protected function _getSoapAutoDiscover()
    {
        if (null === $this->_soapAutoDiscover) {
            $strategy = new Fab_Soap_Wsdl_Strategy_DoctrineRecord();
            $strategy = new Fab_Soap_Wsdl_Strategy_ArrayOfType($strategy);
            $this->_soapAutoDiscover = new Fab_Soap_AutoDiscover($strategy);
            $this->_soapAutoDiscover->setClassmap($this->_getClassmap());
        }
        return $this->_soapAutoDiscover;
    }

    /**
     * Get the OpenAPI AutoDiscover instance.
     * @return Fab_OpenApi_AutoDiscover
     */
    protected function _getOpenApiAutoDiscover()
    {
        if (null === $this->_openApiAutoDiscover) {
            $this->_openApiAutoDiscover = new Fab_OpenApi_AutoDiscover();
            $this->_openApiAutoDiscover->setClassmap($this->_getClassmap());
        }
        return $this->_openApiAutoDiscover;
    }

    /**
     * Get the WSDL document for the current service.
     * If possible, get it from the cache, otherwise generate it and cache it.
     */
    protected function _getWsdl()
    {
        $cache = $this->_getCache();
        if (($wsdl = $cache->load($this->_getWsdlCacheId())) === false) {
            $autoDiscover = $this->_getSoapAutoDiscover();
            $autoDiscover->setServiceName(ucfirst($this->getRequest()->getControllerName()));
            $autoDiscover->setUri($this->_getSoapUri());
            $autoDiscover->setClass($this->_getServiceClass());

            $wsdl = $autoDiscover->toXml();
            $cache->save($wsdl, $this->_getWsdlCacheId(), array('WS'));
        }
        return $wsdl;
    }

    /**
     * Get the OpenAPI document for the current service.
     * If possible, get it from the cache, otherwise generate it and cache it.
     */
    protected function _getOpenApi()
    {
        $cache = $this->_getCache();
        if (($openApi = $cache->load($this->_getOpenApiCacheId())) === false) {
            $autoDiscover = $this->_getOpenApiAutoDiscover();
            $autoDiscover->setServiceName(ucfirst($this->getRequest()->getControllerName()));
            $autoDiscover->setServiceVersion($this->_getServiceVersion());
            $autoDiscover->setServiceUrl($this->_getJsonUri());
            $autoDiscover->setServiceClass($this->_getServiceClass());

            $openApi = $autoDiscover->toJson();
            $cache->save($openApi, $this->_getOpenApiCacheId(), array('WS'));
        }
        return $openApi;
    }

    /**
     * Get the SOAP server configured to use the service class and classmap.
     * @return Fab_Soap_Server
     */
    protected function _getSoapServer()
    {
        $server = new Fab_Soap_Server($this->_getWsdlUri()->getUri());
        $server->registerFaultException('Exception');
        $server->setClass($this->_getServiceClass());
        $server->setClassmap($this->_getClassmap());
        $server->setAutoDiscover($this->_getSoapAutoDiscover());
        $server->setReturnResponse(true);
        return $server;
    }

    /**
     * Get the REST server configured to use the service class.
     * @return Zend_Rest_Server
     */
    protected function _getRestServer()
    {
        $server = new Zend_Rest_Server();
        $server->setClass($this->_getServiceClass());
        $server->returnResponse(true);
        return $server;
    }

    /**
     * Get the JSON server configured to use the service class.
     * @return Zend_Json_Server
     */
    protected function _getJsonServer()
    {
        $server = new Zend_Json_Server();
        $server->setClass($this->_getServiceClass());
        $server->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);
        $server->setTarget($this->_getJsonRpcUri());
        $server->setAutoEmitResponse(false);
        return $server;
    }

    /**
     * Get the request body, taking the Content-Type and Content-Encoding headers into account.
     * This will effectively uncompress a gzip- or deflate-encoded request.
     * @return string
     */
    protected function _getRequestBody()
    {
        $request = $this->getRequest();
        $contentType = $request->getServer('CONTENT_TYPE');
        $contentEncoding = $request->getHeader('Content-Encoding');

        if ($contentEncoding == 'gzip' || $contentType == 'application/x-gzip') {
            // GZIP-compressed request
            //$requestBody = file_get_contents('compress.zlib://php://input'); // Broken in PHP 5.6.1
            $compressedBody = file_get_contents('php://input');
            $requestBody = gzdecode($compressedBody);
        } else if ($contentEncoding == 'deflate') {
            // DEFLATE-compressed request
            $fh = fopen('php://input', 'rb');
            stream_filter_append($fh, 'zlib.inflate', STREAM_FILTER_READ, array('window' => 15));
            $requestBody = stream_get_contents($fh);
            fclose($fh);
        } else {
            // Uncompressed request
            $requestBody = file_get_contents('php://input');
        }

        return $requestBody;
    }

    /**
     * Set the response body, taking the Content-Type and Accept-Encoding headers into account.
     * This will effectively compress the response using gzip or deflate, if requested.
     * @param string $responseBody response body
     * @param int|null $responseCode HTTP response code (optional)
     */
    protected function _setResponseBody($responseBody, $responseCode = null)
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $contentType = $request->getServer('CONTENT_TYPE');
        $acceptEncoding = $request->getHeader('Accept-Encoding');
        $acceptEncodings = $acceptEncoding ? preg_split('/[\s,]+/', $acceptEncoding) : array();

        if (in_array('gzip', $acceptEncodings) || $contentType == 'application/x-gzip') {
            // GZIP-compressed response
            $responseBody = gzencode($responseBody, 9);
            if ($contentType == 'application/x-gzip') {
                $response->setHeader('Content-Type', 'application/x-gzip', true);
            } else {
                $response->setHeader('Content-Encoding', 'gzip', true);
                $response->setHeader('Vary', 'Accept-Encoding', true);
            }
        } else if (in_array('deflate', $acceptEncodings)) {
            // DEFLATE-compressed response
            $responseBody = gzcompress($responseBody, 9);
            $response->setHeader('Content-Encoding', 'deflate', true);
            $response->setHeader('Vary', 'Accept-Encoding', true);
        }

        $response->setBody($responseBody);
        $response->setHeader('Content-Length', strlen($responseBody), true);
        if (!empty($responseCode)) {
            $response->setHttpResponseCode($responseCode);
        }

        // Bypass view renderer
        $this->_helper->viewRenderer->setNoRender();
    }

}
