<?php

class Fab_Acl_Hierarchical_Role extends Zend_Acl_Role implements Fab_Acl_Hierarchical_Role_Interface
{
    /** @var string[]|Zend_Acl_Role_Interface[] */
    protected $_roleParents = array();
    
    /**
     * Returns the parents of the Role.
     * @return string[]|Zend_Acl_Role_Interface[] array of parent roles
     */
    public function getRoleParents()
    {
        return $this->_roleParents;
    }
}
