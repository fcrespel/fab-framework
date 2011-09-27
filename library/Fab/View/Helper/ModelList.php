<?php

class Fab_View_Helper_ModelList extends Zend_View_Helper_Abstract
{
    /** @var array */
    protected static $_defaultOptions = array(
        'pageParamName'         => 'page',
        'sortParamName'         => 'sort',
        'itemsPerPage'          => 30,
        'paginationStyle'       => 'Sliding',
        'paginationScript'      => 'pagination.phtml',
        'listScript'            => 'list.phtml',
        'addRecordAction'       => null,
        'singleRecordActions'   => array(),
    );
    
    /** @var Zend_Acl */
    protected static $_defaultAcl;

    /** @var string|Zend_Acl_Role_Interface */
    protected static $_defaultRole;

    /** @var array */
    protected static $_defaultAdapters = array(
        'Doctrine_Record'   => 'Fab_View_Helper_ModelList_Adapter_Doctrine',
        'Fab_Ldap_Node'     => 'Fab_View_Helper_ModelList_Adapter_Ldap',
    );
    
    /** @var array */
    protected static $_defaultDecorators = array(
        array(
            'fieldType' => 'array',
            'decorator' => 'Fab_View_Helper_ModelList_Decorator_Array',
        ),
        array(
            'fieldType' => 'boolean',
            'decorator' => 'Fab_View_Helper_ModelList_Decorator_Boolean',
        ),
        array(
            'fieldType' => 'enum',
            'decorator' => 'Fab_View_Helper_ModelList_Decorator_Enum',
        ),
    );

    /** @var array */
    protected static $_enabledView = array();

    /**
     * Set the View object.
     * @param Zend_View_Interface $view 
     */
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
     * Initialize a new context with defaults and given options.
     * @param array $options
     * @return Fab_View_Helper_ModelList_Context 
     */
    protected function _initContext($options = array())
    {
        $context = new Fab_View_Helper_ModelList_Context();
        $context->setView($this->view)
                ->setAcl(self::getDefaultAcl())
                ->setRole(self::getDefaultRole())
                ->setAdapters(self::getDefaultAdapters())
                ->setDecorators(self::getDefaultDecorators())
                ->setOptions($options);
        return $context;
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
        // Process options
        $context = $this->_initContext($options);
        $options = array_merge(self::$_defaultOptions, $options);
        
        // Process request params
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $pageParam = $request->getParam($options['pageParamName'], 1);
        $sortParam = $request->getParam($options['sortParamName']);
        $sortField = null;
        $sortDirection = 'asc';
        if (preg_match('/^(\w+)(\.[ad])?$/i', $sortParam, $sortMatches)) {
            $sortField = $sortMatches[1];
            if (isset($sortMatches[2]) && !strcasecmp($sortMatches[2], '.d'))
                $sortDirection = 'desc';
        }

        // Get the model-specific adapter
        $adapter = $context->getAdapter($modelName);

        // Configure the paginator
        $paginator = $adapter->getPaginator($query, $sortField, $sortDirection);
        $paginator->setCurrentPageNumber($pageParam);
        $paginator->setItemCountPerPage($options['itemsPerPage']);

        // Get the field names
        if (!isset($options['showFieldNames'])) {
            $fieldNames = $adapter->getFieldNames();
        } else {
            $fieldNames = $options['showFieldNames'];
        }
        
        // Fill the field labels if needed
        foreach ($fieldNames as $fieldName) {
            if (!isset($options['fieldLabels'][$fieldName]))
                $options['fieldLabels'][$fieldName] = $fieldName;
        }

        // Render the partial
        return $this->view->partial($options['listScript'], array(
            'modelName'     => $modelName,
            'fieldNames'    => $fieldNames,
            'paginator'     => $paginator,
            'sortField'     => $sortField,
            'sortDirection' => $sortDirection,
            'options'       => $options,
            'context'       => $context,
        ));
    }
    
    /**
     * Get default ACL to use if another ACL is not explicitly set.
     * @return Zend_Acl|null
     */
    public static function getDefaultAcl()
    {
        return self::$_defaultAcl;
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
     * Get default ACL role to use if not explicitly set.
     * @return string|Zend_Acl_Role_Interface|null
     */
    public static function getDefaultRole()
    {
        return self::$_defaultRole;
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
            throw new Fab_View_Exception(
                '$role must be null|string|Zend_Acl_Role_Interface'
            );
        }
    }
    
    /**
     * Get default model adapters.
     * @return array
     */
    public static function getDefaultAdapters()
    {
        return self::$_defaultAdapters;
    }
    
    /**
     * Set default model adapters.
     * @param array $defaultAdapters 
     */
    public static function setDefaultAdapters($defaultAdapters)
    {
        self::$_defaultAdapters = $defaultAdapters;
    }

    /**
     * Get default field decorators.
     * @return array
     */
    public static function getDefaultDecorators()
    {
        return self::$_defaultDecorators;
    }
    
    /**
     * Set default field decorators.
     * @param array $defaultDecorators 
     */
    public static function setDefaultDecorators($defaultDecorators)
    {
        self::$_defaultDecorators = $defaultDecorators;
    }
}
