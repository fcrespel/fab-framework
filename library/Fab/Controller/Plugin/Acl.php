<?php

class Fab_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
    /**
     * Called before an action is dispatched by Zend_Controller_Dispatcher.
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $front = Zend_Controller_Front::getInstance();
        $acl = $front->getParam('bootstrap')->getResource('acl');

        $module = $request->getModuleName();
        $controller = $request->getControllerName();

        $resource = ($module == $front->getDefaultModule()) ? $controller : "$module:$controller";
        $privilege = $request->getActionName();

        if ($acl !== null && $acl->has($resource) && !$acl->isCurrentRoleAllowed($resource, $privilege)) {
            throw new Fab_Acl_Permission_Exception("Access denied to resource '$resource' with privilege '$privilege'");
        }
    }
}
