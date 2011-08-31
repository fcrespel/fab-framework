<?php

interface Fab_Acl_Queryable_Resource
{
    /**
     * Returns true if and only if the Role has access to this Resource.
     * This function allows Resource implementations to dynamically
     * determine whether a Role is granted a given privilege.
     * 
     * @param  Zend_Acl_Role_Interface|string     $role
     * @param  string                             $privilege
     * @return boolean
     */
    public function isRoleAllowed($role = null, $privilege = null);
}
