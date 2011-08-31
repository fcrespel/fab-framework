<?php

class Fab_View_Helper_ModelList_Decorator_Escape extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue)
    {
        return $this->view->escape($fieldValue);
    }
}
