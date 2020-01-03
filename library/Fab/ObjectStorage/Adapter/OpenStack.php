<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use OpenStack\OpenStack;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Common\Transport\Utils;
use OpenStack\Identity\v2\Service;

class Fab_ObjectStorage_Adapter_OpenStack implements Fab_ObjectStorage_Adapter_Interface
{
    /** @var array */
    protected $_options = array();

    /** @var Zend_Cache_Core */
    protected $_cache = null;

    /** @var Zend_Log */
    protected $_log = null;

    /** @var bool */
    protected $_throwExceptions = false;

    /** @var OpenStack */
    protected $_openstack;

    /**
     * OpenStack object storage (Swift) adapter.
     * @param array $options options to set for this adapter and the OpenStack client
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Set options for this adapter and the OpenStack client.
     * @param array $options options to set for this adapter and the OpenStack client
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
                unset($options[$key]);
            }
        }
        $this->_options = $options;
        return $this;
    }

    /**
     * Set the token cache to use.
     * @param Zend_Cache_Core $cache cache to use
     * @return self
     */
    public function setCache($cache)
    {
        $this->_cache = $cache;
        return $this;
    }

    /**
     * Set the logger to use.
     * @param Zend_Log $log logger to use
     * @return self
     */
    public function setLog($log)
    {
        $this->_log = $log;
        return $this;
    }

    /**
     * Set whether to throw exceptions on errors, or return false instead.
     * @param bool $throwExceptions whether to throw exceptions on errors
     * @return self
     */
    public function setThrowException($throwExceptions = true)
    {
        $this->_throwExceptions = $throwExceptions;
        return $this;
    }

