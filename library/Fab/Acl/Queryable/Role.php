<?php

interface Fab_Acl_Queryable_Role
{
    /**
     * Returns true if and only if this Role has access to the Resource.
     * This function allows Role implementations to dynamically
     * determine whether a privilege on a given Resource is granted.
     * 
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @param  string                             $privilege
     * @return boolean
     */
    public function isResourceAllowed($resource = null, $privilege = null);
}
