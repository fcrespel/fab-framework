<?php

class Fab_View_Helper_ModelList_Adapter_Ldap extends Fab_View_Helper_ModelList_Adapter_Abstract
{
    public function getFieldNames()
    {
        $modelName = $this->_modelName;
        return $modelName::getFieldNames();
    }

    public function getPaginator($query = null, $sortField = null, $sortDirection = 'asc')
    {
        $modelName = $this->_modelName;
        $records = $modelName::findAll($query);
        return Zend_Paginator::factory($records);
    }
}
