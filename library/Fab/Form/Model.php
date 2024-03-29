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
        'bit' => 'text',
    );
    
    /**
     * Field names listed in this array will not be shown in the form
     * @var array
     */
    protected $_ignoreFields = array('created_at', 'updated_at');
    
    /**
     * Submit button label
     * @var string
     */
    protected $_submitLabel = 'Save';

    /**
     * Enable CSRF token validation.
     * @var bool
     */
    protected $_csrfEnabled = true;

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

        if ($this->_csrfEnabled) {
            $this->addElement('csrfToken', '_csrf', array('decorators' => array('ViewHelper', 'Errors')));
        }

        parent::__construct($options);
    }
    
    /**
     * Generates the form
     */
    protected function _generateForm()
    {
        parent::_generateForm();
        $this->_postGenerateInternal();
        $this->getElement('Save')->setLabel($this->_submitLabel)->setOrder(999);
    }
    
    /**
     * Post-generation internal adjustments.
     */
    protected function _postGenerateInternal()
    {
        foreach ($this->getElements() as $element) {
            if ($element instanceof Zend_Form_Element_Textarea) {
                // Add default rows and cols on textarea
                if ($element->getAttrib('rows') == null)
                    $element->setAttrib('rows', 3);
                if ($element->getAttrib('cols') == null)
                    $element->setAttrib('cols', 70);
                
            } else if ($element instanceof Zend_Form_Element_Multi) {
                // Rewrite options to replace the '0' key with ''
                $optionsOld = $element->getMultiOptions();
                $optionsNew = array();
                if (!$element->isRequired()) {
                    $optionsNew[''] = '------';
                }
                foreach ($optionsOld as $key => $value) {
                    if ($key === 0 && $value === '------') {
                        $optionsNew[''] = '------';
                    } else {
                        $optionsNew[$key] = $value;
                    }
                }
                asort($optionsNew, SORT_STRING | SORT_FLAG_CASE);
                $element->setMultiOptions($optionsNew);
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
        // Preprocess certain elements
        $ignored = array();
        foreach ($this->getElements() as $element) {
            $value = $element->getValue();
            if ($element instanceof Zend_Form_Element_Password && empty($value) && !$element->getIgnore()) {
                // Ignore password elements with empty value (to avoid removing an existing value in DB when editing)
                $element->setIgnore(true);
                $ignored[] = $element;
            } else if ($element instanceof Zend_Form_Element_Multi && empty($value)) {
                // Force empty values in multiselects to be NULL (to avoid foreign key contraint failures)
                $element->setValue(null);
            }
        }

        // Save the record
        $record = $this->getRecord();
        $this->_adapter->setRecordValues($this->getValues(true));
        if ($persist) {
            $this->_adapter->saveRecord();
        }

        // Save sub-forms
        $this->_saveSubForms($this, $persist);

        // Restore ignored elements
        foreach ($ignored as $element) {
            $element->setIgnore(false);
        }

        $this->_postSave($persist);
        return $record;
    }
    
    /**
     * Save sub-forms of a given form.
     * @param Zend_Form $form
     * @param bool $persist
     */
    protected function _saveSubForms($form, $persist = true)
    {
        foreach ($form->getSubForms() as $subForm) {
            if (method_exists($subForm, 'save')) {
                $subForm->save($persist);
            } else {
                $this->_saveSubForms($subForm, $persist);
            }
        }
    }
}
