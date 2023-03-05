<?php

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class Fab_ObjectStorage_Adapter_S3 implements Fab_ObjectStorage_Adapter_Interface
{
    /** @var array */
    protected $_options = array();

    /** @var string */
    protected $_acl = 'private';

    /** @var Zend_Log */
    protected $_log = null;

    /** @var bool */
    protected $_throwExceptions = false;

    /** @var S3Client */
    protected $_s3client;

    /**
     * S3 object storage adapter.
     * @param array $options options to set for this adapter and the S3 client
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Set options for this adapter and the S3 client.
     * @param array $options options to set for this adapter and the S3 client
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
     * Set the ACL to use for put or copy operations.
     * @param string $acl ACL to use (private, public-read, etc.)
     * @return self
     */
    public function setAcl($acl)
    {
        $this->_acl = $acl;
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
            $continuationToken = null;
            do {
                $result = $this->_getS3Client()->listObjectsV2(array(
                    'Bucket' => $pathComponents[0],
                    'Prefix' => count($pathComponents) > 1 ? $pathComponents[1] : '',
                    'ContinuationToken' => $continuationToken,
                ));
                if ($result->hasKey('Contents')) {
                    foreach ($result['Contents'] as $object) {
                        $items[] = $this->_mapObject($pathComponents[0], $object);
                    }
                }
                $continuationToken = $result['NextContinuationToken'];
            } while ($continuationToken !== null);
            return $items;
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function exists($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            return $this->_getS3Client()->doesObjectExistV2($pathComponents[0], $pathComponents[1]);
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function get($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $object = $this->_getS3Client()->headObject(array(
                'Bucket' => $pathComponents[0],
                'Key' => $pathComponents[1],
            ));
            $object['Key'] = $pathComponents[1];
            return $this->_mapObject($pathComponents[0], $object);
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function getContent($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $object = $this->_getS3Client()->getObject(array(
                'Bucket' => $pathComponents[0],
                'Key' => $pathComponents[1],
            ));
            return $object['Body'];
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function put($path, $content)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $object = $this->_getS3Client()->putObject(array(
                'Bucket' => $pathComponents[0],
                'Key' => $pathComponents[1],
                'Body' => GuzzleHttp\Psr7\Utils::streamFor($content),
                'ACL' => $this->_acl,
            ));
            return true;
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function delete($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            $this->_getS3Client()->deleteObject(array(
                'Bucket' => $pathComponents[0],
                'Key' => $pathComponents[1],
            ));
            return true;
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function copy($srcPath, $dstPath)
    {
        try {
            $pathComponents = $this->_getPathComponents($dstPath);
            $this->_getS3Client()->copyObject(array(
                'Bucket' => $pathComponents[0],
                'Key' => $pathComponents[1],
                'CopySource' => $srcPath,
                'ACL' => $this->_acl,
            ));
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
     * Get the S3 client instance.
     * The instance will be lazily created on first call with current options,
     * any further change to options will have no effect.
     * @return S3Client S3 client instance
     */
    protected function _getS3Client()
    {
        if ($this->_s3client === null) {
            $this->_s3client = new S3Client($this->_options);
        }
        return $this->_s3client;
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
     * Map an S3 object to a Fab_ObjectStorage_Object.
     * @param string $bucket bucket name
     * @param mixed $object S3 object
     * @return Fab_ObjectStorage_Object mapped object
     */
    protected function _mapObject($bucket, $object)
    {
        $item = new Fab_ObjectStorage_Object();
        $item->pathname = $bucket . '/' . $object['Key'];
        $item->path = dirname($object['Key']);
        if ($item->path != '.') {
            $item->path = $bucket . '/' . $item->path;
        } else {
            $item->path = $bucket;
        }
        $item->name = basename($object['Key']);
        $item->size = isset($object['ContentLength']) ? intval($object['ContentLength']) : intval($object['Size']);
        if (isset($object['LastModified'])) {
            $item->lastModified = $object['LastModified']->getTimestamp();
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
        if ($e instanceof AwsException && 404 === $e->getStatusCode())
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
