<?php

abstract class Fab_Controller_CRUD extends Zend_Controller_Action implements Zend_Acl_Resource_Interface
{
    /** @var string model class name */
    protected $_modelClassName;

    /** @var string model display name */
    protected $_modelDisplayName;

    /** @var array models field names to display in the list */
    protected $_modelFieldNames;

    /** @var string model form used for input */
    protected $_modelForm;

    /** @var string ACL resource ID to use when checking permissions */
    protected $_modelAclResource;
    

    /**
     * Get the model class name.
     * @return string
     */
    protected function _getModelClassName()
    {
        return $this->_modelClassName;
    }

    /**
     * Get the model display name.
     * @return string
     */
    protected function _getModelDisplayName()
    {
        return $this->_modelDisplayName;
    }

    /**
     * Get the model field names to display in the list.
     * @return array
     */
    protected function _getModelFieldNames()
    {
        return $this->_modelFieldNames;
    }

    /**
     * Get the model field labels to display in the list.
     * @return array
     */
    protected function _getModelFieldLabels()
    {
        return call_user_func(array($this->_getModelClassName(), 'getFieldLabels'));
    }

    /**
     * Get the model form instance to use for input.
     * @return Zend_Form
     */
    protected function _getModelForm()
    {
        $formClass = $this->_modelForm;
        return new $formClass();
    }

    /**
     * Get the model ACL resource name.
     * @return string
     */
    protected function _getModelAclResource()
    {
        return isset($this->_modelAclResource) ? $this->_modelAclResource : $this->getResourceId();
    }

    /**
     * Get model list options.
     * @return array
     */
    protected function _getModelListOptions()
    {
        $options = array(
            'resource'              => $this->_getModelAclResource(),
            'showFieldNames'        => $this->_getModelFieldNames(),
            'fieldLabels'           => $this->_getModelFieldLabels(),
            'addRecordAction'       => 'input',
            'singleRecordActions'   => array(
                'Edit'      => 'input',
                'Delete'    => 'delete',
            ),
        );
        return $options;
    }

    /**
     * Return the query used for model listing.
     * @return Doctrine_Query
     */
    protected function _getModelListQuery()
    {
        return null;
    }

    /**
     * Get the string identifier of this Resource.
     * @return string
     */
    public function getResourceId()
    {
        $front = $this->getFrontController();
        $request = $this->getRequest();

        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        
        return ($module == $front->getDefaultModule())? $controller : "$module:$controller";
    }

    /**
     * Landing page.
     */
    public function indexAction()
    {
        $this->_forward('list');
    }

    /**
     * List action.
     */
    public function listAction()
    {
        $this->view->modelName = $this->_getModelClassName();
        $this->view->modelListOptions = $this->_getModelListOptions();
        $this->view->modelListQuery = $this->_getModelListQuery();
        $this->view->headTitle($this->_getModelDisplayName() . ' List');
    }

    /**
     * Creation/modification action.
     */
    public function inputAction()
    {
        $this->getHelper('modelCRUD')->handleForm($this->_getModelForm(), 'list');
        $this->view->headTitle($this->_getModelDisplayName() . ($this->getRequest()->getParam('id') ? ' Edition' : ' Creation'));
    }

    /**
     * Deletion action.
     */
    public function deleteAction()
    {
        $this->getHelper('modelCRUD')->handleDelete($this->_getModelClassName(), 'list');
    }
}
