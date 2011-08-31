<?php

abstract class Fab_View_Helper_ModelList_Decorator_Abstract implements Fab_View_Helper_ModelList_Decorator_Interface
{
    /** @var Zend_View_Interface */
    public $view = null;
    
    /** @var Fab_View_Helper_ModelList_Context */
    public $context = null;
    
    /**
     * Set the View object.
     * @param  Zend_View_Interface $view
     * @return self
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
        return $this;
    }
    
    /**
     * Set the ModelList_Context object.
     * @param Fab_View_Helper_ModelList_Context $context
     * @return self
     */
    public function setContext(Fab_View_Helper_ModelList_Context $context)
    {
        $this->context = $context;
        return $this;
    }
    
    /**
     * Set the options for this decorator.
     * @param array options
     * @return self
     */
    public function setOptions($options = array())
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
}
