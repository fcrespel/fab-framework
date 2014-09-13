<?php

class Fab_Form_Element_Email extends Fab_Form_Element_Html5
{
    public $type = 'email';
    
    public function init()
    {
        if ($this->isAutoloadValidators())
        {
            $this->addValidator('EmailAddress');
        }
    }
}