    public function list($path)
    {
        try {
            $items = array();
            $pathComponents = $this->_getPathComponents($path);
            $objects = $this->_getOpenStack()->objectStoreV1()->getContainer($pathComponents[0])->listObjects(array('prefix' => count($pathComponents) > 1 ? $pathComponents[1] : ''));
            foreach ($objects as $object) {
                $items[] = $this->_mapObject($object);
            }
            return $items;
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function exists($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            return $this->_getOpenStack()->objectStoreV1()->getContainer($pathComponents[0])->objectExists($pathComponents[1]);
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function get($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $object = $this->_getOpenStack()->objectStoreV1()->getContainer($pathComponents[0])->getObject($pathComponents[1]);
            $object->retrieve();
            return $this->_mapObject($object);
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function getContent($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $object = $this->_getOpenStack()->objectStoreV1()->getContainer($pathComponents[0])->getObject($pathComponents[1]);
            return $object->download();
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function put($path, $content)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $options = array(
                'name' => $pathComponents[1],
                'stream' => GuzzleHttp\Psr7\stream_for($content),
            );
            $object = $this->_getOpenStack()->objectStoreV1()->getContainer($pathComponents[0])->createObject($options);
            return $this->_mapObject($object);
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function delete($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $object = $this->_getOpenStack()->objectStoreV1()->getContainer($pathComponents[0])->getObject($pathComponents[1]);
            $object->delete();
            return true;
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function copy($srcPath, $dstPath)
    {
        try {
            $pathComponents = $this->_getPathComponents($srcPath);
            $object = $this->_getOpenStack()->objectStoreV1()->getContainer($pathComponents[0])->getObject($pathComponents[1]);
            $object->copy(array('destination' => '/' . trim($dstPath, '/')));
            return true;
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function move($srcPath, $dstPath)
    {
        if ($this->copy($srcPath, $dstPath)) {
            return $this->delete($srcPath);
        } else {
            return false;
        }
    }

    /**
     * Get the OpenStack client instance.
     * The instance will be lazily created on first call with current options,
     * any further change to options will have no effect.
     * If a cache is configured, it will be used to save/load the authentication token.
     * @return OpenStack OpenStack client instance
     */
    protected function _getOpenStack()
    {
        if ($this->_openstack === null) {
            $options = $this->_options;

            // Handle identity service version
            if (isset($options['authUrl'])) {
                if (strpos($options['authUrl'], 'v2') !== false) {
                    $httpClient = new Client([
                        'base_uri' => Utils::normalizeUrl($options['authUrl']),
                        'handler'  => HandlerStack::create(),
                    ]);
                    $options['identityService'] = Service::factory($httpClient);
                } else if (strpos($options['authUrl'], 'v3') !== false) {
                    if (!isset($options['user']) && isset($options['username']) && isset($options['password'])) {
                        $options['user'] = array(
                            'name' => $options['username'],
                            'password' => $options['password'],
                            'domain' => array('name' => 'Default'),
                        );
                        unset($options['username'], $options['password']);
                    }
                    if (!isset($options['scope']) && isset($options['tenantName'])) {
                        $options['scope'] = array(
                            'project' => array(
                                'name' => $options['tenantName'],
                                'domain' => array('name' => 'Default'),
                            ),
                        );
                    }
                }
            }

            // Check cached token
            if ($this->_cache !== null && !isset($options['cachedToken'])) {
                $cacheId = $this->_getCacheId($options);
                $cachedToken = $this->_cache->load($cacheId);
                if ($cachedToken === false) {
                    $openstack = new OpenStack($options);
                    $newToken = $openstack->identityV3()->generateToken($options);
                    $cachedToken = $newToken->export();
                    $lifetime = $newToken->expires->format('U') - time() - 60; // add 60 seconds error margin
                    if ($lifetime > 0) {
                        $this->_cache->save($cachedToken, $cacheId, array(), $lifetime);
                    }
                }
                $options['cachedToken'] = $cachedToken;
            }

            // Create OpenStack client
            $this->_openstack = new OpenStack($options);
        }
        return $this->_openstack;
    }

    /**
     * Get the unique cache ID for the given options.
     * @return string cache ID
     */
    protected function _getCacheId(array $options)
    {
        return 'OpenStack_' . sha1(serialize($options));
    }

    /**
     * Split the given path in two components: bucket and prefix
     * @return array bucket and prefix
     */
    protected function _getPathComponents($path)
    {
        return explode('/', trim($path, '/'), 2);
    }

    /**
     * Map an OpenStack object to a Fab_ObjectStorage_Object.
     * @return Fab_ObjectStorage_Object mapped object
     */
    protected function _mapObject($object)
    {
        $item = new Fab_ObjectStorage_Object();
        $item->pathname = $object->containerName . '/' . $object->name;
        $item->path = dirname($object->name);
        if ($item->path != '.') {
            $item->path = $object->containerName . '/' . $item->path;
        } else {
            $item->path = $object->containerName;
        }
        $item->name = basename($object->name);
        $item->size = intval($object->contentLength);
        if (!empty($object->lastModified)) {
            $add = 0;
            if ($object->lastModified instanceof DateTimeImmutable) {
                if ($object->lastModified->getOffset() != 0) {
                    // Fix for GMT offset and microseconds rounding
                    $add = intval(round($object->lastModified->format('0.u')));
                    $object->lastModified = new DateTime($object->lastModified->format('Y-m-d\TH:i:s.u'), new DateTimeZone('GMT'));
                }
            } else {
                $object->lastModified = DateTime::createFromFormat(DateTime::RFC2822, $object->lastModified);
            }
            $item->lastModified = $object->lastModified->getTimestamp();
            $item->lastModified += $add;
        }
        return $item;
    }

    /**
     * Handle an exception by logging it and rethrowing it, or returning false.
     * @param Exception $exception exception to handle
     * @return bool false if not configured to throw exceptions
     */
    protected function _handleException(Exception $e)
    {
        if ($e instanceof BadResponseError && 404 === $e->getResponse()->getStatusCode())
            return false;

        if ($this->_log !== null)
            $this->_log->err($e);

        if ($this->_throwExceptions) {
            throw $e;
        } else {
            return false;
        }
    }

}
