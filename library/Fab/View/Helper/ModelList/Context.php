<?php

class Fab_View_Helper_ModelList_Context
{
    /** @var Zend_View_Interface */
    protected $_view;
    
    /** @var bool */
    protected $_useAcl = true;

    /** @var Zend_Acl */
    protected $_acl;

    /** @var string|Zend_Acl_Role_Interface */
    protected $_role;

    /** @var string|Zend_Acl_Resource_Interface */
    protected $_resource;
    
    /** @var bool */
    protected $_useRecordResource = true;
    
    /** @var array */
    protected $_adapters = array();
    
    /** @var array */
    protected $_decorators = array();
    
    /** @var string */
    protected $_idParamName = 'id';
    
    /** @var string */
    protected $_idParamField = null;
    
    /** @var string */
    protected $_pageParamName = 'page';
    
    /** @var string */
    protected $_sortParamName = 'sort';
    
    /**
     * Construct a new context with the given options.
     */
    public function __construct($options = array())
    {
        $this->setOptions($options);
    }
    
    /**
     * Set options for this context.
     * @param array options
     * @return self
     */
    public function setOptions($options = array())
    {
        if (isset($options['useAcl']))
            $this->setUseAcl($options['useAcl']);
        if (isset($options['acl']))
            $this->setAcl($options['acl']);
        if (isset($options['role']))
            $this->setRole($options['role']);
        if (isset($options['resource']))
            $this->setResource($options['resource']);
        if (isset($options['useRecordResource']))
            $this->setUseRecordResource($options['useRecordResource']);
        if (isset($options['adapters']))
            $this->addAdapters($options['adapters']);
        if (isset($options['decorators']))
            $this->addDecorators($options['decorators']);
        if (isset($options['idParamName']))
            $this->setIdParamName($options['idParamName']);
        if (isset($options['idParamField']))
            $this->setIdParamField($options['idParamField']);
        if (isset($options['pageParamName']))
            $this->setPageParamName($options['pageParamName']);
        if (isset($options['sortParamName']))
            $this->setSortParamName($options['sortParamName']);
        return $this;
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

        if (!$this->getUseRecordResource() || (!is_string($resource) && !($resource instanceof Zend_Acl_Resource_Interface)))
            $resource = $this->getResource();

        if ($resource === null && $privilege === null)
            return true;
        else
            return $acl->isAllowed($this->getRole(), $resource, $privilege);
    }
    
    /**
     * Get global record action parameters suitable for use with the URL view helper.
     * @param array $params
     * @return array
     */
    public function getGlobalRecordActionParams($params = array())
    {
        $pageParamName = $this->getPageParamName();
        if (empty($pageParamName))
            $pageParamName = 'page';
        
        $sortParamName = $this->getSortParamName();
        if (empty($sortParamName))
            $sortParamName = 'sort';
        
        return array_merge($params, array($pageParamName => null, $sortParamName => null));
    }
    
    /**
     * Get record action parameters suitable for use with the URL view helper.
     * @param mixed $record
     * @param array $params
     * @return array
     */
    public function getRecordActionParams($record, $params = array())
    {
        $idParamName = $this->getIdParamName();
        if (empty($idParamName))
            $idParamName = 'id';
        
        $idParamField = $this->getIdParamField();
        if (empty($idParamField))
            $idParamValue = $record->identifier();
        else
            $idParamValue = $record->$idParamField;
        
        $pageParamName = $this->getPageParamName();
        if (empty($pageParamName))
            $pageParamName = 'page';
        
        $sortParamName = $this->getSortParamName();
        if (empty($sortParamName))
            $sortParamName = 'sort';
        
        return array_merge($params, array($idParamName => $idParamValue, $pageParamName => null, $sortParamName => null));
    }
    
    /**
     * Get the view this context is related to.
     * @return Zend_View_Interface
     */
    public function getView()
    {
        return $this->_view;
    }
    
