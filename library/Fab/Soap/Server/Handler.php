<?php

interface Fab_Soap_Server_Handler
{
    /**
     * Method called before invoking the actual method.
     * @param Fab_Soap_Server_MessageContext $context message context
     */
    public function preInvoke(Fab_Soap_Server_MessageContext $context);

    /**
     * Method called after invoking the actual method.
     * @param Fab_Soap_Server_MessageContext $context message context
     */
    public function postInvoke(Fab_Soap_Server_MessageContext $context);
}
