<?php

interface Fab_Acl_Hierarchical_Resource_Interface extends Zend_Acl_Resource_Interface
{
    /**
     * Returns the parent of the Resource.
     * @return string|Zend_Acl_Resource_Interface parent resource
     */
    public function getResourceParent();
}
