<?php

abstract class Fab_Cron_Abstract implements Fab_Cron_Interface
{
    /** @var Zend_Log */
    protected $_log = null;
    
    /**
     * Lock this task.
     * @return integer pid of this process
     * @throws Fab_Cron_Exception if already locked
     */
    public function lock()
    {   
        if ($this->isLocked()) {
            throw new Fab_Cron_Exception('This task is already locked.');
        }

        $pid = getmypid();
        if (!file_put_contents($this->_getLockFile(), $pid)) {
            throw new Fab_Cron_Exception('A lock could not be obtained.');
        }

        return $pid;
    }

    /**
     * Unlock this task.
     * @return boolean true if successful
     * @throws Fab_Cron_Exception if an error occurs
     */
    public function unlock()
    {
        if (!file_exists($this->_getLockFile())) {
            throw new Fab_Cron_Exception('This task is not locked.');
        }

        if (!unlink($this->_getLockFile())) {
            throw new Fab_Cron_Exception('The lock could not be deleted.');
        }

        return true;
    }

    /**
     * Check if this task is locked.
     * @return boolean true if the task is already locked, false otherwise
     */
    public function isLocked()
    {
        $locked = file_exists($this->_getLockFile());
        if ($locked && function_exists('posix_getpgid')) {
            $pid = file_get_contents($this->_getLockFile());
            $locked = posix_getpgid(intval($pid)) !== false;
        }
        return $locked;
    }

    /**
     * Get the lock file name.
     * @return string 
     */
    protected function _getLockFile()
    {
        $fileName = 'cron.' . get_class($this) . '.lock';
        $lockFile = realpath(APPLICATION_PATH . '/../data/tmp/') . '/' . $fileName;
        return $lockFile;
    }
    
    /**
     * Get the logger to use during execution.
     * @return Zend_Log
     */
    public function getLog()
    {
        if (null === $this->_log) {
            $log = new Zend_Log();
            $log->addWriter(new Zend_Log_Writer_Stream('php://stdout'));
            $this->_log = $log;
        }
        return $this->_log;
    }
    
    /**
     * Set the logger to use during execution.
     * @param Zend_Log $log logger instance
     * @return void
     */
    public function setLog(Zend_Log $log)
    {
        $this->_log = $log;
    }
}
