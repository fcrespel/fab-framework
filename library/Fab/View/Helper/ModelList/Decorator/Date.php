<?php

class Fab_View_Helper_ModelList_Decorator_Date extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var string */
    protected $_sourceFormat = 'yyyy-MM-dd HH:mm:ss';
    
    /** @var string */
    protected $_displayFormat = 'yyyy-MM-dd HH:mm';
    
    /** @var string */
    protected $tooltipFormat = 'yyyy-MM-dd HH:mm:ss';
    
    /** @var bool */
    protected $_showTooltip = false;
    
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue)
    {
        if (empty($fieldValue))
            return $fieldValue;
        
        $date = new Zend_Date($fieldValue, $this->getSourceFormat());
        $ret = $this->view->escape($date->toString($this->getDisplayFormat()));
        if ($this->getShowTooltip()) {
            $ret = '<span title="' . $date->toString($this->getTooltipFormat()) . '">' . $ret . '</span>';
        }
        return $ret;
    }
    
    /**
     * Get the source date format (ISO 8601 syntax).
     * @return string
     */
    public function getSourceFormat()
    {
        return $this->_sourceFormat;
    }

    /**
     * Set the source date format (ISO 8601 syntax).
     * @param string $sourceFormat
     * @return self
     */
    public function setSourceFormat($sourceFormat)
    {
        $this->_sourceFormat = $sourceFormat;
        return $this;
    }

    /**
     * Get the display date format (ISO 8601 syntax).
     * @return string
     */
    public function getDisplayFormat()
    {
        return $this->_displayFormat;
    }

    /**
     * Set the display date format (ISO 8601 syntax).
     * @param string $displayFormat
     * @return self
     */
    public function setDisplayFormat($displayFormat)
    {
        $this->_displayFormat = $displayFormat;
        return $this;
    }
    
    /**
     * Get the tooltip date format (ISO 8601 syntax).
     * @return string
     */
    public function getTooltipFormat()
    {
        return $this->tooltipFormat;
    }

    /**
     * Set the tooltip date format (ISO 8601 syntax).
     * @param string $tooltipFormat
     * @return self
     */
    public function setTooltipFormat($tooltipFormat)
    {
        $this->tooltipFormat = $tooltipFormat;
        return $this;
    }

    /**
     * Get whether a tooltip should be displayed when hovering the value.
     * @return bool
     */
    public function getShowTooltip()
    {
        return $this->_showTooltip;
    }

    /**
     * Set whether a tooltip should be displayed when hovering the value.
     * @param bool $showTooltip 
     * @return self
     */
    public function setShowTooltip($showTooltip)
    {
        $this->_showTooltip = $showTooltip;
        return $this;
    }
}
