<?php

class Fab_Form extends Zend_Form
{
    public function __construct($options = null)
    {
        $this->addPrefixPath('Fab_Form_Element_', 'Fab/Form/Element/', self::ELEMENT);
        $this->addElementPrefixPath('Fab_Validate', 'Fab/Validate/', Zend_Form_Element::VALIDATE);
        parent::__construct($options);
    }
}
