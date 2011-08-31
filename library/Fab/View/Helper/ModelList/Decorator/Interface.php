<?php

interface Fab_View_Helper_ModelList_Decorator_Interface
{
    /**
     * Set the View object.
     * @param Zend_View_Interface $view
     * @return self
     */
    public function setView(Zend_View_Interface $view);
    
    /**
     * Set the ModelList_Context object.
     * @param Fab_View_Helper_ModelList_Context $context
     * @return self
     */
    public function setContext(Fab_View_Helper_ModelList_Context $context);
    
    /**
     * Set the options for this decorator.
     * @param array options
     * @return self
     */
    public function setOptions($options = array());
    
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue);
}
