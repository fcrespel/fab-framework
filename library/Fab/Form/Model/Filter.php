<?php

class Fab_Form_Model_Filter extends Fab_Form_Model
{
    protected $_generateManyFields = false;
    protected $_submitLabel = 'Search';
    
    /**
     * Generates the form
     */
    protected function _generateForm()
    {
        parent::_generateForm();
        foreach ($this->getElements() as $element) {
            if ($element->isRequired()) {
                // No element is required for search
                $element->setRequired(false);
            }
            if ($element instanceof Zend_Form_Element_Multi) {
                // Rewrite options to add an empty key
                $optionsOld = $element->getMultiOptions();
                $optionsNew = array('' => '------');
                foreach ($optionsOld as $key => $value) {
                    if (($key === 0 || $key === '') && $value === '------') {
                        // Existing empty key, ignore it
                    } else {
                        $optionsNew[$key] = $value;
                    }
                }
                $element->setMultiOptions($optionsNew);
            }
        }
    }
}
