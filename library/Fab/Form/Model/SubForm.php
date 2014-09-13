<?php

class Fab_Form_Model_SubForm extends Fab_Form_Model
{
    /**
     * Whether or not form elements are members of an array
     * @var bool
     */
    protected $_isArray = true;

    /**
     * Load the default decorators
     *
     * @return Zend_Form_SubForm
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements');
        }
        return $this;
    }

    /**
     * Generates the form, but without submit button in this case
     */
    protected function _generateForm() {
        $this->_columnsToFields();
        if ($this->_generateManyFields) {
            $this->_manyRelationsToFields();
        }
        $this->_postGenerateInternal();
    }
}
