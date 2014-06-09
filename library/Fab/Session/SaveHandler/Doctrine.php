<?php

class Fab_Session_SaveHandler_Doctrine implements Zend_Session_SaveHandler_Interface
{
    const MODEL_NAME        = 'modelName';
    const ID_COLUMN         = 'idColumn';
    const MODIFIED_COLUMN   = 'modifiedColumn';
    const LIFETIME_COLUMN   = 'lifetimeColumn';
    const DATA_COLUMN       = 'dataColumn';
    const LIFETIME          = 'lifetime';
    const OVERRIDE_LIFETIME = 'overrideLifetime';

    /**
     * Doctrine model name
     *
     * @var string
     */
    protected $_modelName;

    /**
     * Session table id column
     *
     * @var string
     */
    protected $_idColumn = 'id';

    /**
     * Session table last modification time column
     *
     * @var string
     */
    protected $_modifiedColumn = 'modified';

    /**
     * Session table lifetime column
     *
     * @var string
     */
    protected $_lifetimeColumn = 'lifetime';

    /**
     * Session table data column
     *
     * @var string
     */
    protected $_dataColumn = 'data';

    /**
     * Session lifetime
     *
     * @var int
     */
    protected $_lifetime = false;

    /**
     * Whether or not the lifetime of an existing session should be overridden
     *
     * @var boolean
     */
    protected $_overrideLifetime = false;

    /**
     * Constructor
     *
     * $config is an instance of Zend_Config or an array of key/value pairs containing configuration options:
     *
     * modelName         => (string) Doctrine model name
     *
     * idColumn          => (string) Session table id column
     *
     * modifiedColumn    => (string) Session table last modification time column
     *
     * lifetimeColumn    => (string) Session table lifetime column
     *
     * dataColumn        => (string) Session table data column
     *
     * lifetime          => (integer) Session lifetime (optional; default: ini_get('session.gc_maxlifetime'))
     *
     * overrideLifetime  => (boolean) Whether or not the lifetime of an existing session should be overridden
     *      (optional; default: false)
     *
     * @param  Zend_Config|array $config      User-provided configuration
     * @return void
     * @throws Zend_Session_SaveHandler_Exception
     */
    public function __construct($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } else if (!is_array($config)) {
            throw new Zend_Session_SaveHandler_Exception('$config must be an instance of Zend_Config or array of key/value pairs.');
        }

        foreach ($config as $key => $value) {
            switch ($key) {
                case self::MODEL_NAME:
                    $this->_modelName = (string) $value;
                    break;
                case self::ID_COLUMN:
                    $this->_idColumn = (string) $value;
                    break;
                case self::MODIFIED_COLUMN:
                    $this->_modifiedColumn = (string) $value;
                    break;
                case self::LIFETIME_COLUMN:
                    $this->_lifetimeColumn = (string) $value;
                    break;
                case self::DATA_COLUMN:
                    $this->_dataColumn = (string) $value;
                    break;
                case self::LIFETIME:
                    $this->setLifetime($value);
                    break;
                case self::OVERRIDE_LIFETIME:
                    $this->setOverrideLifetime($value);
                    break;
            }
        }
        
