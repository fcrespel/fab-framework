<?php

class Fab_Soap_Server_Handler_Wrapped implements Fab_Soap_Server_Handler
{
    public function preInvoke(Fab_Soap_Server_MessageContext $context)
    {
        $args = $context->getMethodArgs();
        if (count($args) == 1 && isset($args[0]) && is_object($args[0])) {
            $args = get_object_vars($args[0]);
            $context->setMethodArgs($args);
        }
    }

    public function postInvoke(Fab_Soap_Server_MessageContext $context)
    {
        $result = array($context->getMethodName() . 'Result' => $context->getMethodReturn());
        $context->setMethodReturn($result);
    }
}
