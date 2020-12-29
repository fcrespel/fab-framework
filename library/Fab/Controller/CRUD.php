<?php

abstract class Fab_Controller_CRUD extends Fab_Controller_Action
{
    /** @var string model class name */
    protected $_modelClassName;
    
    /** @var string model id request param name */
    protected $_modelIdParamName = 'id';
    
    /** @var string model id record field name */
    protected $_modelIdFieldName;

    /** @var string model display name */
    protected $_modelDisplayName;

    /** @var array models field names to display in the list */
    protected $_modelFieldNames;

    /** @var string model form used for input */
    protected $_modelInputForm;
    
    /** @var string model form used for filtering results */
    protected $_modelFilterForm;

    /** @var string ACL resource ID to use when checking permissions */
    protected $_modelAclResource;
    
    
    /**
     * Add a message to the FlashMessenger helper.
     * @param type $namespace 'info', 'success', 'warning' or 'error'
     * @param type $message message in which %1$s is the model display name and %2$s is the record as a string
     * @param type $record affected record
     */
    protected function _addFlashMessage($namespace, $message, $record = '')
    {
        $message = sprintf($message, $this->_getModelDisplayName(), (string)$record);
        $this->_helper->flashMessenger->setNamespace($namespace)->addMessage($message);
    }

    /**
     * Get the model class name.
     * @return string
     */
    protected function _getModelClassName()
    {
        return $this->_modelClassName;
    }
    
    /**
     * Get the model ID request param name.
     * @return string
     */
    protected function _getModelIdParamName()
    {
        return $this->_modelIdParamName;
    }

    /**
     * Get the model ID record field name.
     * @return string
     */
    protected function _getModelIdFieldName()
    {
        return $this->_modelIdFieldName;
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
    protected function _getModelInputForm()
    {
        $formClass = $this->_modelInputForm;
        if ($formClass) {
            return new $formClass();
        } else {
            return null;
        }
    }
    
    /**
     * Get the model form instance to use for filtering results.
     * @return Zend_Form
     */
    protected function _getModelFilterForm()
    {
        $formClass = $this->_modelFilterForm;
        if ($formClass) {
            return new $formClass();
        } else {
            return null;
        }
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
            'idParamName'           => $this->_getModelIdParamName(),
            'idParamField'          => $this->_getModelIdFieldName(),
            'resource'              => $this->_getModelAclResource(),
            'showFieldNames'        => $this->_getModelFieldNames(),
            'fieldLabels'           => $this->_getModelFieldLabels(),
            'globalRecordActions'   => array(
                'Add'    => array(
                    'action'    => 'add',
                ),
            ),
            'singleRecordActions'   => array(
                'Edit'   => array(
                    'action'    => 'edit',
                ),
                'Delete' => array(
                    'action'    => 'delete',
                ),
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
     * Get the ModelCRUD action helper.
     * @return Fab_Controller_Action_Helper_ModelCRUD
     */
    protected function _getModelCRUDHelper()
    {
        return $this->getHelper('modelCRUD')
                    ->setRecordIdParam($this->_getModelIdParamName())
                    ->setRecordIdField($this->_getModelIdFieldName());
    }
    
    /**
     * Get the record requested in parameter and check ACL permission.
     * @return Doctrine_Record
     */
    protected function _getRecord($redirectAction = 'list')
    {
        $record = $this->_getModelCRUDHelper()->handleRecord($this->_getModelClassName(), $redirectAction);
        $resource = ($record instanceof Zend_Acl_Resource_Interface) ? $record : $this->_getModelAclResource();
        $resourceId = ($resource instanceof Zend_Acl_Resource_Interface) ? $resource->getResourceId() : $resource;
        $action = $this->getRequest()->getActionName();
        
        $allowed = Zend_Registry::get('acl')->isCurrentRoleAllowed($resource, $action);
        if (!$allowed) {
            throw new Fab_Acl_Permission_Exception("Access denied to resource '$resourceId' with privilege '$action'");
        }
        
        return $record;
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
        $this->view->modelFilterForm = $this->_getModelFilterForm();
        $this->view->headTitle($this->_getModelDisplayName() . ' List');
    }
    
    /**
     * Creation action.
     */
    public function addAction()
    {
        $modelCRUD = $this->_getModelCRUDHelper();
        $redirector = $this->getHelper('redirector');
        $exit = $redirector->getExit();
        $form = $this->_getModelInputForm();
        
        // Ensure no existing record can be edited through this action
        $this->getRequest()->setParam($modelCRUD->getRecordIdParam(), null);
        
        // Handle the form submission
        $redirector->setExit(false);
        $modelCRUD->handleForm($form, 'list');
        $redirector->setExit($exit);
        if ($redirector->getRedirectUrl() !== null) {
            $this->_addFlashMessage('success', '%1$s \'%2$s\' created.', $form->getRecord());
            if ($redirector->getExit()) {
                $redirector->redirectAndExit();
            }
            return $form->getRecord();
        }
        
        $this->view->headTitle($this->_getModelDisplayName() . ' Creation');
    }

    /**
     * Edition action.
     */
    public function editAction()
    {
        $modelCRUD = $this->_getModelCRUDHelper();
        $redirector = $this->getHelper('redirector');
        $exit = $redirector->getExit();
        $form = $this->_getModelInputForm();
        
        // Ensure no new record can be added through this action
        if ($this->getRequest()->getUserParam($modelCRUD->getRecordIdParam()) == null)
            throw new Exception("Missing record id in request params.");
        
        // Handle the form submission
        $redirector->setExit(false);
        $modelCRUD->handleForm($form, 'list');
        $redirector->setExit($exit);
        if ($redirector->getRedirectUrl() !== null) {
            $this->_addFlashMessage('success', '%1$s \'%2$s\' updated.', $form->getRecord());
            if ($redirector->getExit()) {
                $redirector->redirectAndExit();
            }
            return $form->getRecord();
        }
        
        $this->view->headTitle($this->_getModelDisplayName() . ' Edition');
    }

    /**
     * Deletion action.
     */
    public function deleteAction()
    {
        $record = $this->_getRecord('list');
        $record->delete();
        $this->_addFlashMessage('success', '%1$s deleted.');
        $this->_helper->redirector('list');
    }
}
