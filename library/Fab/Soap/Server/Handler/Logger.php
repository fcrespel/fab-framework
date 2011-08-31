<?php

class Fab_Soap_Server_Handler_Logger implements Fab_Soap_Server_Handler
{
    /** @var Zend_Log */
    protected $_log = null;

    /**
     * Logging handler, to record pre- and post-invoke events.
     * @param <type> $log
     */
    public function __construct($log)
    {
        $this->setLog($log);
    }

    /**
     * Get the Zend_Log instance to use.
     * @return Zend_Log|null
     */
    public function getLog()
    {
        return $this->_log;
    }

    /**
     * Set the Zend_Log instance to use.
     * @param Zend_Log|null $log
     * @return self
     */
    public function setLog($log)
    {
        $this->_log = $log;
        return $this;
    }

    public function preInvoke(Fab_Soap_Server_MessageContext $context)
    {
        $log = $this->getLog();
        if (isset($log))
            $log->info('SOAP Handler preInvoke: ' . $context->getMethodName() . '(' . serialize($context->getMethodArgs()) . ')');
    }

    public function postInvoke(Fab_Soap_Server_MessageContext $context)
    {
        $log = $this->getLog();
        if (isset($log))
            $log->info('SOAP Handler postInvoke: ' . $context->getMethodName() . ' => ' . serialize($context->getMethodReturn()));
    }
}
