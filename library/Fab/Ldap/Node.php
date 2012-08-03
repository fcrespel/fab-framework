<?php

abstract class Fab_Ldap_Node extends Zend_Ldap_Node
{
    /** @var boolean */
    protected $_isLazy = false;

    /** @var array */
    protected static $_arrayAttributes = array();

    /** @var array */
    protected static $_modelMap = array();

    /** @var array */
    protected static $_fieldLabels = array(
        'dn'            => 'Distinguished name'
    );

    /** @var array */
    private static $_modelCache = array();

    /**
     * Get the LDAP configuration.
     * @return Zend_Config|array
     */
    protected static abstract function _getLdapConfig();
    
    /**
     * Get the configuration for this model.
     * @return Zend_Config|array
     */
    protected static abstract function _getModelConfig();

    /**
     * Get the subtree distinguished name.
     * This denotes the subtree under which models should be searched and created.
     * @param boolean $full whether to return only the subDn or the full DN (with the baseDn appended)
     * @return string
     */
    protected static function _getSubDn($full = true)
    {
        $modelConfig = static::_getModelConfig();
        if ($modelConfig instanceof Zend_Config)
            $modelConfig = $modelConfig->toArray();
        
        $ldapConfig = static::_getLdapConfig();
        if ($ldapConfig instanceof Zend_Config)
            $ldapConfig = $ldapConfig->toArray();
        
        $dn = $modelConfig['subDn'];
        if ($full === true) {
            $baseDn = $ldapConfig['baseDn'];
            if (!empty($baseDn))
                $dn .= ',' . $baseDn;
        }
        return $dn;
    }

    /**
     * Get the RDN attribute name.
     * This attribute is supposed to uniquely identify entries.
     * @return string
     */
    protected static function _getRdnAttribute()
    {
        $modelConfig = static::_getModelConfig();
        if ($modelConfig instanceof Zend_Config)
            $modelConfig = $modelConfig->toArray();
        
        return $modelConfig['rdnAttribute'];
    }

    /**
     * Get the object class(es) for this model.
     * @return array
     */
    protected static function _getObjectClass()
    {
        $modelConfig = static::_getModelConfig();
        if ($modelConfig instanceof Zend_Config)
            $modelConfig = $modelConfig->toArray();
        
        return $modelConfig['objectClass'];
    }

    /**
     * Get the entry LDAP filter for this model.
     * @return string|Zend_Ldap_Filter
     */
    protected static function _getFilter()
    {
        $modelConfig = static::_getModelConfig();
        if ($modelConfig instanceof Zend_Config)
            $modelConfig = $modelConfig->toArray();
        
        return $modelConfig['filter'];
    }

    /**
     * Get attributes that should always be returned as an array.
     * @return array
     */
    protected static function _getArrayAttributes()
    {
        return static::$_arrayAttributes;
    }

    /**
     * Get a map between attributes and models.
     * This is used in get/setAttribute to automatically convert from DNs to
     * models and from models to DNs.
     * @return array
     */
    protected static function _getModelMap()
    {
        return static::$_modelMap;
    }

    /**
     * Get field names for this model.
     * @return array
     */
    public static function getFieldNames()
    {
        return array_keys(static::$_fieldLabels);
    }

    /**
     * Get user-friendly labels associated with field names.
     * @return array
     */
    public static function getFieldLabels()
    {
        return static::$_fieldLabels;
    }
    
    /**
     * Check if data should be loaded from LDAP, and if so does so.
     */
    protected function _lazyLoad()
    {
        if ($this->_isLazy === true) {
            $this->reload(); // This also removes the lazy flag
        }
    }
    
    /**
     * Checks if the attribute can be set and sets it accordingly.
     *
     * @param  string  $name
     * @param  mixed   $value
     * @param  boolean $append
     * @throws Zend_Ldap_Exception
     */
    protected function _setAttribute($name, $value, $append)
    {
        $this->_lazyLoad();
        return parent::_setAttribute($name, $value, $append);
    }
    
