<?php

abstract class Fab_Controller_CRUD extends Fab_Controller_Action
{
    /** @var string model class name */
    protected $_modelClassName;

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
        $modelCRUD = $this->getHelper('modelCRUD');
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
            $redirector->redirectAndExit();
        }
        
        $this->view->headTitle($this->_getModelDisplayName() . ' Creation');
    }

    /**
     * Edition action.
     */
    public function editAction()
    {
        $modelCRUD = $this->getHelper('modelCRUD');
        $redirector = $this->getHelper('redirector');
        $exit = $redirector->getExit();
        $form = $this->_getModelInputForm();
        
        // Ensure no new record can be added through this action
        if ($this->getRequest()->getParam($modelCRUD->getRecordIdParam()) == null)
            throw new Exception("Missing record id in request params.");
        
        // Handle the form submission
        $redirector->setExit(false);
        $modelCRUD->handleForm($form, 'list');
        $redirector->setExit($exit);
        if ($redirector->getRedirectUrl() !== null) {
            $this->_addFlashMessage('success', '%1$s \'%2$s\' updated.', $form->getRecord());
            $redirector->redirectAndExit();
        }
        
        $this->view->headTitle($this->_getModelDisplayName() . ' Edition');
    }

    /**
     * Deletion action.
     */
    public function deleteAction()
    {
        $modelCRUD = $this->getHelper('modelCRUD');
        $redirector = $this->getHelper('redirector');
        $exit = $redirector->getExit();
        
        // Handle the deletion
        $redirector->setExit(false);
        $modelCRUD->handleDelete($this->_getModelClassName(), 'list');
        $redirector->setExit($exit);
        if ($redirector->getRedirectUrl() !== null) {
            $this->_addFlashMessage('success', '%1$s deleted.');
            $redirector->redirectAndExit();
        }
    }
}
