<?php

abstract class Fab_View_Helper_ModelList_Adapter_Abstract implements Fab_View_Helper_ModelList_Adapter_Interface
{
    protected $_modelName;

    public function __construct($modelName)
    {
        $this->_modelName = $modelName;
    }
}
