<?php

class Fab_View_Helper_ModelList extends Zend_View_Helper_Abstract
{
    /** @var bool */
    protected $_useAcl = true;

    /** @var Zend_Acl */
    protected $_acl;

    /** @var string|Zend_Acl_Role_Interface */
    protected $_role;

    /** @var string|Zend_Acl_Resource_Interface */
    protected $_resource;

    /** @var Zend_Acl */
    protected static $_defaultAcl;

    /** @var string|Zend_Acl_Role_Interface */
    protected static $_defaultRole;

    /** @var array */
    protected static $_defaultOptions = array(
        'pageParamName'         => 'page',
        'itemsPerPage'          => 30,
        'paginationStyle'       => 'Sliding',
        'paginationScript'      => null,
        'listScript'            => null,
        'addRecordAction'       => null,
        'singleRecordActions'   => array(),
    );

    /** @var array */
    protected static $_helpers = array(
        'Doctrine_Record'   => 'Fab_View_Helper_ModelList_Doctrine',
        'Fab_Ldap_Node'     => 'Fab_View_Helper_ModelList_Ldap',
    );

    /** @var array */
    protected static $_enabledView = array();

    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);
        $oid = spl_object_hash($view);
        if (!isset(self::$_enabledView[$oid])) {
            $view->addBasePath(dirname(__FILE__) . '/files');
            self::$_enabledView[$oid] = true;
        }
    }

    /**
     * Render a record list partial for a model.
     * @param string $modelName
     * @param array $options
     * @param mixed $query
     * @return string
     */
    public function modelList($modelName, array $options = array(), $query = null)
    {
        // Merge and fix options
        $options = array_merge(self::$_defaultOptions, $options);
        if (!$options['paginationScript'])
            $options['paginationScript'] = 'pagination.phtml';
        if (!isset($options['listScript']))
            $options['listScript'] = 'list.phtml';
        if (isset($options['useAcl']))
            $this->setUseAcl($options['useAcl']);
        if (isset($options['acl']))
            $this->setAcl($options['acl']);
        if (isset($options['resource']))
            $this->setResource($options['resource']);
        if (isset($options['role']))
            $this->setRole($options['role']);

        // Get the model-specific helper
        $helper = $this->_getHelper($modelName);

        // Configure the paginator
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $currentPage = $request->getParam($options['pageParamName'], 1);
        $paginator = $helper->getPaginator($query);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($options['itemsPerPage']);

        // Get the field names
        if (!isset($options['showFieldNames'])) {
            $fieldNames = $helper->getFieldNames();
        } else {
            $fieldNames = $options['showFieldNames'];
        }

        // Render the partial
        return $this->view->partial($options['listScript'], array(
            'modelName' => $modelName,
            'paginator' => $paginator,
            'currentPage' => $currentPage,
            'options' => $options,
            'fieldNames' => $fieldNames,
        ));
    }

    /**
     * Get a model-specific helper instance.
     * @param string $modelName 
     * @return Fab_View_Helper_ModelList_Interface
     */
    protected function _getHelper($modelName)
    {
        foreach (self::$_helpers as $modelClass => $helperClass) {
            if (is_subclass_of($modelName, $modelClass)) {
                return new $helperClass($modelName);
            }
        }
        throw new Fab_View_Exception("No helper found for model '$modelName'");
    }

    /**
     * Check whether a privilege is allowed on a given resource.
     * @param string|Zend_Acl_Resource_Interface $resource
     * @param string $privilege
     * @return bool
     */
    public function isAllowed($resource, $privilege)
    {
        if (!$this->getUseAcl() || ($acl = $this->getAcl()) === null)
            return true;

        if (!is_string($resource) && !($resource instanceof Zend_Acl_Resource_Interface))
            $resource = $this->getResource();

        if ($resource === null && $privilege === null)
            return true;
        else
            return $acl->isAllowed($this->getRole(), $resource, $privilege);
    }

    /**
     * Set whether ACL should be used.
     * @param  bool $useAcl
     * @return self
     */
    public function setUseAcl($useAcl = true)
    {
        $this->_useAcl = (bool) $useAcl;
        return $this;
    }

    /**
     * Get whether ACL should be used.
     * @return bool
     */
    public function getUseAcl()
    {
        return $this->_useAcl;
    }

    /**
     * Set ACL to use to check permissions on records.
     * @param Zend_Acl $acl
     * @return self
     */
    public function setAcl(Zend_Acl $acl = null)
    {
        $this->_acl = $acl;
        return $this;
    }

    /**
     * Get ACL to use to check permissions on records.
     * @return Zend_Acl|null
     */
    public function getAcl()
    {
        if ($this->_acl === null && self::$_defaultAcl !== null) {
            return self::$_defaultAcl;
        }
        return $this->_acl;
    }

    /**
     * Set ACL role to use to check permissions on records.
     * @param  string[Zend_Acl_Role_Interface|null $role
     * @throws Fab_View_Exception
     * @return self
     */
    public function setRole($role = null)
    {
        if (null === $role || is_string($role) ||
            $role instanceof Zend_Acl_Role_Interface) {
            $this->_role = $role;
        } else {
            $e = new Fab_View_Exception(sprintf(
                '$role must be a string, null, or an instance of '
                .  'Zend_Acl_Role_Interface; %s given',
                gettype($role)
            ));
            $e->setView($this->view);
            throw $e;
        }

        return $this;
    }

    /**
     * Get ACL role to use to check permissions on records.
     * @return string|Zend_Acl_Role_Interface|null
     */
    public function getRole()
    {
        if ($this->_role === null && self::$_defaultRole !== null) {
            return self::$_defaultRole;
        }
        return $this->_role;
    }

    /**
     * Set ACL resource to use to check permissions on records.
     * @param  string[Zend_Acl_Resource_Interface|null $resource
     * @throws Fab_View_Exception
     * @return self
     */
    public function setResource($resource = null)
    {
        if (null === $resource || is_string($resource) ||
            $resource instanceof Zend_Acl_Resource_Interface) {
            $this->_resource = $resource;
        } else {
            $e = new Fab_View_Exception(sprintf(
                '$resource must be a string, null, or an instance of '
                .  'Zend_Acl_Resource_Interface; %s given',
                gettype($resource)
            ));
            $e->setView($this->view);
            throw $e;
        }

        return $this;
    }

    /**
     * Get ACL resource to use to check permissions on records.
     * @return string|Zend_Acl_Resource_Interface|null
     */
    public function getResource()
    {
        return $this->_resource;
    }

    /**
     * Set default ACL to use if another ACL is not explicitly set.
     * @param  Zend_Acl|null $acl
     * @return void
     */
    public static function setDefaultAcl(Zend_Acl $acl = null)
    {
        self::$_defaultAcl = $acl;
    }

    /**
     * Set default ACL role to use if not explicitly set.
     * @param  string|Zend_Acl_Role_Interface|null $role
     * @throws Fab_View_Exception
     * @return void
     */
    public static function setDefaultRole($role = null)
    {
        if (null === $role || is_string($role) ||
            $role instanceof Zend_Acl_Role_Interface) {
            self::$_defaultRole = $role;
        } else {
            // require_once 'Zend/View/Exception.php';
            throw new Fab_View_Exception(
                '$role must be null|string|Zend_Acl_Role_Interface'
            );
        }
    }

}
