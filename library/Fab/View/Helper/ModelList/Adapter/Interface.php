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
     * @param mixed $query
     * @return Zend_Paginator
     */
    public function getPaginator($query = null);

    /**
     * Get fields names for a model.
     * @return array
     */
    public function getFieldNames();
}
