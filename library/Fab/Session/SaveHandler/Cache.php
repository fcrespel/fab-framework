<?php

class Fab_Session_SaveHandler_Cache implements Zend_Session_SaveHandler_Interface
{
    const CACHE_NAME = "cacheName";

    /** @var string */
    protected $_cacheName;

    /** @var Zend_Cache */
    protected $_cache;

    /**
     * Constructor
     * 
     * @param array|Zend_Config $config Configuration array or Zend_Config object containing the cache backend name.
     */
    public function __construct($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } else if (!is_array($config)) {
            throw new Zend_Session_SaveHandler_Exception('$config must be an instance of Zend_Config or array of key/value pairs.');
        }

        if (!isset($config[self::CACHE_NAME])) {
            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::CACHE_NAME . "' which names the "
              . "cache instance.");
        }
        $this->setCacheName($config[self::CACHE_NAME]);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        Zend_Session::writeClose();
    }

    /**
     * Set the cache name
     *
     * @param string $cacheName The name of the cache instance.
     */
    public function setCacheName($cacheName)
    {
        if (empty($cacheName)) {
            throw new Zend_Session_SaveHandler_Exception('Cache name cannot be empty.');
        }
        $this->_cacheName = $cacheName;
    }

    /**
     * Get the cache name
     *
     * @return string The name of the cache instance.
     */
    public function getCacheName()
    {
        return $this->_cacheName;
    }

    /**
     * Open session
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
        return (string) $this->_getCache()->load($id);
    }

    /**
     * Write session data
     *
     * @param string $id
     * @param mixed $data
     * @return boolean
     */
    public function write($id, $data)
    {
        return $this->_getCache()->save($data, $id);
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {
        return $this->_getCache()->remove($id);
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return boolean
     */
    public function gc($maxlifetime)
    {
        return $this->_getCache()->clean(Zend_Cache::CLEANING_MODE_OLD);
    }

    /**
     * Get the cache instance from the CacheManager bootstrap resource
     *
     * @return Zend_Cache_Core The cache instance
     */
    protected function _getCache()
    {
        if ($this->_cache === null) {
            $bootstrap = Zend_Registry::get('bootstrap');
            $cacheManager = $bootstrap->getResource('cacheManager');
            $this->_cache = $cacheManager->getCache($this->getCacheName());
        }
        return $this->_cache;
    }
}