    /**
     * Checks if the attribute can be set and sets it accordingly.
     *
     * @param  string        $name
     * @param  integer|array $value
     * @param  boolean       $utc
     * @param  boolean       $append
     * @throws Zend_Ldap_Exception
     */
    protected function _setDateTimeAttribute($name, $value, $utc, $append)
    {
        $this->_lazyLoad();
        return parent::_setDateTimeAttribute($name, $value, $utc, $append);
    }
    
    /**
     * Constructor.
     *
     * Constructor is protected to enforce the use of factory methods.
     *
     * @param  Zend_Ldap_Dn $dn
     * @param  array        $data
     * @param  boolean      $fromDataSource
     * @param  Zend_Ldap    $ldap
     * @param  boolean      $lazy
     * @throws Zend_Ldap_Exception
     */
    public function __construct(Zend_Ldap_Dn $dn, array $data, $fromDataSource, Zend_Ldap $ldap = null, $lazy = false)
    {
        $this->_isLazy = $fromDataSource && $lazy ? true : false;
        parent::__construct($dn, $data, $fromDataSource, $ldap);
    }
    
    /**
     * Return a unique identifier for this object.
     * @return string
     */
    public function identifier()
    {
        return current($this->getRdnArray());
    }

    /**
     * Return a string representation of this object.
     * @return string
     */
    public function __toString()
    {
        return current($this->getRdnArray());
    }
    
    /**
     * Reload node attributes from LDAP.
     *
     * This is an online method.
     *
     * @param  Zend_Ldap $ldap
     * @return Zend_Ldap_Node Provides a fluid interface
     * @throws Zend_Ldap_Exception
     */
    public function reload(Zend_Ldap $ldap = null) {
        $this->_isLazy = false;
        return parent::reload($ldap);
    }
    
    /**
     * Gets node attributes.
     *
     * The array contains all attributes in its internal format (no conversion).
     *
     * This is an offline method.
     *
     * @param  boolean $includeSystemAttributes
     * @return array
     */
    public function getData($includeSystemAttributes = true)
    {
        $this->_lazyLoad();
        return parent::getData($includeSystemAttributes);
    }

    /**
     * Gets a LDAP attribute.
     * If the attribute only counts 1 element, this element is returned rather
     * than an array. Dates are automatically converted to Zend_Date.
     * Additionally, DNs are automatically mapped to models if the attribute
     * is defined in the modelMap.
     * This is an offline method.
     * @param  string  $name
     * @param  integer $index
     * @return mixed
     * @throws Zend_Ldap_Exception
     */
    public function getAttribute($name, $index = null)
    {
        $this->_lazyLoad();
        
        $attr = parent::getAttribute($name, $index);

        // Flatten 1-element array
        $arrAttr = static::_getArrayAttributes();
        if (is_array($attr) && !in_array($name, $arrAttr)) {
            if (count($attr) == 0) return null;
            else if (count($attr) == 1) $attr = $attr[0];
        }

        // Map DNs to models
        $modelMap = static::_getModelMap();
        if (array_key_exists($name, $modelMap)) {
            $model = $modelMap[$name];
            $ldap = new Zend_Ldap(static::_getLdapConfig());
            if (is_string($attr) && Zend_Ldap_Dn::checkDn($attr)) {
                $attr = $model::fromLdap($attr, $ldap);
            } else if (is_array($attr)) {
                $mapped = array();
                foreach ($attr as $value) {
                    if (Zend_Ldap_Dn::checkDn($value))
                        $mapped[] = $model::fromLdap($value, $ldap);
                    else
                        $mapped[] = $value;
                }
                $attr = $mapped;
            }
        }

        // Convert date/time to Zend_Date
        if (is_string($attr)) {
            if (!is_numeric($attr) && preg_match('/^\d{4}[\d\+\-Z\.]*$/', $attr)) {
                $attr = new Zend_Date(Zend_Ldap_Converter::fromLdapDateTime($attr, true)->getTimestamp());
            }
        } else if (is_array($attr)) {
            $mapped = array();
            foreach ($attr as $value) {
                if (!is_numeric($value) && preg_match('/^\d{4}[\d\+\-Z\.]*$/', $value)) {
                    $mapped[] = new Zend_Date(Zend_Ldap_Converter::fromLdapDateTime($value, true)->getTimestamp());
                } else {
                    $mapped[] = $value;
                }
            }
            $attr = $mapped;
        }

        return $attr;
    }

