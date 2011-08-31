<?php

abstract class Fab_View_Helper_ModelList_Abstract implements Fab_View_Helper_ModelList_Interface
{
    protected $_modelName;

    public function __construct($modelName)
    {
        $this->_modelName = $modelName;
    }
}
