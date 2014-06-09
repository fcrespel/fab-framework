<?php

class Fab_Acl extends Zend_Acl
{
    /** @const string anonymous role name */
    const ANONYMOUS_ROLE = 'anonymous';
    
    /** @var Zend_Acl_Role_Interface|string|null current role */
    protected $_currentRole = self::ANONYMOUS_ROLE;
    
    /**
     * Get the current role.
     * @return Zend_Acl_Role_Interface|string
     */
    public function getCurrentRole()
    {
        return $this->_currentRole;
    }

    /**
     * Set the current role.
     * @param Zend_Acl_Role_Interface|string|null $role
     */
    public function setCurrentRole($role = null)
    {
        $this->_currentRole = $role;
    }
    
    /**
     * Check if the current role is anonymous.
     * @return boolean
     */
    public function isAnonymous()
    {
        return $this->_currentRole == self::ANONYMOUS_ROLE;
    }
    
    /**
     * Check if the current role is authenticated (not anonymous).
     * @return boolean
     */
    public function isAuthenticated()
    {
        return !$this->isAnonymous();
    }

    /**
     * Returns true if and only if the current Role has access to the Resource.
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @param  string                             $privilege
     * @return boolean
     */
    public function isCurrentRoleAllowed($resource = null, $privilege = null)
    {
        return $this->isAllowed($this->_currentRole, $resource, $privilege);
    }
    
    /**
     * @see Zend_Acl::isAllowed()
     */
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        $this->_autoRegisterResource($resource);
        $this->_autoRegisterRole($role);
        
        $allowed = parent::isAllowed($role, $resource, $privilege);
        if ($allowed && $role instanceof Fab_Acl_Queryable_Role)
            $allowed &= $role->isResourceAllowed($resource, $privilege);
        if ($allowed && $resource instanceof Fab_Acl_Queryable_Resource)
            $allowed &= $resource->isRoleAllowed($role, $privilege);
        
        return $allowed;
    }
    
    /**
     * @see Zend_Acl::addResource()
     */
    public function addResource($resource, $parent = null)
    {
        $this->_autoRegisterResource($parent);
        return parent::addResource($resource, $parent);
    }
    
    /**
     * @see Zend_Acl::addRole()
     */
    public function addRole($role, $parents = null)
    {
        if (is_string($parents)) {
            $this->_autoRegisterRole($parents);
        } else if (is_array($parents)) {
            foreach ($parents as $parent) {
                $this->_autoRegisterRole($parent);
            }
        }
        return parent::addRole($role, $parents);
    }
    
    /**
     * Register a Resource automatically if it is missing.
     * @param Zend_Acl_Resource_Interface $resource 
     */
    protected function _autoRegisterResource($resource)
    {
        if ($resource !== null && !$this->has($resource) && $resource instanceof Zend_Acl_Resource_Interface) {
            $parent = null;
            if ($resource instanceof Fab_Acl_Hierarchical_Resource_Interface) {
                $parent = $resource->getResourceParent();
            }
            $this->addResource($resource, $parent);
        }
    }
    
    /**
     * Register a Role automatically if it is missing.
     * @param Zend_Acl_Role_Interface $role 
     */
    protected function _autoRegisterRole($role)
    {
        if ($role !== null && !$this->hasRole($role) && $role instanceof Zend_Acl_Role_Interface) {
            $parents = null;
            if ($role instanceof Fab_Acl_Hierarchical_Role_Interface) {
                $parents = $role->getRoleParents();
            }
            $this->addRole($role, $parents);
        }
    }
}