    /**
     * Sets a LDAP attribute.
     * Models are automatically converted to DNs if the attribute is defined
     * in the modelMap.
     * This is an offline method.
     * @param  string $name
     * @param  mixed  $value
     * @return Zend_Ldap_Node Provides a fluid interface
     * @throws Zend_Ldap_Exception
     */
    public function setAttribute($name, $value)
    {
        // Force value to be an array (easier for conversion)
        if (!is_array($value))
            $value = array($value);

        // Check if the attribute can be mapped to a model
        $model = null;
        $modelMap = static::_getModelMap();
        if (array_key_exists($name, $modelMap))
            $model = $modelMap[$name];

        // Map models to DNs
        $mapped = array();
        foreach ($value as $v) {
            if (is_object($v) && $v instanceof Zend_Ldap_Dn)
                $mapped[] = $v->toString();
            else if (is_object($v) && isset($model) && $v instanceof $model)
                $mapped[] = $v->getDnString();
            else
                $mapped[] = $v;
        }

        return parent::setAttribute($name, $mapped);
    }
    
    /**
     * Sets a LDAP password.
     *
     * @param  string $password
     * @param  string $hashType
     * @param  string $attribName
     * @return Zend_Ldap_Node Provides a fluid interface
     * @throws Zend_Ldap_Exception
     */
    public function setPasswordAttribute($password, $hashType = Zend_Ldap_Attribute::PASSWORD_HASH_MD5, $attribName = 'userPassword')
    {
        $this->_lazyLoad();
        return parent::setPasswordAttribute($password, $hashType, $attribName);
    }
    
    /**
     * Sets a LDAP attribute from a template (including %attribute% placeholders).
     * @param  string $name
     * @param  string $template
     * @return string
     */
    public function setTemplateAttribute($name, $template)
    {
        $value = $template;
        if (preg_match_all('/%(\w+)%/', $template, $matches)) {
            foreach ($matches[1] as $match) {
                $value = str_replace('%' . $match . '%', $this->getAttribute($match), $value);
            }
        }
        return $this->setAttribute($name, trim($value));
    }
    
    /**
     * Removes duplicate values from a LDAP attribute
     *
     * @param  string $attribName
     * @return void
     */
    public function removeDuplicatesFromAttribute($attribName)
    {
        $this->_lazyLoad();
        return parent::removeDuplicatesFromAttribute($attribName);
    }

    /**
     * Remove given values from a LDAP attribute
     *
     * @param  string      $attribName
     * @param  mixed|array $value
     * @return void
     */
    public function removeFromAttribute($attribName, $value)
    {
        $this->_lazyLoad();
        parent::removeFromAttribute($attribName, $value);
    }
    
    /**
     * Checks whether a given attribute exists.
     *
     * If $emptyExists is false empty attributes (containing only array()) are
     * treated as non-existent returning false.
     * If $emptyExists is true empty attributes are treated as existent returning
     * true. In this case method returns false only if the attribute name is
     * missing in the key-collection.
     *
     * @param  string  $name
     * @param  boolean $emptyExists
     * @return boolean
     */
    public function existsAttribute($name, $emptyExists = false)
    {
        $this->_lazyLoad();
        return parent::existsAttribute($name, $emptyExists);
    }
    
