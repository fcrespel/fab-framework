<?php

class Fab_Form_Element_Week extends Fab_Form_Element_Html5
{
    public $type = 'week';
    
    public function init()
    {
        if ($this->isAutoloadValidators())
        {
            //@todo: base week numbers on Zend_Locale
            $this->addValidator('Between', false, array('min' => 1, 'max' => 52));
        }
    }
}
