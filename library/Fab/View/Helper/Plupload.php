<?php

class Fab_View_Helper_Plupload extends Zend_View_Helper_Abstract
{
    /** @var array */
    protected static $_enabledView = array();
    
    /** @var string */
    protected static $_defaultFlashSwfUrl;
    
    /** @var string */
    protected static $_defaultSilverlightXapUrl;
    
    /** @var array */
    protected $_runtimes = array('html5', 'gears', 'flash', 'silverlight', 'browserplus', 'html4');
    
    /** @var string */
    protected $_browseButton = 'upload-pickfiles';
    
    /** @var string */
    protected $_container = 'upload-container';
    
    /** @var string */
    protected $_dropElement = 'upload-dropzone';
    
    /** @var string */
    protected $_fileList = 'upload-filelist';
    
    /** @var string */
    protected $_errorList = 'upload-errors';
    
    /** @var int */
    protected $_maxFileSize = 10485760; // 10 MB
    
    /** @var bool */
    protected $_multiSelection = true;
    
    /** @var string */
    protected $_flashSwfUrl;
    
    /** @var string */
    protected $_silverlightXapUrl;
    
    /** @var array */
    protected $_filters = array();
    
    /**
     * Set the View object.
     * @param Zend_View_Interface $view 
     */
    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);
        $oid = spl_object_hash($view);
        if (!isset(self::$_enabledView[$oid])) {
            $view->addBasePath(dirname(__FILE__) . '/Plupload/views');
            self::$_enabledView[$oid] = true;
        }
    }
    
    /**
     * Get options.
     * @return array
     */
    public function getOptions()
    {
        return array(
            'runtimes'          => $this->getRuntimes(),
            'browseButton'      => $this->getBrowseButton(),
            'container'         => $this->getContainer(),
            'dropElement'       => $this->getDropElement(),
            'fileList'          => $this->getFileList(),
            'errorList'         => $this->getErrorList(),
            'maxFileSize'       => $this->getMaxFileSize(),
            'multiSelection'    => $this->getMultiSelection(),
            'flashSwfUrl'       => $this->getFlashSwfUrl(),
            'silverlightXapUrl' => $this->getSilverlightXapUrl(),
            'filters'           => $this->getFilters(),
        );
    }

    /**
     * Set options.
     * @param array $options
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }
    
    /**
     * Render the Plupload uploader script.
     * @param array $options
     * @return string
     */
    public function plupload($url = null, $options = array())
    {
        // Set default URL if necessary
        if (empty($url))
            $url = $this->view->url(array('format' => 'json'));
        
        // Build runtime options
        $options = array_merge($this->getOptions(), $options);
        $options['url'] = $url;
        
        // Render the script
        return $this->view->partial('uploader.phtml', $options);
    }
    
    /**
     * Get the default Flash SWF URL to use if not explicitly set.
     * @return string|null
     */
    public static function getDefaultFlashSwfUrl()
    {
        return self::$_defaultFlashSwfUrl;
    }
    
    /**
     * Set the default Flash SWF URL to use if not explicitly set.
     * @param string|null $defaultFlashSwfUrl
     */
    public static function setDefaultFlashSwfUrl($defaultFlashSwfUrl = null)
    {
        self::$_defaultFlashSwfUrl = $defaultFlashSwfUrl;
    }
    
    /**
     * Get the default Silverlight XAP URL to use if not explicitly set.
     * @return string|null
     */
    public static function getDefaultSilverlightXapUrl()
    {
        return self::$_defaultSilverlightXapUrl;
    }
    
    /**
     * Set the default Silverlight XAP URL to use if not explicitly set.
     * @param string|null $defaultSilverlightXapUrl
     */
    public static function setDefaultSilverlightXapUrl($defaultSilverlightXapUrl = null)
    {
        self::$_defaultSilverlightXapUrl = $defaultSilverlightXapUrl;
    }
    
    /**
     * Get the runtimes to use in the order of preference.
     * @return array
     */
    public function getRuntimes()
    {
        return $this->_runtimes;
    }

    /**
     * Set the runtimes to use in the order of preference.
     * @param array $runtimes
     * @return self
     */
    public function setRuntimes($runtimes)
    {
        $this->_runtimes = $runtimes;
        return $this;
    }

    /**
     * Get the browse button element id.
     * @return string
     */
    public function getBrowseButton()
    {
        return $this->_browseButton;
    }

    /**
     * Set the browse button element id.
     * @param string $browseButton
     * @return self
     */
    public function setBrowseButton($browseButton)
    {
        $this->_browseButton = $browseButton;
        return $this;
    }

    /**
     * Get the container element id.
     * @return string
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Set the container element id.
     * @param string $container
     * @return self
     */
    public function setContainer($container)
    {
        $this->_container = $container;
        return $this;
    }

    /**
     * Get the drop zone element id.
     * @return string
     */
    public function getDropElement()
    {
        return $this->_dropElement;
    }

    /**
     * Set the drop zone element id.
     * @param string $dropElement
     * @return self
     */
    public function setDropElement($dropElement)
    {
        $this->_dropElement = $dropElement;
        return $this;
    }
    
    /**
     * Get the file list element id.
     * @return string
     */
    public function getFileList()
    {
        return $this->_fileList;
    }

    /**
     * Set the file list element id.
     * @param string $fileList
     * @return self
     */
    public function setFileList($fileList)
    {
        $this->_fileList = $fileList;
        return $this;
    }

    /**
     * Get the error list element id.
     * @return string
     */
    public function getErrorList()
    {
        return $this->_errorList;
    }

    /**
     * Set the error list element id.
     * @param string $errorList
     * @return self
     */
    public function setErrorList($errorList)
    {
        $this->_errorList = $errorList;
        return $this;
    }

    /**
     * Get the maximum allowed file size in bytes.
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->_maxFileSize;
    }

    /**
     * Set the maximum allowed file size in bytes.
     * @param int $maxFileSize
     * @return self
     */
    public function setMaxFileSize($maxFileSize)
    {
        $this->_maxFileSize = $maxFileSize;
        return $this;
    }

    /**
     * Get whether multiple files selection is allowed.
     * @return bool
     */
    public function getMultiSelection()
    {
        return $this->_multiSelection;
    }

    /**
     * Set whether multiple files selection is allowed.
     * @param bool $multiSelection
     * @return self
     */
    public function setMultiSelection($multiSelection)
    {
        $this->_multiSelection = $multiSelection;
        return $this;
    }

    /**
     * Get the Flash SWF URL.
     * @return string
     */
    public function getFlashSwfUrl()
    {
        if (!empty($this->_flashSwfUrl))
            return $this->_flashSwfUrl;
        else
            return self::$_defaultFlashSwfUrl;
    }

    /**
     * Set the Flash SWF URL.
     * @param string $flashSwfUrl
     * @return self
     */
    public function setFlashSwfUrl($flashSwfUrl)
    {
        $this->_flashSwfUrl = $flashSwfUrl;
        return $this;
    }

    /**
     * Get the Silverlight XAP URL.
     * @return string
     */
    public function getSilverlightXapUrl()
    {
        if (!empty($this->_silverlightXapUrl))
            return $this->_silverlightXapUrl;
        else
            return self::$_defaultSilverlightXapUrl;
    }

    /**
     * Set the Silverlight XAP URL.
     * @param string $silverlightXapUrl
     * @return self
     */
    public function setSilverlightXapUrl($silverlightXapUrl)
    {
        $this->_silverlightXapUrl = $silverlightXapUrl;
        return $this;
    }

    /**
     * Get the list of file filters (array of title/extensions pairs).
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * Set the list of file filters (array of title/extensions pairs).
     * @param array $filters
     * @return self
     */
    public function setFilters($filters)
    {
        $this->_filters = $filters;
        return $this;
    }
}
