<?php

class Fab_Controller_Action extends Zend_Controller_Action implements Zend_Acl_Resource_Interface
{
    /**
     * Add a message to the FlashMessenger helper.
     * @param type $namespace 'info', 'success', 'warning' or 'error'
     * @param type $message message to display
     */
    protected function _addFlashMessage($namespace, $message)
    {
        $this->_helper->flashMessenger->setNamespace($namespace)->addMessage($message);
    }

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
}
