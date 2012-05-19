<?php

class Fab_Controller_Action_Helper_ModelCRUD extends ZFDoctrine_Controller_Helper_ModelForm
{

    public function __construct()
    {
        $this->setRecordIdParam('id');
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
     * @return void
     */
    public function handleDelete($modelName, $action = null, $controller = null, $module = null, array $params = array())
    {
        $actionController = $this->getActionController();
        $request = $actionController->getRequest();

        if (!$action) {
            $action = $actionController;
        }

        if ($this->getRecordIdParam()) {
            $id = $request->getParam($this->getRecordIdParam());
            if ($id) {
                $table = Doctrine_Core::getTable($modelName);
                $record = $table->find($id);
                if (!$record) {
                    throw new ZFDoctrine_DoctrineException("Cannot find record with given id.");
                }
                $record->delete();
            }
        }

        $redirector = $actionController->getHelper('redirector');
        return $redirector->gotoSimple($action, $controller, $module, $params);
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
        $actionController = $this->getActionController();
        $request = $actionController->getRequest();

        if (!$action) {
            $action = $actionController;
        }

        if ($this->getRecordIdParam()) {
            $id = $request->getParam($this->getRecordIdParam());
            if ($id) {
                $table = Doctrine_Core::getTable($modelName);
                $record = $table->find($id);
                if (!$record) {
                    throw new ZFDoctrine_DoctrineException("Cannot find record with given id.");
                }
                return $record;
            }
        }

        $redirector = $actionController->getHelper('redirector');
        return $redirector->gotoSimple($action, $controller, $module, $params);
    }

}
