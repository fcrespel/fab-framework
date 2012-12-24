<?php

class Fab_View_Helper_ModelList_Decorator_Link extends Fab_View_Helper_ModelList_Decorator_Abstract
{
    /** @var array */
    protected $_urlOptions = array();
    
    /** @var array */
    protected $_fieldMap = array();
    
    /**
     * Render the field.
     * @param  string $fieldName name of the field to decorate
     * @param  string $fieldValue value of the field to decorate
     * @return string
     */
    public function render($fieldName, $fieldValue)
    {
        if (empty($fieldValue))
            return $fieldValue;
        
        $urlOpts = $this->getUrlOptions();
        foreach ($this->getFieldMap() as $paramField => $paramName) {
            if ($paramField == '$this') {
                $urlOpts[$paramName] = strval($fieldValue);
            } else {
                $urlOpts[$paramName] = $fieldValue->$paramField;
            }
        }
        $url = $this->view->url($urlOpts, null, true);
        return '<a href="' . $url . '">' . $this->view->escape((string)$fieldValue) . '</a>';
    }
    
    /**
     * Get URL options to pass to the Url view helper.
     * @return array 
     */
    public function getUrlOptions()
    {
        return $this->_urlOptions;
    }
    
    /**
     * Set the URL options to pass to the Url view helper.
     * @param array $urlOptions
     * @return self
     */
    public function setUrlOptions($urlOptions)
    {
        $this->_urlOptions = $urlOptions;
        return $this;
    }

    /**
     * Get the field-to-url-parameter map.
     * @return array
     */
    public function getFieldMap()
    {
        return $this->_fieldMap;
    }
    
    /**
     * Set the field-to-url-parameter map.
     * @param array $fieldMap 
     */
    public function setFieldMap($fieldMap)
    {
        $this->_fieldMap = $fieldMap;
    }
}
