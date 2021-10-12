<?php

class Fab_Form extends Zend_Form
{
    /**
     * Enable CSRF token validation.
     * @var bool
     */
    protected $_csrfEnabled = true;

    public function __construct($options = null)
    {
        $this->addPrefixPath('Fab_Form_Element_', 'Fab/Form/Element/', self::ELEMENT);
        $this->addElementPrefixPath('Fab_Validate', 'Fab/Validate/', Zend_Form_Element::VALIDATE);

        if ($this->_csrfEnabled) {
            $this->addElement('csrfToken', '_csrf', array('decorators' => array('ViewHelper', 'Errors')));
        }

        parent::__construct($options);
    }
}
