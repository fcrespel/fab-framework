<?php

class Fab_Controller_Action_Helper_ModelCRUD extends ZFDoctrine_Controller_Helper_ModelForm
{
    protected $_recordIdField = null;
    
    public function getRecordIdField()
    {
        return $this->_recordIdField;
    }

    public function setRecordIdField($recordIdField)
    {
        $this->_recordIdField = $recordIdField;
        return $this;
    }

    /**
     * Construct the ModelCRUD helper.
     */
    public function __construct()
    {
        $this->setRecordIdParam('id');
    }
    
    /**
     * Get the record ID specified in request params.
     * @return mixed
     */
    protected function _getRecordId()
    {
        $request = $this->getActionController()->getRequest();
        if ($this->getRecordIdParam()) {
            return $request->getUserParam($this->getRecordIdParam());
        }
        return null;
    }
    
    /**
     * Get a record of a given Doctrine model.
     * @param type $modelName
     * @return null|Doctrine_Record
     * @throws ZFDoctrine_DoctrineException
     */
    protected function _getRecord($modelName)
    {
        $id = $this->_getRecordId();
        if ($id) {
            $table = Doctrine_Core::getTable($modelName);
            $record = $this->getRecordIdField() ? $table->findOneBy($this->getRecordIdField(), $id) : $table->find($id);
            if (!$record) {
                throw new ZFDoctrine_DoctrineException("Cannot find record with given id.");
            }
            return $record;
        }
        return null;
    }
    
    /**
     * Redirect to the specified location.
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     */
    protected function _doRedirect($action, $controller, $module, $params)
    {
        $actionController = $this->getActionController();
        if (!$action) {
            $action = $actionController;
        }

        $redirector = $actionController->getHelper('redirector');
        $redirector->gotoSimple($action, $controller, $module, $params);
    }
    
    /**
     * Handle Create or Update Workflow of a ZFDoctrine_Form_Model instance
     * 
     * @throws ZFDoctrine_DoctrineException
     * @param ZFDoctrine_Form_Model $form
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @return void
     */
    public function handleForm(ZFDoctrine_Form_Model $form, $action = null, $controller = null, $module = null, array $params = array())
    {
        $actionController = $this->getActionController();
        $request = $actionController->getRequest();

        $actionParams = array();
        $record = $this->_getRecord($form->getModelName());
        if ($record) {
            $id = $this->_getRecordId();
            $actionParams = array($this->getRecordIdParam() => $id);
            $actionController->view->assign('recordId', $id);
            $actionController->view->assign('record', $record);
            $form->setRecord($record);
        }

        $urlHelper = $actionController->getHelper('url');
        $form->setMethod('post');
        $form->setAction($urlHelper->simple(
            $request->getActionName(),
            $request->getControllerName(),
            $request->getModuleName(),
            $actionParams
        ));

        if ($request->isPost() && $form->isValid($request->getPost())) {
            $form->save();
            return $this->_doRedirect($action, $controller, $module, $params);
        }

        $actionController->view->assign('form', $form);
    }

    /**
     * Handle Delete Workflow of a Doctrine model
     *
     * @throws ZFDoctrine_DoctrineException
     * @param string $modelName
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     */
    public function handleDelete($modelName, $action = null, $controller = null, $module = null, array $params = array())
    {
        $record = $this->_getRecord($modelName);
        if ($record) {
            $record->delete();
        } else {
            $this->_doRedirect($action, $controller, $module, $params);
        }
    }
    
    /**
     * Handle Record selection Workflow of a Doctrine model
     *
     * @throws ZFDoctrine_DoctrineException
     * @param string $modelName
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @return Doctrine_Record
     */
    public function handleRecord($modelName, $action = null, $controller = null, $module = null, array $params = array())
    {
        $record = $this->_getRecord($modelName);
        if ($record) {
            return $record;
        } else {
            $this->_doRedirect($action, $controller, $module, $params);
        }
    }

}
