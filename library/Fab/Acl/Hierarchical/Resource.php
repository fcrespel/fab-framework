<?php

class Fab_Acl_Hierarchical_Resource extends Zend_Acl_Resource implements Fab_Acl_Hierarchical_Resource_Interface
{
    /** @var string|Zend_Acl_Resource_Interface */
    protected $_resourceParent = null;
    
    /**
     * Returns the parent of the Resource.
     * @return string|Zend_Acl_Resource_Interface parent resource
     */
    public function getResourceParent()
    {
        return $this->_resourceParent;
    }
}