        $this->_checkRequiredOptions();
        $this->setLifeTime($this->_lifetime);
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        Zend_Session::writeClose();
    }

    /**
     * Set session lifetime and optional whether or not the lifetime of an existing session should be overridden
     *
     * $lifetime === false resets lifetime to session.gc_maxlifetime
     *
     * @param int $lifetime
     * @param boolean $overrideLifetime (optional)
     * @return Fab_Session_SaveHandler_Doctrine
     */
    public function setLifetime($lifetime, $overrideLifetime = null)
    {
        if ($lifetime < 0) {
            throw new Zend_Session_SaveHandler_Exception();
        } else if (empty($lifetime)) {
            $this->_lifetime = (int) ini_get('session.gc_maxlifetime');
        } else {
            $this->_lifetime = (int) $lifetime;
        }

        if ($overrideLifetime != null) {
            $this->setOverrideLifetime($overrideLifetime);
        }

        return $this;
    }

    /**
     * Retrieve session lifetime
     *
     * @return int
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }

    /**
     * Set whether or not the lifetime of an existing session should be overridden
     *
     * @param boolean $overrideLifetime
     * @return Fab_Session_SaveHandler_Doctrine
     */
    public function setOverrideLifetime($overrideLifetime)
    {
        $this->_overrideLifetime = (boolean) $overrideLifetime;

        return $this;
    }

    /**
     * Retrieve whether or not the lifetime of an existing session should be overridden
     *
     * @return boolean
     */
    public function getOverrideLifetime()
    {
        return $this->_overrideLifetime;
    }

    /**
     * Open Session
     *
     * @param string $save_path
     * @param string $name
     * @return boolean
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Close session
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        $return = '';

        $record = $this->_getRecord($id);

        if ($record) {
            if ($this->_getExpirationTime($record) > time()) {
                $return = $record->{$this->_dataColumn};
            } else {
                $this->destroy($id);
            }
        }

        return $return;
    }

    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        $return = false;

        $record = $this->_getRecord($id);

        if ($record) {
            $record->{$this->_lifetimeColumn} = $this->_getLifetime($record);
        } else {
            $record = new $this->_modelName();
            $record->{$this->_idColumn} = $id;
            $record->{$this->_lifetimeColumn} = $this->_lifetime;
        }

        $record->{$this->_modifiedColumn} = time();
        $record->{$this->_dataColumn} = (string) $data;
        try {
            $record->save();
            $return = true;
        } catch (Exception $e) {
            // Ignored
        }

        return $return;
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {
        $return = false;

        $record = $this->_getRecord($id);

        if ($record) {
            try {
                $record->delete();
                $return = true;
            } catch (Exception $e) {
                // Ignored
            }
        }

        return $return;
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {
        Doctrine_Query::create()
                        ->delete()
                        ->from($this->_modelName)
                        ->where($this->_modifiedColumn . ' + ' . $this->_lifetimeColumn . ' < ?', time())
                        ->execute();

        return true;
    }

    /**
     * Check for required options
     *
     * @return void
     * @throws Zend_Session_SaveHandler_Exception
     */
    protected function _checkRequiredOptions()
    {
        if ($this->_modelName === null) {
            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::MODEL_NAME . "' which names the "
              . "Doctrine session model name.");
        } else if ($this->_idColumn === null) {
            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::ID_COLUMN . "' which names the "
              . "session table id column.");
        } else if ($this->_modifiedColumn === null) {
            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::MODIFIED_COLUMN . "' which names the "
              . "session table last modification time column.");
        } else if ($this->_lifetimeColumn === null) {
            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::LIFETIME_COLUMN . "' which names the "
              . "session table lifetime column.");
        } else if ($this->_dataColumn === null) {
            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::DATA_COLUMN . "' which names the "
              . "session table data column.");
        }
    }
    
    /**
     * Retrieve the session record in database
     * 
     * @param string $id
     * @return Doctrine_Record
     */
    protected function _getRecord($id)
    {
        return Doctrine::getTable($this->_modelName)->findOneBy($this->_idColumn, $id);
    }

    /**
     * Retrieve session lifetime considering Fab_Session_SaveHandler_Doctrine::OVERRIDE_LIFETIME
     *
     * @param Doctrine_Record $record
     * @return int
     */
    protected function _getLifetime(Doctrine_Record $record)
    {
        $return = $this->_lifetime;

        if (!$this->_overrideLifetime) {
            $return = (int) $record->{$this->_lifetimeColumn};
        }

        return $return;
    }

    /**
     * Retrieve session expiration time
     *
     * @param Doctrine_Record $record
     * @return int
     */
    protected function _getExpirationTime(Doctrine_Record $record)
    {
        return (int) $record->{$this->_modifiedColumn} + $this->_getLifetime($record);
    }
}
