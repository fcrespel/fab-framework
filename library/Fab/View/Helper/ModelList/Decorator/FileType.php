<?php

class Fab_View_Helper_ModelList_Decorator_FileType extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var string */
    protected $_cssClass = 'file';
    
    /** @var string */
    protected $_cssPrefix = 'file-';
    
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
        
        $class = $this->getCssClass();
        $extension = pathinfo($fieldValue, PATHINFO_EXTENSION);
        if (!empty($extension))
            $class .= ' ' . $this->getCssPrefix() . strtolower($extension);
        
        return '<span class="' . $class . '">' . $fieldValue . '</span>';
    }
    
    /**
     * Get the generic CSS class to use.
     * @return string
     */
    public function getCssClass()
    {
        return $this->_cssClass;
    }

    /**
     * Set the generic CSS class to use.
     * @param string $cssClass
     * @return self
     */
    public function setCssClass($cssClass)
    {
        $this->_cssClass = $cssClass;
        return $this;
    }
    
    /**
     * Get the CSS class prefix to use before the extension.
     * @return string
     */
    public function getCssPrefix()
    {
        return $this->_cssPrefix;
    }

    /**
     * Set the CSS class prefix to use before the extension.
     * @param string $cssPrefix
     * return self
     */
    public function setCssPrefix($cssPrefix)
    {
        $this->_cssPrefix = $cssPrefix;
        return $this;
    }
}
