<?php

interface Fab_View_Helper_ModelList_Adapter_Interface
{
    /**
     * Constructor.
     * @param string $modelName
     */
    public function __construct($modelName);

    /**
     * Get a paginator instance for a model.
     * @param mixed $query model query
     * @param mixed $filter model filter
     * @param string $sortField field to order by
     * @param string $sortDirection either 'asc' or 'desc'
     * @return Zend_Paginator
     */
    public function getPaginator($query = null, $filter = null, $sortField = null, $sortDirection = 'asc');

    /**
     * Get fields names for a model.
     * @return array
     */
    public function getFieldNames();
}
