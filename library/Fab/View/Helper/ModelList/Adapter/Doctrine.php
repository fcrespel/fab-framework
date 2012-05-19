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

    public function getPaginator($query = null, $filter = null, $sortField = null, $sortDirection = 'asc')
    {
        $table = Doctrine_Core::getTable($this->_modelName);
        
        // Create a query if necessary
        if (!$query)
            $query = $table->createQuery();
        
        // Filter query results
        if ($filter) {
            if (is_string($filter)) {
                // Textual where clause
                $query->andWhere($filter);
            } else if (is_array($filter)) {
                // Add each field as a where clause
                foreach ($filter as $field => $value) {
                    $value = (string)$value;
                    if (strlen($value) != 0 && $table->hasField($field)) {
                        $definition = $table->getDefinitionOf($field);
                        if ($definition['type'] == 'string') {
                            if (substr($value, 0, 1) != '%') $value = '%' . $value;
                            if (substr($value, -1) != '%') $value .= '%';
                            $query->andWhere($field . ' LIKE ?', $value);
                        } else {
                            $query->andWhere($field . ' = ?', $value);
                        }
                    }
                }
            }
        }

        // Sort query results
        if ($sortField) {
            // Convert relation name to local field name
            $table = Doctrine_Core::getTable($this->_modelName);
            if ($table->hasRelation($sortField))
                $sortField = $table->getRelation($sortField)->getLocalFieldName();
            
            // Ensure the sort field is valid
            if ($table->hasField($sortField)) {
                // Create the 'order by' query part
                $orderby = $sortField;
                if (!strcasecmp($sortDirection, 'desc'))
                    $orderby .= ' DESC';

                // Replace the existing 'order by' query part
                $query->removeDqlQueryPart('orderby');
                $query->orderBy($orderby);
            }
        }

        $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);
        $paginator = new Zend_Paginator($adapter);

        return $paginator;
    }
}
