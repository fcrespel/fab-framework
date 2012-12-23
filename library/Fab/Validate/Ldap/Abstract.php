<?php

abstract class Fab_Validate_Ldap_Abstract extends Zend_Validate_Abstract
{
    /**
     * Error constants
     */
    const ERROR_NO_RECORD_FOUND = 'noRecordFound';
    const ERROR_RECORD_FOUND    = 'recordFound';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_NO_RECORD_FOUND => "No record matching '%value%' was found",
        self::ERROR_RECORD_FOUND    => "A record matching '%value%' was found",
    );
    
    /**
     * @var Zend_Ldap
     */
    protected $_ldap = null;
    
    /**
     * @var string 
     */
    protected $_subDn = null;
    
    /**
     * @var string
     */
    protected $_rdnAttribute = null;
    
    /**
     * @var string|Zend_Ldap_Filter
     */
    protected $_filter = null;
    
    /**
     * @var mixed
     */
    protected $_exclude = null;
    
    /**
     * @var Zend_Ldap_Filter
     */
    protected $_query = null;
    
    /**
     * LDAP validator constructor.
     * @param array|Zend_Config $options Options to use for this validator
     */
    public function __construct($options)
    {
        if (!isset($options)) {
            $options = array();
        } else if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
    
    /**
     * Get the LDAP instance to use.
     * @return Zend_Ldap
     */
    public function getLdap()
    {
        return $this->_ldap;
    }

    /**
     * Set the LDAP instance to use/
     * @param Zend_Ldap $ldap
     * @return self
     */
    public function setLdap($ldap)
    {
        $this->_ldap = $ldap;
        return $this;
    }

    /**
     * Get the sub DN to prepend to the base DN.
     * @return string
     */
    public function getSubDn()
    {
        return $this->_subDn;
    }

    /**
     * Set the sub DN to prepend to the base DN.
     * @param string $subDn
     * @return self
     */
    public function setSubDn($subDn)
    {
        $this->_subDn = $subDn;
        return $this;
    }

    /**
     * Get the RDN attribute.
     * @return string
     */
    public function getRdnAttribute()
    {
        return $this->_rdnAttribute;
    }

    /**
     * Set the RDN attribute.
     * @param string $rdnAttr
     * @return self
     */
    public function setRdnAttribute($rdnAttr)
    {
        $this->_rdnAttribute = $rdnAttr;
        return $this;
    }

    /**
     * Get the LDAP filter to use.
     * @return string|Zend_Ldap_Filter
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * Set the LDAP filter to use.
     * @param string|Zend_Ldap_Filter $filter
     * @return self
     */
    public function setFilter($filter)
    {
        $this->_filter = $filter;
        return $this;
    }

    /**
     * Get the value to exclude from the query.
     * @return string
     */
    public function getExclude()
    {
        return $this->_exclude;
    }

    /**
     * Set the value to exclude from the query.
     * @param string $exclude
     * @return self
     */
    public function setExclude($exclude)
    {
        $this->_exclude = $exclude;
        return $this;
    }
    
    /**
     * Get the LDAP query used by the validator.
     * If no query was specified manually, a default one will be generated
     * from the filter, rdnAttr and exclude options.
     * @param string $value
     * @return Zend_Ldap_Filter
     */
    public function getQuery($value)
    {
        if ($this->_query === null) {
            // Build the base search filter
            $searchFilter = $this->getFilter();
            $rdnFilter = Zend_Ldap_Filter::equals($this->getRdnAttribute(), $value);
            if (empty($searchFilter)) {
                $searchFilter = $rdnFilter;
            } else {
                $searchFilter = Zend_Ldap_Filter::andFilter($searchFilter, $rdnFilter);
            }

            // Exclude a specific value if necessary
            $exclude = $this->getExclude();
            if (!empty($exclude)) {
                if (is_array($exclude)) {
                    $excludeFilter = new Zend_Ldap_Filter_Not(Zend_Ldap_Filter::equals($exclude['field'], $exclude['value']));
                    $searchFilter = Zend_Ldap_Filter::andFilter($searchFilter, $excludeFilter);
                } else if (is_object($exclude) && $exclude instanceof Zend_Ldap_Filter) {
                    $searchFilter = Zend_Ldap_Filter::andFilter($searchFilter, $exclude);
                } else if (is_string($exclude)) {
                    $excludeFilter = new Zend_Ldap_Filter_Not(Zend_Ldap_Filter::equals($this->getRdnAttribute(), $exclude));
                    $searchFilter = Zend_Ldap_Filter::andFilter($searchFilter, $excludeFilter);
                }
            }
            
            $this->_query = $searchFilter;
        }
        return $this->_query;
    }

    /**
     * Set the LDAP query used by the validator.
     * @param type $query
     */
    public function setQuery($query)
    {
        $this->_query = $query;
    }

    /**
     * Query a value in the LDAP directory.
     * @param string $value
     * @return Zend_Ldap_Collection|false
     */
    protected function _query($value)
    {
        $ldap = $this->getLdap();
        
        // Get the search base DN
        $searchDn = $ldap->getBaseDn();
        $subDn = $this->getSubDn();
        if (!empty($subDn)) {
            $searchDn = $subDn . ',' . $searchDn;
        }
        
        // Get the search filter
        $searchFilter = $this->getQuery($value);
        
        // Execute the search
        $result = $ldap->search($searchFilter, $searchDn, Zend_Ldap::SEARCH_SCOPE_SUB);
        if ($result->count() == 0) {
            return false;
        } else {
            return $result->getFirst();
        }
    }
}
