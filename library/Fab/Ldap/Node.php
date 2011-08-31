<?php

abstract class Fab_Ldap_Node extends Zend_Ldap_Node
{
    /** @var string */
    protected static $_subDn = '';

    /** @var string */
    protected static $_rdnAttribute = 'cn';

    /** @var array */
    protected static $_objectClass = array();

    /** @var string|Zend_Ldap_Filter */
    protected static $_filter = 'objectClass=*';

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
     * Get the configuration for this LDAP model.
     * @return Zend_Config|array
     */
    protected static abstract function _getConfig();

    /**
     * Get the subtree distinguished name.
     * This denotes the subtree under which models should be searched and created.
     * @param boolean $full whether to return only the subDn or the full DN (with the baseDn appended)
     * @return string
     */
    protected static function _getSubDn($full = true)
    {
        $dn = static::$_subDn;
        if ($full === true) {
            $baseDn = static::_getConfig()->baseDn;
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
        return static::$_rdnAttribute;
    }

    /**
     * Get the object class(es) for this model.
     * @return array
     */
    protected static function _getObjectClass()
    {
        return static::$_objectClass;
    }

    /**
     * Get the entry LDAP filter for this model.
     * @return string|Zend_Ldap_Filter
     */
    protected static function _getFilter()
    {
        return static::$_filter;
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
     * Return a unique identifier for this object.
     * @return string
     */
    public function identifier()
    {
        return (string) $this->getAttribute(static::_getRdnAttribute(), 0);
    }

    /**
     * Return a string representation of this object.
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getAttribute(static::_getRdnAttribute(), 0);
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
            $ldap = new Zend_Ldap(static::_getConfig());
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
            if (preg_match('/^\d{4}[\d\+\-Z\.]*$/', $attr)) {
                $attr = new Zend_Date(Zend_Ldap_Converter::fromLdapDateTime($attr, true)->getTimestamp());
            }
        } else if (is_array($attr)) {
            $mapped = array();
            foreach ($attr as $value) {
                if (preg_match('/^\d{4}[\d\+\-Z\.]*$/', $value)) {
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
     * Factory method to create an attached Fab_Ldap_Node for a given DN.
     * This will additionally cache the instantiated model for the duration of
     * the PHP engine execution, to avoid superfluous LDAP queries.
     * @param  string|array|Zend_Ldap_Dn $dn
     * @param  Zend_Ldap                 $ldap
     * @return Fab_Ldap_Node|null
     * @throws Zend_Ldap_Exception
     */
    public static function fromLdap($dn, Zend_Ldap $ldap)
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

        $data = $ldap->getEntry($dn, array('*', '+'), true);
        if ($data === null) {
            return null;
        }

        $entry = new static($dn, $data, true, $ldap);
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
     * @param string|Zend_Ldap_Filter $filter
     * @return Fab_Ldap_Node_Collection
     */
    public static function findAll($filter = null)
    {
        // Create the LDAP search filter
        $modelFilter = static::_getFilter();
        if ($modelFilter !== null && $filter !== null) {
            $searchFilter = Zend_Ldap_Filter::andFilter($modelFilter, $filter);
        } else if ($modelFilter !== null) {
            $searchFilter = Zend_Ldap_Filter::string($modelFilter);
        } else if ($filter !== null) {
            $searchFilter = Zend_Ldap_Filter::string($filter);
        } else {
            $searchFilter = Zend_Ldap_Filter::any('objectClass');
        }

        // Instantiate the LDAP object from configuration
        $ldap = new Zend_Ldap(static::_getConfig());

        // Perform the search and return a collection of models sorted by the RDN attribute
        $collection = $ldap->search($searchFilter, static::_getSubDn(), Zend_Ldap::SEARCH_SCOPE_SUB, array('*', '+'), static::_getRdnAttribute(), 'Fab_Ldap_Node_Collection');
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
        $ldap = new Zend_Ldap(static::_getConfig());

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
