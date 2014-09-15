<?php

class Fab_View_Helper_TinyMce extends Zend_View_Helper_Abstract
{
    /** @var bool */
    protected $_enabled = false;
    
    /** @var string */
    protected $_defaultScript = '//tinymce.cachefly.net/4.1/tinymce.min.js';

    /** @var array */
    protected $_config = array('selector' => 'textarea[data-tinymce]');
    
    /** @var string */
    protected $_scriptPath;
    
    /** @var string */
    protected $_scriptFile;

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
     * Render this TinyMCE view helper (only once).
     */
    public function render()
    {
        if (false === $this->_enabled) {
            $this->_renderScript();
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
     * Render the editor script.
     * @return self
     */
    protected function _renderEditor()
    {
        $script = 'tinymce.init({' . PHP_EOL;

        $params = array();
        foreach ($this->_config as $name => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } else if ($value == 'true') {
                $value = 'true';
            } else if ($value == 'false') {
                $value = 'false';
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
