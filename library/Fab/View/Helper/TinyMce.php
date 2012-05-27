<?php

class Fab_View_Helper_TinyMce extends Zend_View_Helper_Abstract
{
    /** @var bool */
    protected $_enabled = false;
    
    /** @var string */
    protected $_defaultScript = 'http://www.tinymce.com/js/tinymce/jscripts/tiny_mce/tiny_mce.js';

    /** @var array */
    protected $_supported = array(
        'mode'      => array('textareas', 'specific_textareas', 'exact', 'none'),
        'theme'     => array('simple', 'advanced'),
        'format'    => array('html', 'xhtml'),
        'languages' => array('en'),
        'plugins'   => array('style', 'layer', 'table', 'save',
                             'advhr', 'advimage', 'advlink', 'emotions',
                             'iespell', 'insertdatetime', 'preview', 'media',
                             'searchreplace', 'print', 'contextmenu', 'paste',
                             'directionality', 'fullscreen', 'noneditable', 'visualchars',
                             'nonbreaking', 'xhtmlxtras', 'imagemanager', 'filemanager','template'));

    /** @var array */
    protected $_config = array('mode'  =>'textareas',
                               'theme' => 'simple',
                               'element_format' => 'html');
    
    /** @var string */
    protected $_scriptPath;
    
    /** @var string */
    protected $_scriptFile;
    
    /** @var bool */
    protected $_useCompressor = false;

    /**
     * Magic setter.
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $method = 'set' . $name;
        if (!method_exists($this, $method)) {
            throw new Fab_View_Exception('Invalid tinyMce property');
        }
        $this->$method($value);
    }

    /**
     * Magic getter.
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get' . $name;
        if (!method_exists($this, $method)) {
            throw new Fab_View_Exception('Invalid tinyMce property');
        }
        return $this->$method();
    }

    /**
     * Set options.
     * @param array $options
     * @return self
     */
    public function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            } else {
                $this->_config[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Direct helper call.
     * @return self
     */
    public function tinyMce()
    {
        return $this;
    }

    /**
     * Set the TinyMCE script path.
     * @param string $path
     * @return self
     */
    public function setScriptPath($path)
    {
        $this->_scriptPath = rtrim($path,'/');
        return $this;
    }

    /**
     * Set the TinyMCE script file name.
     * @param string $file 
     * @return self
     */
    public function setScriptFile($file)
    {
        $this->_scriptFile = (string) $file;
        return $this;
    }

    /**
     * Set whether the compressor should be used.
     * @param bool $switch
     * @return self
     */
    public function setCompressor($switch)
    {
        $this->_useCompressor = (bool) $switch;
        return $this;
    }

    /**
     * Render this TinyMCE view helper (only once).
     */
    public function render()
    {
        if (false === $this->_enabled) {
            $this->_renderScript();
            $this->_renderCompressor();
            $this->_renderEditor();
        }
        $this->_enabled = true;
    }

    /**
     * Render the TinyMCE script.
     * @return self
     */
    protected function _renderScript()
    {
        if(null === $this->_scriptFile) {
            $script = $this->_defaultScript;
        } else {
            $script = $this->_scriptPath . '/' . $this->_scriptFile;
        }

        $this->view->headScript()->appendFile($script);
        return $this;
    }

    /**
     * Render the compressor script.
     * @return self
     */
    protected function _renderCompressor()
    {
        if (false === $this->_useCompressor) {
            return;
        }

        if (isset($this->_config['plugins']) && is_array($this->_config['plugins'])) {
            $plugins = $this->_config['plugins'];
        } else {
            $plugins = $this->_supported['plugins'];
        }
        
        $script = 'tinyMCE_GZ.init({' . PHP_EOL
                . 'themes: "' . implode(',', $this->_supported['theme']) . '",' . PHP_EOL
                . 'plugins: "'. implode(',', $plugins) . '",' . PHP_EOL
                . 'languages: "' . implode(',', $this->_supported['languages']) . '",' . PHP_EOL
                . 'disk_cache: true,' . PHP_EOL
                . 'debug: false' . PHP_EOL
                . '});';

        $this->view->headScript()->appendScript($script);
        return $this;
    }

    /**
     * Render the editor script.
     * @return self
     */
    protected function _renderEditor()
    {
        $script = 'tinyMCE.init({' . PHP_EOL;

        $params = array();
        foreach ($this->_config as $name => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } else {
                $value = '"' . $value . '"';
            }
            $params[] = $name . ': ' . $value;
        }
        $script .= implode(',' . PHP_EOL, $params) . PHP_EOL;
        $script .= '});';

        $this->view->headScript()->appendScript($script);
        return $this;
    }
}