    /**
     * Checks if the given value(s) exist in the attribute
     *
     * @param  string      $attribName
     * @param  mixed|array $value
     * @return boolean
     */
    public function attributeHasValue($attribName, $value)
    {
        $this->_lazyLoad();
        return parent::attributeHasValue($attribName, $value);
    }
    
    /**
     * Returns the number of attributes in node.
     * Implements Countable
     *
     * @return int
     */
    public function count()
    {
        $this->_lazyLoad();
        return parent::count();
    }
    
    /**
     * Returns an array representation of the current node
     *
     * @param  boolean $includeSystemAttributes
     * @return array
     */
    public function toArray($includeSystemAttributes = true)
    {
        if ($this->_isLazy) {
            return array('dn' => $this->getDnString());
        } else {
            $attributes = parent::toArray($includeSystemAttributes);
            foreach ($attributes as $attrName => $attrValue) {
                if (is_object($attrValue) && $attrValue instanceof Zend_Ldap_Node) {
                    $attributes[$attrName] = $attrValue->toArray();
                } else if (is_array($attrValue)) {
                    foreach ($attrValue as $index => $value) {
                        if (is_object($value) && $value instanceof Zend_Ldap_Node) {
                            $attrValue[$index] = $value->toArray();
                        }
                    }
                    $attributes[$attrName] = $attrValue;
                }
            }
            return $attributes;
        }
    }

    /**
     * Factory method to create an attached Fab_Ldap_Node for a given DN.
     * By default, the node's data is not loaded from LDAP and will be lazily
     * loaded as required, when calling methods such as getAttribute().
     * This will additionally cache the instantiated model for the duration of
     * the PHP engine execution, to avoid superfluous LDAP queries.
     * @param  string|array|Zend_Ldap_Dn $dn
     * @param  Zend_Ldap                 $ldap
     * @param  boolean                   $lazy
     * @return Fab_Ldap_Node|null
     * @throws Zend_Ldap_Exception
     */
    public static function fromLdap($dn, Zend_Ldap $ldap, $lazy = true)
    {
        if (is_string($dn) || is_array($dn)) {
            $dn = Zend_Ldap_Dn::factory($dn);
        } else if ($dn instanceof Zend_Ldap_Dn) {
            $dn = clone $dn;
        } else {
            throw new Fab_Ldap_Exception(null, '$dn is of a wrong data type.');
        }

        $dnString = $dn->toString();
        if (isset(self::$_modelCache[$dnString])) {
            return self::$_modelCache[$dnString];
        }

        $data = array();
        if ($lazy === false) {
            $data = $ldap->getEntry($dn, array('*', '+'), true);
            if ($data === null) {
                return null;
            }
        }

        $entry = new static($dn, $data, true, $ldap, $lazy);
        self::$_modelCache[$dnString] = $entry;
        return $entry;
    }

    /**
     * Factory method to create a detached Fab_Ldap_Node from array data.
     * @param  array   $data
     * @param  boolean $fromDataSource
     * @return Fab_Ldap_Node
     * @throws Zend_Ldap_Exception
     */
    public static function fromArray(array $data, $fromDataSource = false)
    {
        if (!array_key_exists('dn', $data)) {
            throw new Fab_Ldap_Exception(null, '\'dn\' key is missing in array.');
        }
        if (is_string($data['dn']) || is_array($data['dn'])) {
            $dn = Zend_Ldap_Dn::factory($data['dn']);
        } else if ($data['dn'] instanceof Zend_Ldap_Dn) {
            $dn = clone $data['dn'];
        } else {
            throw new Fab_Ldap_Exception(null, '\'dn\' key is of a wrong data type.');
        }
        $fromDataSource = ($fromDataSource === true) ? true : false;
        $new = new static($dn, $data, $fromDataSource, null);
        $new->_ensureRdnAttributeValues();
        return $new;
    }

