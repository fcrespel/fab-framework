<?php

class Fab_View_Helper_ModelList_Doctrine extends Fab_View_Helper_ModelList_Abstract
{
    public function getFieldNames()
    {
        $table = Doctrine_Core::getTable($this->_modelName);
        $data = $table->getColumns();
        
        $cols = array();
        foreach ($data as $name => $def) {
            $columnName = $table->getColumnName($name);
            $fieldName = $table->getFieldName($columnName);

            $cols[] = $fieldName;
        }

        return $cols;
    }

    public function getPaginator($query = null)
    {
        if (!$query) {
            $query = Doctrine_Core::getTable($this->_modelName)->createQuery();
        }

        $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);
        $paginator = new Zend_Paginator($adapter);

        return $paginator;
    }
}
