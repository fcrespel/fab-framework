<?php

abstract class Fab_Controller_WebService_Abstract extends Zend_Controller_Action
{
    /** @var Zend_Soap_AutoDiscover */
    protected $_autoDiscover;

    /** @var array */
    protected $_classmap = array();


    /**
     * Get the SOAP service URI.
     * @return Zend_Uri
     */
    protected abstract function _getSoapUri();

    /**
     * Get the WSDL document URI.
     * @return Zend_Uri
     */
    protected abstract function _getWsdlUri();

    /**
     * Get the service class name.
     * @return string
     */
    protected abstract function _getServiceClass();

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
    protected function _getCacheId()
    {
        return 'wsdl_' . $this->_getServiceClass() . '_' . sha1($this->_getSoapUri()->getUri());
    }

    /**
     * Invalidate the entire content of the cache.
     */
    protected function _invalidateCache()
    {
        $this->_getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('wsdl'));
    }

    /**
     * Get the WSDL AutoDiscover instance.
     * @return Fab_Soap_AutoDiscover
     */
    protected function _getAutoDiscover()
    {
        if (null === $this->_autoDiscover) {
            $strategy = new Fab_Soap_Wsdl_Strategy_DoctrineRecord();
            $strategy = new Fab_Soap_Wsdl_Strategy_ArrayOfType($strategy);
            $this->_autoDiscover = new Fab_Soap_AutoDiscover($strategy);
            $this->_autoDiscover->setClassmap($this->_getClassmap());
        }
        return $this->_autoDiscover;
    }

    /**
     * Get the WSDL document for the current service.
     * If possible, get it from the cache, otherwise generate it and cache it.
     */
    protected function _getWsdl()
    {
        $cache = $this->_getCache();
        if (($wsdl = $cache->load($this->_getCacheId())) === false) {
            $autoDiscover = $this->_getAutoDiscover();
            $autoDiscover->setUri($this->_getSoapUri());
            $autoDiscover->setClass($this->_getServiceClass());

            $wsdl = $autoDiscover->toXml();
            $cache->save($wsdl, $this->_getCacheId(), array('wsdl'));
        }
        return $wsdl;
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
        $server->setAutoDiscover($this->_getAutoDiscover());
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
        return $server;
    }

}