    /**
     * Find an entry by its RDN attribute.
     * @param  string $value
     * @return self
     */
    public static function find($value)
    {
        $rdnAttr = static::_getRdnAttribute();
        $results = static::findAll(Zend_Ldap_Filter::equals($rdnAttr, $value));
        if ($results->count() == 0) {
            throw new Fab_Ldap_Exception(null, "No match for $rdnAttr='$value' in directory", $code);
        } else {
            return $results->getFirst();
        }
    }

    /**
     * Find all models matching a filter, if any.
     * @param string|Zend_Ldap_Filter_Abstract $filter
     * @param string $sort
     * @return Fab_Ldap_Node_Collection
     */
    public static function findAll($filter = null, $sort = null)
    {
        // Create the LDAP search filter
        $modelFilter = static::_getFilter();
        if ($modelFilter !== null && $filter !== null) {
            $searchFilter = Zend_Ldap_Filter::andFilter($modelFilter, $filter);
        } else if ($modelFilter !== null) {
            $searchFilter = Zend_Ldap_Filter::string($modelFilter);
        } else if ($filter !== null && is_string($filter)) {
            $searchFilter = Zend_Ldap_Filter::string($filter);
        } else if ($filter !== null && $filter instanceof Zend_Ldap_Filter_Abstract) {
            $searchFilter = $filter;
        } else {
            $searchFilter = Zend_Ldap_Filter::any('objectClass');
        }
        
        // Set the default sort field to the RDN attribute
        if ($sort === null)
            $sort = static::_getRdnAttribute();

        // Instantiate the LDAP object from configuration
        $ldap = new Zend_Ldap(static::_getLdapConfig());

        // Perform the search and return a collection of models sorted by the RDN attribute
        $collection = $ldap->search($searchFilter, static::_getSubDn(), Zend_Ldap::SEARCH_SCOPE_SUB, array('*', '+'), $sort, 'Fab_Ldap_Node_Collection');
        $collection->setModelClass(get_called_class());
        return $collection;
    }


    /**
     * Create and attach a new node.
     * If the $dn argument is a simple string and not a real DN, one will be
     * constructed using the subDn of the model.
     * The $objectClass argument, when empty or not specified, will use the defaults from the model.
     * @param string|array|Zend_Ldap_Dn $dn full DN or RDN value for the new object
     * @param array $objectClass object class(es)
     * @return self
     */
    public static function create($dn, array $objectClass = array())
    {
        // Check the given DN and create one if necessary
        $dn = static::getDnForRdn($dn);

        // Ensure default object class(es) are used if none was specified
        if (count($objectClass) == 0) {
            $objectClass = static::_getObjectClass();
        }

        // Instantiate the LDAP object from configuration
        $ldap = new Zend_Ldap(static::_getLdapConfig());

        // Create and attach the new node
        if (is_string($dn) || is_array($dn)) {
            $dn = Zend_Ldap_Dn::factory($dn);
        } else if ($dn instanceof Zend_Ldap_Dn) {
            $dn = clone $dn;
        } else {
            throw new Fab_Ldap_Exception(null, '$dn is of a wrong data type.');
        }
        $new = new static($dn, array(), false, $ldap);
        $new->_ensureRdnAttributeValues();
        $new->setAttribute('objectClass', $objectClass);
        return $new;
    }

    /**
     * Get the full DN for a given RDN value.
     * @param Zend_Ldap_Dn|string $rdn
     * @return Zend_Ldap_Dn|string
     */
    public static function getDnForRdn($rdn)
    {
        if (Zend_Ldap_Dn::checkDn($rdn)) {
            $dn = $rdn;
        } else if (is_string($rdn)) {
            $dn = static::_getRdnAttribute() . '=' . $rdn . ',' . static::_getSubDn();
            $dn = Zend_Ldap_Dn::factory($dn);
        } else {
            throw new Fab_Ldap_Exception(null, "Unexpected RDN value = '$rdn' when converting to DN");
        }
        return $dn;
    }

}
