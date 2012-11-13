<?php

class Fab_View_Helper_ModelList_Decorator_FileSize extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var array */
    protected $_units = array();
    
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue)
    {
        return $this->view->fileSize($fieldValue, $this->getUnits());
    }
    
    /**
     * Get the unit names to use, starting from bytes.
     * @return array
     */
    public function getUnits()
    {
        return $this->_units;
    }
    
    /**
     * Set unit names to use, starting from bytes.
     * @param array $units
     * @return self
     */
    public function setUnits($units)
    {
        $this->_units = $units;
        return $this;
    }
}
