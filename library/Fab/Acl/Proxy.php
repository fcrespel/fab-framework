<?php

class Fab_Acl_Proxy extends Zend_Acl
{
    protected $_className = null;
    protected $_classArgs = array();
    protected $_instance = null;
    protected $_cache = null;
    

    /**
     * This class delegates all public calls to another Zend_Acl instance,
     * that is retrieved from cache or constructed dynamically.
     * Thanks to overloading, the backend class may contain additional methods
     * and may not extend Zend_Acl directly, although all methods from Zend_Acl
     * should be implemented.
     * @param string $className name or instance of the backend class
     * @param array $classArgs arguments used to instantiate the backend class
     */
    public function __construct($className = null, $classArgs = array())
    {
        if (is_object($className)) {
            $this->setObject($className);
        } else if (is_string($className)) {
            $this->setClass($className, $classArgs);
        }
    }

    /**
     * Set the backend Zend_Acl class.
     * @param string $className name of the backend class
     * @param array $classArgs arguments used to instantiate the backend class
     */
    public function setClass($className, $classArgs = array())
    {
        if (!is_string($className)) {
            throw new Fab_Acl_Exception('Invalid class argument (' . gettype($className) . ')');
        }

        if (!class_exists($className)) {
            throw new Fab_Acl_Exception('Class "' . $className . '" does not exist');
        }

        $this->_className = $className;
        $this->_classArgs = $classArgs;
    }

    /**
     * Set the backend Zend_Acl instance.
     * @param object $object
     */
    public function setObject($object)
    {
        if (!is_object($object)) {
            throw new Fab_Acl_Exception('Invalid object argument (' . gettype($object) . ')');
        }

        $this->_instance = $object;
    }

    /**
     * Get the cache frontend being used.
     * @return Zend_Cache_Core|null
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Set the cache frontend to use.
     * @param Zend_Cache_Core|null $cache
     */
    public function setCache($cache)
    {
        $this->_cache = $cache;
    }

    /**
     * Invalidate the ACL cache.
     */
    public function invalidateCache()
    {
        if ($this->_cache !== null) {
            $this->_cache->remove($this->_getCacheId());
        }
    }

    /**
     * Get the cache identifier to use.
     * @return string
     */
    protected function _getCacheId()
    {
        return 'ACL_' . $this->_className;
    }

    /**
     * Get the backing Zend_Acl instance.
     * This method will try to use a cached object first.
     * @return Zend_Acl
     */
    public function _getInstance()
    {
        if ($this->_instance === null) {
            if (empty($this->_className))
                throw new Fab_Acl_Exception('No backend ACL class name was specified');
            if ($this->_cache === null || ($acl = $this->_cache->load($this->_getCacheId())) === false) {
                $class = new ReflectionClass($this->_className);
                $acl = $class->getConstructor() == null ? $class->newInstance() : $class->newInstanceArgs($this->_classArgs);
                if ($this->_cache !== null) {
                    $this->_cache->save($acl, $this->_getCacheId());
                }
            }
            $this->_instance = $acl;
        }
        return $this->_instance;
    }

    
    /* Overriden methods ******************************************************/

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->_getInstance(), $name), $arguments);
    }
    
    public function add(Zend_Acl_Resource_Interface $resource, $parent = null)
    {
        return $this->_getInstance()->add($resource, $parent);
    }

    public function addResource($resource, $parent = null)
    {
        return $this->_getInstance()->addResource($resource, $parent);
    }

    public function addRole($role, $parents = null)
    {
        return $this->_getInstance()->addRole($role, $parents);
    }

    public function allow($roles = null, $resources = null, $privileges = null, Zend_Acl_Assert_Interface $assert = null)
    {
        return $this->_getInstance()->allow($roles, $resources, $privileges, $assert);
    }

    public function deny($roles = null, $resources = null, $privileges = null, Zend_Acl_Assert_Interface $assert = null)
    {
        return $this->_getInstance()->deny($roles, $resources, $privileges, $assert);
    }

    public function get($resource)
    {
        return $this->_getInstance()->get($resource);
    }

    public function getRegisteredRoles()
    {
        return $this->_getInstance()->getRegisteredRoles();
    }

    public function getResources()
    {
        return $this->_getInstance()->getResources();
    }

    public function getRole($role)
    {
        return $this->_getInstance()->getRole($role);
    }

    public function getRoles()
    {
        return $this->_getInstance()->getRoles();
    }

    public function has($resource)
    {
        return $this->_getInstance()->has($resource);
    }

    public function hasRole($role)
    {
        return $this->_getInstance()->hasRole($role);
    }

    public function inherits($resource, $inherit, $onlyParent = false)
    {
        return $this->_getInstance()->inherits($resource, $inherit, $onlyParent);
    }

    public function inheritsRole($role, $inherit, $onlyParents = false)
    {
        return $this->_getInstance()->inheritsRole($role, $inherit, $onlyParents);
    }

    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        return $this->_getInstance()->isAllowed($role, $resource, $privilege);
    }

    public function remove($resource)
    {
        return $this->_getInstance()->remove($resource);
    }

    public function removeAll()
    {
        return $this->_getInstance()->removeAll();
    }

    public function removeAllow($roles = null, $resources = null, $privileges = null)
    {
        return $this->_getInstance()->removeAllow($roles, $resources, $privileges);
    }

    public function removeDeny($roles = null, $resources = null, $privileges = null)
    {
        return $this->_getInstance()->removeDeny($roles, $resources, $privileges);
    }

    public function removeRole($role)
    {
        return $this->_getInstance()->removeRole($role);
    }

    public function removeRoleAll()
    {
        return $this->_getInstance()->removeRoleAll();
    }

    public function setRule($operation, $type, $roles = null, $resources = null, $privileges = null, Zend_Acl_Assert_Interface $assert = null)
    {
        return $this->_getInstance()->setRule($operation, $type, $roles, $resources, $privileges, $assert);
    }

}
