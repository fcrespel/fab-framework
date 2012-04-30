<?php

class Fab_View_Helper_ModelList_Adapter_Ldap extends Fab_View_Helper_ModelList_Adapter_Abstract
{
    public function getFieldNames()
    {
        $modelName = $this->_modelName;
        return $modelName::getFieldNames();
    }

    public function getPaginator($query = null, $filter = null, $sortField = null, $sortDirection = 'asc')
    {
        $filters = array();
        
        // Add query to filters list if specified
        if ($query !== null) {
            $filters[] = $query;
        }
        
        // Add extra filters to filters list
        if ($filter) {
            if (is_string($filter)) {
                // Textual filter
                $filters[] = Zend_Ldap_Filter::string($filter);
            } else if (is_array($filter)) {
                // Add each field as a filter
                foreach ($filter as $field => $value) {
                    $value = (string)$value;
                    if (strlen($value) != 0) {
                        $filters[] = Zend_Ldap_Filter::begins($field, $value);
                    }
                }
            }
        }
        
        // Build the final LDAP filter
        $ldapFilter = null;
        if (count($filters) > 0) {
            $ldapFilter = new Zend_Ldap_Filter_And($filters);
        }
        
        // Find all LDAP nodes
        $modelName = $this->_modelName;
        $records = $modelName::findAll($ldapFilter, $sortField);
        return Zend_Paginator::factory($records);
    }
}
