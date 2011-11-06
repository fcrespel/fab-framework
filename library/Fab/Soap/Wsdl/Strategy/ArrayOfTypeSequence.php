<?php

class Fab_Soap_Wsdl_Strategy_ArrayOfTypeSequence extends Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence
{
    /**
     * Strip the xsd: from a singularType and Format it nice for ArrayOf<Type> naming
     *
     * @param  string $singularType
     * @return string
     */
    protected function _getStrippedXsdType($singularType)
    {
        return ucfirst(substr($singularType, 4));
    }
}
