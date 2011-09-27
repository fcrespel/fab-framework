<?php

class Fab_View_Helper_ModelList_Adapter_Doctrine extends Fab_View_Helper_ModelList_Adapter_Abstract
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

    public function getPaginator($query = null, $sortField = null, $sortDirection = 'asc')
    {
        if (!$query)
            $query = Doctrine_Core::getTable($this->_modelName)->createQuery();
        if ($sortField) {
            $orderby = $sortField;
            if (!strcasecmp($sortDirection, 'desc'))
                $orderby .= ' DESC';
            $query->removeDqlQueryPart('orderby');
            $query->orderBy($orderby);
        }

        $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);
        $paginator = new Zend_Paginator($adapter);

        return $paginator;
    }
}
