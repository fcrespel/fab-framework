<?php

abstract class Fab_Validate_Doctrine_Abstract extends Zend_Validate_Abstract
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
     * @var string
     */
    protected $_table = '';

    /**
     * @var string
     */
    protected $_field = '';

    /**
     * @var mixed
     */
    protected $_exclude = null;
    
    /**
     * Query object to use. can be set, or will be auto-generated
     * @var Doctrine_Query
     */
    protected $_query;
    
    /**
     * Provides basic configuration for use with Zend_Validate_Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     * Exclude can either be a String containing a where clause, or an array with `field` and `value` keys
     * to define the where clause added to the sql.
     *
     * The following option keys are supported:
     * 'table'   => The database table to validate against
     * 'field'   => The field to check for a match
     * 'exclude' => An optional where clause or field/value pair to exclude from the query
     *
     * @param array|Zend_Config $options Options to use for this validator
     */
    public function __construct($options)
    {
        if ($options instanceof Doctrine_Query) {
            $this->setQuery($options);
            return;
        }
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (func_num_args() > 1) {
            $options       = func_get_args();
            $temp['table'] = array_shift($options);
            $temp['field'] = array_shift($options);
            if (!empty($options)) {
                $temp['exclude'] = array_shift($options);
            }

            $options = $temp;
        }

        if (!array_key_exists('table', $options)) {
            // require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('Table option missing!');
        }

        if (!array_key_exists('field', $options)) {
            // require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('Field option missing!');
        }

        if (array_key_exists('exclude', $options)) {
            $this->setExclude($options['exclude']);
        }

        $this->setField($options['field']);
        $this->setTable($options['table']);
    }
    
    /**
     * Returns the set exclude clause
     *
     * @return string|array
     */
    public function getExclude()
    {
        return $this->_exclude;
    }

    /**
     * Sets a new exclude clause
     *
     * @param string|array $exclude
     * @return Fab_Validate_Doctrine_Abstract
     */
    public function setExclude($exclude)
    {
        $this->_exclude = $exclude;
        return $this;
    }
    
    /**
     * Returns the set field
     *
     * @return string|array
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * Sets a new field
     *
     * @param string $field
     * @return Fab_Validate_Doctrine_Abstract
     */
    public function setField($field)
    {
        $this->_field = (string) $field;
        return $this;
    }

    /**
     * Returns the set table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Sets a new table
     *
     * @param string $table
     * @return Fab_Validate_Doctrine_Abstract
     */
    public function setTable($table)
    {
        $this->_table = (string) $table;
        return $this;
    }
    
    /**
     * Sets the query object to be used by the validator
     *
     * @param Doctrine_Query $query
     * @return Fab_Validate_Doctrine_Abstract
     */
    public function setQuery($query)
    {
        if (!$query instanceof Doctrine_Query) {
            throw new Zend_Validate_Exception('Select option must be a valid ' .
                                              'Doctrine_Query object');
        }
        $this->_query = $query;
        return $this;
    }
    
    /**
     * Gets the query object to be used by the validator.
     * If no query object was supplied to the constructor,
     * then it will auto-generate one from the given table
     * and field options.
     *
     * @return Doctrine_Query The Query object which will be used
     */
    public function getQuery()
    {
        if (null === $this->_query) {
            $query = Doctrine_Query::create();
            $query->select($this->_field);
            $query->from($this->_table);
            $query->where($this->_field . ' = ?');
            if ($this->_exclude !== null) {
                if (is_array($this->_exclude)) {
                    $query->andWhere($this->_exclude['field'] . ' != ?', $this->_exclude['value']);
                } else {
                    $query->andWhere($this->_exclude);
                }
            }
            $query->limit(1);
            $this->_query = $query;
        }
        return $this->_query;
    }
    
    /**
     * Run query and returns matches, or false if no matches are found.
     *
     * @param  String $value
     * @return Doctrine_Record when matches are found.
     */
    protected function _query($value)
    {
        $query = $this->getQuery();
        $result = $query->fetchOne(array($value));
        return $result;
    }
}
