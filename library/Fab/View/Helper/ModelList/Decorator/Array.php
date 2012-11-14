<?php

class Fab_View_Helper_ModelList_Decorator_Array extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var string */
    protected $_separator = ', ';
    
    /** @var string */
    protected $_emptyValue = '';
    
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue)
    {
        if (empty($fieldValue))
            return $this->getEmptyValue();
        
        if (!is_array($fieldValue)) {
            $fieldValue = array($fieldValue);
        }
        
        if (count($fieldValue) == 1) {
            return $fieldValue[0];
        }
        
        $decoratedValues = array();
        foreach ($fieldValue as $singleValue) {
            $decoratedValues[] = $this->context->getDecorator($fieldName, $singleValue)->render($fieldName, $singleValue);
        }
        return implode($this->getSeparator(), $decoratedValues);
    }
    
    /**
     * Get the separator to use between array elements.
     * @return string 
     */
    public function getSeparator()
    {
        return $this->_separator;
    }
    
    /**
     * Set the separator to use between array elements.
     * @param  string $separator
     * @return self
     */
    public function setSeparator($separator)
    {
        $this->_separator = $separator;
        return $this;
    }
    
    /**
     * Get the value to display when the array is empty.
     * @return string
     */
    public function getEmptyValue()
    {
        return $this->_emptyValue;
    }

    /**
     * Set the value to display when the array is empty.
     * @param string $emptyValue
     * @return self
     */
    public function setEmptyValue($emptyValue)
    {
        $this->_emptyValue = $emptyValue;
        return $this;
    }


}
