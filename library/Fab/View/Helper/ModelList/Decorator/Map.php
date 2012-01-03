<?php

class Fab_View_Helper_ModelList_Decorator_Map extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var array */
    protected $_map = array();
    
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
        
        $map = $this->getMap();
        $value = isset($map[$fieldValue]) ? $map[$fieldValue] : $fieldValue;
        return $this->view->escape($value);
    }
    
    /**
     * Get the replacement map.
     * @return array
     */
    public function getMap()
    {
        return $this->_map;
    }

    /**
     * Set the remplacement map.
     * @param array $map
     * @return self 
     */
    public function setMap($map)
    {
        $this->_map = $map;
        return $this;
    }
}
