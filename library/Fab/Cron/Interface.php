<?php

interface Fab_Cron_Interface
{
    /**
     * Construct a new cron task. 
     */
    public function __construct($args = null);

    /**
     * Lock this task.
     * @return integer pid of this process
     * @throws Fab_Cron_Exception if already locked
     */
    public function lock();

    /**
     * Unlock this task.
     * @return boolean true if successful
     * @throws Fab_Cron_Exception if an error occurs
     */
    public function unlock();

    /**
     * Check if this task is locked.
     * @return boolean true if the task is already locked, false otherwise
     */
    public function isLocked();

    /**
     * Run the cron task.
     * @return void
     * @throws Fab_Cron_Exception to describe any errors that should be returned to the user
     */
    public function run();
    
    /**
     * Set the logger to use during execution.
     * @param Zend_Log $log logger instance
     * @return void
     */
    public function setLog(Zend_Log $log);
}
