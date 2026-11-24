<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Fab_ObjectStorage_Adapter_GitHub implements Fab_ObjectStorage_Adapter_Interface
{
    /** @var array */
    protected $_options = array();

    /** @var string|null */
    protected $_token;

    /** @var Zend_Log */
    protected $_log = null;

    /** @var bool */
    protected $_throwExceptions = false;

    /** @var Client */
    protected $_client;

    /**
     * GitHub object storage adapter.
     * @param array $options options to set for this adapter
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Set options for this adapter.
     * @param array $options options to set for this adapter
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
     * Set the GitHub API token to use for authenticated requests.
     * @param string|null $token GitHub personal access token, or null for unauthenticated requests
     * @return self
     */
    public function setToken($token)
    {
        if ($token !== null && !is_string($token)) {
            throw new Fab_ObjectStorage_Exception('GitHub token must be a string or null');
        }
        $this->_token = $token;
        $this->_client = null;
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
            $pathComponents = $this->_getPathComponents($path);
            $release = $this->_getRelease($pathComponents);
            $items = array();
            foreach ($release['assets'] as $asset) {
                if ($pathComponents['asset'] !== null && strpos($asset['name'], $pathComponents['asset']) !== 0) {
                    continue;
                }
                $items[] = $this->_mapObject($pathComponents, $asset);
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
            if ($pathComponents['asset'] === null) {
                return false;
            }
            return $this->_getAsset($this->_getRelease($pathComponents), $pathComponents['asset']) !== false;
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function get($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            if ($pathComponents['asset'] === null) {
                return false;
            }
            $asset = $this->_getAsset($this->_getRelease($pathComponents), $pathComponents['asset']);
            if ($asset === false) {
                return false;
            }
            return $this->_mapObject($pathComponents, $asset);
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function getContent($path)
    {
        try {
            $pathComponents = $this->_getPathComponents($path);
            if ($pathComponents['asset'] === null) {
                return false;
            }
            $asset = $this->_getAsset($this->_getRelease($pathComponents), $pathComponents['asset']);
            if ($asset === false) {
                return false;
            }
            $downloadUrlValue = isset($asset['url']) && is_string($asset['url'])
                ? $asset['url']
                : '';
            $downloadUrl = parse_url($downloadUrlValue);
            if ($downloadUrl === false || !isset($downloadUrl['scheme'], $downloadUrl['host'])
                || strtolower($downloadUrl['scheme']) !== 'https'
                || strtolower($downloadUrl['host']) !== 'api.github.com') {
                throw new Fab_ObjectStorage_Exception('Invalid asset URL from GitHub API');
            }
            $response = $this->_getClient()->request('GET', $downloadUrlValue, array(
                'stream' => true,
                'headers' => array('Accept' => 'application/octet-stream'),
            ));
            return $response->getBody();
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function put($path, $content)
    {
        return $this->_handleException(new Fab_ObjectStorage_Exception('Unsupported GitHub storage operation: put'));
    }

    public function delete($path)
    {
        return $this->_handleException(new Fab_ObjectStorage_Exception('Unsupported GitHub storage operation: delete'));
    }

    public function copy($srcPath, $dstPath)
    {
        return $this->_handleException(new Fab_ObjectStorage_Exception('Unsupported GitHub storage operation: copy'));
    }

    public function move($srcPath, $dstPath)
    {
        return $this->_handleException(new Fab_ObjectStorage_Exception('Unsupported GitHub storage operation: move'));
    }

    /**
     * Get the GitHub API client.
     * The client is lazily created on first use.
     * @return Client
     */
    protected function _getClient()
    {
        if ($this->_client === null) {
            $headers = array(
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'RPMod-Web',
            );
            if ($this->_token !== null && $this->_token !== '') {
                $headers['Authorization'] = 'Bearer ' . $this->_token;
            }
            $this->_client = new Client(array(
                'base_uri' => 'https://api.github.com/',
                'headers' => $headers,
            ));
        }
        return $this->_client;
    }

    /**
     * Parse a path relative to https://github.com, optionally followed by an asset name.
     * Supported paths are {owner}/{repo}/releases/latest and {owner}/{repo}/releases/tag/{tag}.
     * @return array parsed repository, release, and optional asset
     */
    protected function _getPathComponents($path)
    {
        if (!is_string($path) || empty($path)) {
            throw new Fab_ObjectStorage_Exception('Invalid GitHub path (empty): ' . $path);
        }

        $segments = explode('/', trim($path, '/'));
        if (count($segments) < 4) {
            throw new Fab_ObjectStorage_Exception('Invalid GitHub path (wrong format): ' . $path);
        }

        foreach ($segments as $segment) {
            if (empty($segment)) {
                throw new Fab_ObjectStorage_Exception('Invalid GitHub path (empty segment): ' . $path);
            }
        }

        $owner = rawurldecode($segments[0]);
        $repo = rawurldecode($segments[1]);
        if (empty($owner) || empty($repo)) {
            throw new Fab_ObjectStorage_Exception('Invalid GitHub path (empty owner or repo): ' . $path);
        }

        if ($segments[2] !== 'releases') {
            throw new Fab_ObjectStorage_Exception('Invalid GitHub path (missing releases segment): ' . $path);
        } elseif ($segments[3] === 'latest') {
            $release = array('type' => 'latest');
            $assetSegments = array_slice($segments, 4);
        } elseif ($segments[3] === 'tag' && isset($segments[4]) && rawurldecode($segments[4]) !== '') {
            $release = array('type' => 'tag', 'tag' => rawurldecode($segments[4]));
            $assetSegments = array_slice($segments, 5);
        } else {
            throw new Fab_ObjectStorage_Exception('Invalid GitHub path (invalid release): ' . $path);
        }

        return array(
            'owner' => $owner,
            'repo' => $repo,
            'release' => $release,
            'asset' => empty($assetSegments) ? null : rawurldecode(implode('/', $assetSegments)),
        );
    }

    /**
     * Fetch release metadata from the GitHub API.
     * @return array release data
     */
    protected function _getRelease(array $pathComponents)
    {
        $endpoint = 'repos/' . rawurlencode($pathComponents['owner']) . '/' . rawurlencode($pathComponents['repo']) . '/releases/';
        if ($pathComponents['release']['type'] === 'latest') {
            $endpoint .= 'latest';
        } else {
            $endpoint .= 'tags/' . rawurlencode($pathComponents['release']['tag']);
        }

        $response = $this->_getClient()->request('GET', $endpoint);
        $release = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($release) || !isset($release['assets']) || !is_array($release['assets'])) {
            throw new Fab_ObjectStorage_Exception('Invalid response from GitHub API');
        }
        foreach ($release['assets'] as $asset) {
            if (!is_array($asset) || !isset($asset['name'], $asset['size'])
                || !is_string($asset['name']) || !is_numeric($asset['size'])) {
                throw new Fab_ObjectStorage_Exception('Invalid response from GitHub API');
            }
        }
        return $release;
    }

    /**
     * Find an asset by its exact name.
     * @return array|bool asset data or false if it does not exist
     */
    protected function _getAsset(array $release, $name)
    {
        foreach ($release['assets'] as $asset) {
            if (isset($asset['name']) && $asset['name'] === $name) {
                return $asset;
            }
        }
        return false;
    }

    /**
     * Map a GitHub release asset to a Fab_ObjectStorage_Object.
     * @return Fab_ObjectStorage_Object mapped object
     */
    protected function _mapObject(array $pathComponents, array $asset)
    {
        $item = new Fab_ObjectStorage_Object();
        $item->pathname = $this->_getReleaseUrl($pathComponents) . '/' . rawurlencode($asset['name']);
        $item->path = $this->_getReleaseUrl($pathComponents);
        $item->name = $asset['name'];
        $item->size = intval($asset['size']);
        if (!empty($asset['updated_at'])) {
            $lastModified = strtotime($asset['updated_at']);
        } elseif (!empty($asset['created_at'])) {
            $lastModified = strtotime($asset['created_at']);
        }
        if (isset($lastModified) && $lastModified !== false) {
            $item->lastModified = $lastModified;
        }
        return $item;
    }

    /**
     * Rebuild the canonical release path relative to https://github.com.
     * @return string canonical release path
     */
    protected function _getReleaseUrl(array $pathComponents)
    {
        $url = rawurlencode($pathComponents['owner']) . '/' . rawurlencode($pathComponents['repo']) . '/releases/';
        if ($pathComponents['release']['type'] === 'latest') {
            return $url . 'latest';
        }
        return $url . 'tag/' . rawurlencode($pathComponents['release']['tag']);
    }

    /**
     * Handle an exception by logging it and rethrowing it, or returning false.
     * @param Exception $exception exception to handle
     * @return bool false if not configured to throw exceptions
     */
    protected function _handleException(Exception $e)
    {
        if ($e instanceof RequestException && $e->getResponse() !== null && 404 === $e->getResponse()->getStatusCode())
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
