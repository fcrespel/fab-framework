<?php

abstract class Fab_Form_Element_Html5 extends Zend_Form_Element
{
    /**
     * Default input type
     * @var string
     */
    const DEFAULT_TYPE = 'text';
    
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'formHtml5';
    
    /**
     * Input element type
     * @var string
     */
    public $type = self::DEFAULT_TYPE;
    
    /**
     * Array of allowed input types
     * @var array
     */
    protected $_allowedTypes = array('text', 'color', 'date', 'datetime', 'datetime-local', 'email', 'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week');

    /**
     * Check if the validators should be auto loaded
     * @var bool
     */
    protected $_autoloadValidators = true;

    /**
     * Check if the filters should be auto loaded
     * @var bool
     */
    protected $_autoloadFilters = true;

    /**
     * Constructor that takes into account the type given, if given
     * Proxies its parent constructor to provide rest of functionality
     * @param $spec
     * @param $options
     * @uses Zend_Form_Element
     */
    public function __construct($spec, $options = null)
    {
        if (!isset($options['type'])) {
            $options['type'] = $this->_getType($spec);
        }
        parent::__construct($spec, $options);
    }

    /**
     * Flag if the the validators should be autoloaded
     * @param bool $flag
     * @return Fab_Form_Element_Html5 Provides a fluent interface
     */
    public function setAutoloadValidators($flag)
    {
        $this->_autoloadValidators = (bool) $flag;
        return $this;
    }

    /**
     * Flag if the the validators should be autoloaded
     * @return bool
     */
    public function isAutoloadValidators()
    {
        return $this->_autoloadValidators;
    }

    /**
     * Flag if the the filters should be autoloaded
     * @param bool $flag
     * @return Fab_Form_Element_Html5 Provides a fluent interface
     */
    public function setAutoloadFilters($flag)
    {
        $this->_autoloadFilters = (bool) $flag;
        return $this;
    }

    /**
     * Flag if the the validators should be autoloaded
     * @return bool
     */
    public function isAutoloadFilters()
    {
        return $this->_autoloadFilters;
    }

    /**
     * Check if the doctype is HTML5
     * @return bool
     */
    protected function _isHtml5()
    {
        return $this->getView()->getHelper('doctype')->isHtml5();
    }

    /**
     * Check if the current type is allowed, else return the DEFAULT_TYPE value
     * @return string
     */
    protected function _getType()
    {
        $type = trim(strtolower($this->type));
        if ($this->_isHtml5() && in_array($type, $this->_allowedTypes)) {
            return $type;
        } else {
            return self::DEFAULT_TYPE;
        }
    }
}