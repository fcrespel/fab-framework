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
        'text' => 'text',
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
        
        parent::__construct($options);
    }
}
