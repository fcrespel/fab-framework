<?php

class Fab_Form_Element_Note extends Zend_Form_Element_Xhtml
{
    /**
     * Use formNote view helper by default
     * @var string
     */
    public $helper = 'formNote';
    
    /**
     * Validate element value.
     * This overridden method always returns true, as this element only displays
     * (X)HTML data and is not a real form element.
     * @param type $value
     * @param type $context
     * @return type 
     */
    public function isValid($value, $context = null)
    {
        return true;
    }
}
