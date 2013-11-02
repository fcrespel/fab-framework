<?php

class Fab_View_Helper_ModelList_Decorator_Default extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var string */
    protected $_defaultValue;
    
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue)
    {
        if (empty($fieldValue)) {
            return $this->view->escape($this->getDefaultValue());
        } else {
            return $this->view->escape($fieldValue);
        }
    }
    
    /**
     * Get the default value to use if the actual value is missing.
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    /**
     * Set the default value to use if the actual value is missing.
     * @param string $defaultValue
     * @return self
     */
    public function setDefaultValue($defaultValue)
    {
        $this->_defaultValue = $defaultValue;
        return $this;
    }
}
