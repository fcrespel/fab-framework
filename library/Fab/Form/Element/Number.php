<?php

class Fab_Form_Element_Number extends Fab_Form_Element_Html5
{
    public $type = 'number';
    
    public function init()
    {
        if ($this->isAutoloadFilters())
        {
            $this->addFilter('Digits');
        }

        if ($this->isAutoloadValidators())
        {
            $this->addValidator('Digits');
            $validatorOpts = array_filter(array(
                'min' => $this->getAttrib('min'),
                'max' => $this->getAttrib('max'),
            ));
            $validator = null;
            if (2 === count($validatorOpts))
            {
                $validator = 'Between';
            }
            else if (isset($validatorOpts['min']))
            {
                $validator = 'GreaterThan';
            }
            else if (isset($validatorOpts['max']))
            {
                $validator = 'LessThan';
            }
            if (null !== $validator)
            {
                $this->addValidator($validator, false, $validatorOpts);
            }
        }
    }
}
