<?php

class Fab_Form_Model extends ZFDoctrine_Form_Model
{
    /**
     * Which Zend_Form element types are associated with which doctrine type?
     * @var array
     */
    protected $_columnTypes = array(
        'integer' => 'text',
        'decimal' => 'text',
        'float' => 'text',
        'string' => 'text',
        'varchar' => 'text',
        'boolean' => 'checkbox',
        'timestamp' => 'text',
        'time' => 'text',
        'date' => 'text',
        'enum' => 'select',
        'text' => 'textarea',
    );
    
    /**
     * Field names listed in this array will not be shown in the form
     * @var array
     */
    protected $_ignoreFields = array('created_at', 'updated_at');
    
    /**
     * @param array $options Options to pass to the Zend_Form constructor
     */
    public function __construct($options = null)
    {
        if (!isset($options['fieldLabels']) &&
           (!isset($this->_fieldLabels) || count($this->_fieldLabels) == 0) &&
            is_callable(array($this->_model, 'getFieldLabels'))) {
            $this->setFieldLabels(call_user_func(array($this->_model, 'getFieldLabels')));
        }
        
        $this->addPrefixPath('Fab_Form_Element_', 'Fab/Form/Element/', self::ELEMENT);
        $this->addElementPrefixPath('Fab_Validate', 'Fab/Validate/', Zend_Form_Element::VALIDATE);
        
        parent::__construct($options);
    }
    
    /**
     * Parses columns to fields
     */
    protected function _columnsToFields()
    {
        parent::_columnsToFields();
        foreach ($this->getElements() as $element) {
            if ($element->getType() == 'Zend_Form_Element_Textarea') {
                if ($element->getAttrib('rows') == null)
                    $element->setAttrib('rows', 3);
                if ($element->getAttrib('cols') == null)
                    $element->setAttrib('cols', 70);
            }
        }
    }
    
    /**
     * Save the form data
     * @param bool $persist Save to DB or not
     * @return Doctrine_Record
     */
    public function save($persist = true)
    {
        // Ignore password elements with empty value (to avoid removing an existing value in DB when editing)
        $ignored = array();
        foreach ($this->getElements() as $element) {
            $value = $element->getValue();
            if ($element->getType() == 'Zend_Form_Element_Password' && empty($value) && !$element->getIgnore()) {
                $element->setIgnore(true);
                $ignored[] = $element;
            }
        }
        
        // Save the record
        parent::save($persist);
        
        // Restore ignored elements
        foreach ($ignored as $element) {
            $element->setIgnore(false);
        }
    }
}
