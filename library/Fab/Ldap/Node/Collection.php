<?php

class Fab_Ldap_Node_Collection extends Zend_Ldap_Node_Collection
{
    /** @var string */
    protected $_modelClass = 'Zend_Ldap_Node';

    /**
     * Get the name of the model class.
     * @return string
     */
    public function getModelClass()
    {
        return $this->_modelClass;
    }

    /**
     * Set the model class extending Zend_Ldap_Node.
     * @param string $modelClass
     */
    public function setModelClass($modelClass)
    {
        $this->_modelClass = $modelClass;
    }

    /**
     * Creates the data structure for the given entry data
     * @param  array $data
     * @return Zend_Ldap_Node
     */
    protected function _createEntry(array $data)
    {
        $model = $this->getModelClass();
        $node = $model::fromArray($data, true);
        $node->attachLdap($this->_iterator->getLdap());
        return $node;
    }

    /**
     * Get an associative array between each node's identifier and the node itself.
     * @return array 
     */
    public function toArrayAssoc()
    {
        $data = array();
        foreach ($this as $item) {
            $data[(string)$item] = $item;
        }
        return $data;
    }
    
    /**
     * Get all entries as an array
     *
     * @param boolean $deep if true, convert LDAP nodes to arrays as well
     * @return array
     */
    public function toArray($deep = false)
    {
        $data = parent::toArray();
        if ($deep) {
            foreach ($data as $key => $value) {
                if (is_object($value) && $value instanceof Zend_Ldap_Node) {
                    $data[$key] = $value->toArray();
                }
            }
        }
        return $data;
    }

}
