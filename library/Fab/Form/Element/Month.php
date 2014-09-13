<?php

class Fab_Form_Element_Month extends Fab_Form_Element_Html5
{
    public $type = 'month';
    
    public function init()
    {
        if ($this->isAutoloadValidators())
        {
            //@todo: base month numbers on Zend_Locale
            $this->addValidator('Between', false, array('min' => 1, 'max' => 52));
        }
    }
}