    /**
     * Set the view this context is related to.
     * @param Zend_View_Interface $view
     * @return self
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->_view = $view;
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
     * Get ACL to use to check permissions on records.
     * @return Zend_Acl|null
     */
    public function getAcl()
    {
        return $this->_acl;
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
     * Get ACL role to use to check permissions on records.
     * @return string|Zend_Acl_Role_Interface|null
     */
    public function getRole()
    {
        return $this->_role;
    }

    /**
     * Set ACL role to use to check permissions on records.
     * @param  string[Zend_Acl_Role_Interface|null $role
     * @throws Fab_View_Exception
     * @return self
     */
    public function setRole($role = null)
    {
        $this->_role = $role;
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
     * Set ACL resource to use to check permissions on records.
     * @param  string[Zend_Acl_Resource_Interface|null $resource
     * @throws Fab_View_Exception
     * @return self
     */
    public function setResource($resource = null)
    {
        $this->_resource = $resource;
        return $this;
    }
    
    /**
     * Get whether each record's ACL resource should be used (if available).
     * @return bool
     */
    public function getUseRecordResource()
    {
        return $this->_useRecordResource;
    }

    /**
     * Set whether each record's ACL resource should be used (if available).
     * @param  bool $useRecordResource
     * @return self
     */
    public function setUseRecordResource($useRecordResource = true)
    {
        $this->_useRecordResource = (bool) $useRecordResource;
        return $this;
    }
    
    /**
     * Get all registered model adapters.
     * @return array
     */
    public function getAdapters()
    {
        return $this->_adapters;
    }
    
    /**
     * Get a model adapter instance.
     * @param string $modelName 
     * @return Fab_View_Helper_ModelList_Adapter_Interface
     */
    public function getAdapter($modelName)
    {
        foreach ($this->getAdapters() as $modelClass => $adapterClass) {
            if (is_subclass_of($modelName, $modelClass)) {
                return new $adapterClass($modelName);
            }
        }
        throw new Fab_View_Exception("No adapter found for model '$modelName'");
    }
    
    /**
     * Set model adapters.
     * @param array $adapters
     * @return self
     */
    public function setAdapters($adapters)
    {
        $this->_adapters = $adapters;
        return $this;
    }
    
    /**
     * Add model adapters to the current list of adapters.
     * Existing adapters with the same model class will be overwritten.
     * @param array $adapters
     * @return self
     */
    public function addAdapters($adapters)
    {
        $this->_adapters = array_merge($this->_adapters, $adapters);
        return $this;
    }
    
    /**
     * Get all registered field decorators.
     * @return array
     */
    public function getDecorators()
    {
        return $this->_decorators;
    }
    
    /**
     * Get a field decorator instance.
     * @param string $fieldName
     * @param mixed $fieldValue
     * @return Fab_View_Helper_ModelList_Decorator_Interface
     */
    public function getDecorator($fieldName, $fieldValue)
    {
        // TODO: support having several decorators
        
        // TODO: translate 'object' type to class name
        $fieldType = gettype($fieldValue);
        
        // Search for the most specific decorator, starting with a valid default
        $decoratorDef = array('decorator' => 'Fab_View_Helper_ModelList_Decorator_Escape');
        $lastScore = -1;
        foreach ($this->getDecorators() as $d) {
            if ((!isset($d['fieldName']) || $d['fieldName'] == $fieldName) &&
                (!isset($d['fieldType']) || $d['fieldType'] == $fieldType)) {
                $score = 0;
                $score |= (isset($d['fieldName']) ? 2 : 0);
                $score |= (isset($d['fieldType']) ? 1 : 0);
                if ($score > $lastScore) {
                    $decoratorDef = $d;
                    $lastScore = $score;
                }
            }
        }
        
        // Instantiate the decorator if necessary
        if (is_string($decoratorDef['decorator'])) {
            $decorator = new $decoratorDef['decorator']();
        } else if ($decoratorDef['decorator'] instanceof Fab_View_Helper_ModelList_Decorator_Interface) {
            $decorator = $decoratorDef['decorator'];
        } else {
            throw new Fab_View_Exception("Invalid decorator specified for fieldName='" . $decoratorDef['fieldName'] .
                    "' and fieldType='" . $decoratorDef['fieldType'] . "'");
        }
        
        // Configure the decorator
        $decorator->setView($this->getView());
        $decorator->setContext($this);
        if (isset($decoratorDef['options']))
            $decorator->setOptions($decoratorDef['options']);
        
        return $decorator;
    }
    
    /**
     * Set field decorators.
     * @param array $decorators
     * @return self
     */
    public function setDecorators($decorators)
    {
        $this->_decorators = $decorators;
        return $this;
    }
    
    /**
     * Add field decorators to the current list of decorators.
     * Existing decorators with the same fieldName/fieldType couple will be overwritten.
     * @param array $decorators
     * @return self
     */
    public function addDecorators($decorators)
    {
        $this->_decorators = array_merge($decorators, $this->_decorators);
        return $this;
    }
    
    /**
     * Get the record identifier URL parameter name.
     * @return string
     */
    public function getIdParamName()
    {
        return $this->_idParamName;
    }

    /**
     * Set the record identifier URL parameter name.
     * @param string $idParamName
     * @return self
     */
    public function setIdParamName($idParamName)
    {
        $this->_idParamName = $idParamName;
        return $this;
    }

    /**
     * Get the record identifier URL parameter value field.
     * @return string
     */
    public function getIdParamField()
    {
        return $this->_idParamField;
    }

    /**
     * Set the record identifier URL parameter value field.
     * @param string $idParamField
     * @return self
     */
    public function setIdParamField($idParamField)
    {
        $this->_idParamField = $idParamField;
        return $this;
    }
    
    /**
     * Get the page number URL parameter name.
     * @return string
     */
    public function getPageParamName()
    {
        return $this->_pageParamName;
    }
    
    /**
     * Set the page number URL parameter name.
     * @param string $pageParamName
     * @return self
     */
    public function setPageParamName($pageParamName)
    {
        $this->_pageParamName = $pageParamName;
        return $this;
    }

    /**
     * Get the sort direction URL parameter name.
     * @return string
     */
    public function getSortParamName()
    {
        return $this->_sortParamName;
    }

   /**
     * Set the sort direction URL parameter name.
     * @param string $sortParamName
     * @return self
     */
    public function setSortParamName($sortParamName)
    {
        $this->_sortParamName = $sortParamName;
        return $this;
    }
}
