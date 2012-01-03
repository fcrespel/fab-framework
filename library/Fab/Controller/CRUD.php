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
     * Post-dispatch routines.
     */
    public function postDispatch()
    {
        $flashMessenger = $this->getHelper('FlashMessenger');
        $namespaces = array('default', 'success', 'info', 'warning', 'error');
        $messages = array();
        foreach ($namespaces as $namespace) {
            if ($flashMessenger->setNamespace($namespace)->hasMessages())
                $messages[$namespace] = $flashMessenger->getMessages();
        }
        $this->view->flashMessages = $messages;
    }
    
    /**
     * Add a message to the FlashMessenger helper.
     * @param type $record affected record
     * @param type $namespace 'info', 'success', 'warning' or 'error'
     * @param type $message message in which %1$s is the model display name and %2$s is the record as a string
     */
    protected function _addFlashMessage($record, $namespace, $message)
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
     * Creation action.
     */
    public function addAction()
    {
        $modelCRUD = $this->getHelper('modelCRUD');
        $redirector = $this->getHelper('redirector');
        $exit = $redirector->getExit();
        $form = $this->_getModelForm();
        
        // Ensure no existing record can be edited through this action
        $this->getRequest()->setParam($modelCRUD->getRecordIdParam(), null);
        
        // Handle the form submission
        $redirector->setExit(false);
        $modelCRUD->handleForm($form, 'list');
        $redirector->setExit($exit);
        if ($redirector->getRedirectUrl() !== null) {
            $this->_addFlashMessage($form->getRecord(), 'success', '%1$s \'%2$s\' created.');
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
        $form = $this->_getModelForm();
        
        // Ensure no new record can be added through this action
        if ($this->getRequest()->getParam($modelCRUD->getRecordIdParam()) == null)
            throw new Exception("Missing record id in request params.");
        
        // Handle the form submission
        $redirector->setExit(false);
        $modelCRUD->handleForm($form, 'list');
        $redirector->setExit($exit);
        if ($redirector->getRedirectUrl() !== null) {
            $this->_addFlashMessage($form->getRecord(), 'success', '%1$s \'%2$s\' updated.');
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
            $this->_addFlashMessage('', 'success', '%1$s deleted.');
            $redirector->redirectAndExit();
        }
    }
}
