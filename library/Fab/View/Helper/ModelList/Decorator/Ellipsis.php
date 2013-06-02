<?php

class Fab_View_Helper_ModelList_Decorator_Ellipsis extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var int */
    protected $_maxLength = 50;

    /** @var string */
    protected $_ellipsis = '...';

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
        $max = $this->getMaxLength();
        $ellipsis = $this->getEllipsis();
        
        $value = $this->view->escape($fieldValue);
        if (strlen($value) > $max) {
            // Truncate the value
            $value = substr($value, 0, $max - strlen($ellipsis));
            $lastSpace = strrpos($value, ' ');
            if ($lastSpace !== false)
                $value = substr($value, 0, $lastSpace);
            
            // Add the ellipsis
            $value .= $ellipsis;
            
            // Add a tooltip if necessary
            if ($this->getShowTooltip())
                $value = '<span title="' . $this->view->escape($fieldValue) . '">' . $value . '</span>';
        }
        
        return $value;
    }

    /**
     * Get the maximum length of the string after which it will be truncated.
     * @return int
     */
    public function getMaxLength()
    {
        return $this->_maxLength;
    }

    /**
     * Set the maximum length of the string after which it will be truncated.
     * @param int $maxLength
     * @return self
     */
    public function setMaxLength($maxLength)
    {
        $this->_maxLength = $maxLength;
        return $this;
    }

    /**
     * Get the ellipsis characters to use when the string needs to be truncated.
     * @return string
     */
    public function getEllipsis()
    {
        return $this->_ellipsis;
    }

    /**
     * Set the ellipsis characters to use when the string needs to be truncated.
     * @param string $ellipsis
     * @return self
     */
    public function setEllipsis($ellipsis)
    {
        $this->_ellipsis = $ellipsis;
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
