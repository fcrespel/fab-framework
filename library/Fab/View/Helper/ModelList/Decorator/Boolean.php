<?php

class Fab_View_Helper_ModelList_Decorator_Boolean extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var string */
    protected $_trueValue = 'true';
    
    /** @var string */
    protected $_falseValue = 'false';
    
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue)
    {
        $displayValue = $fieldValue ? $this->getTrueValue() : $this->getFalseValue();
        return '<span class="record-value-bool record-value-bool-' . ($fieldValue ? 'true' : 'false') . '">' . $displayValue . '</span>';
    }
    
    /**
     * Get the field value to display when its actual value is true.
     * @return string
     */
    public function getTrueValue()
    {
        return $this->_trueValue;
    }
    
    /**
     * Set the field value to display when its actual value is true.
     * @param string $trueValue
     * @return self
     */
    public function setTrueValue($trueValue)
    {
        $this->_trueValue = $trueValue;
        return $this;
    }
    
    /**
     * Get the field value to display when its actual value is false.
     * @return string
     */
    public function getFalseValue()
    {
        return $this->_falseValue;
    }
    
    /**
     * Set the field value to display when its actual value is false.
     * @param string $falseValue
     * @return self
     */
    public function setFalseValue($falseValue)
    {
        $this->_falseValue = $falseValue;
        return $this;
    }
}
