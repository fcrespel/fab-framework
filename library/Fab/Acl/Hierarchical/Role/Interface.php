<?php

interface Fab_Acl_Hierarchical_Role_Interface extends Zend_Acl_Role_Interface
{
    /**
     * Returns the parents of the Role.
     * @return string[]|Zend_Acl_Role_Interface[] array of parent roles
     */
    public function getRoleParents();
}
