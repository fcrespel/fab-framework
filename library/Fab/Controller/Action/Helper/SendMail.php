<?php

class Fab_Controller_Action_Helper_SendMail extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Send a template mail to a user.
     * 
     * @param string $template template view prefix (e.g. controller/view)
     * @param string $subject mail subject
     * @param mixed $user target user (must have at least a 'mail' attribute)
     * @param array $vars arbitrary variables for to the template view
     */
    public function direct($template, $subject, $user, array $vars = array())
    {
        $bootstrap = $this->getFrontController()->getParam('bootstrap');
        $view = $bootstrap->getResource('view');
        
        $appname = $bootstrap->getOption('appname');
        $appurl = $bootstrap->getOption('appurl');
        $sender = Zend_Mail::getDefaultFrom();
        
        $partialVars = array(
            'appname'   => $appname,
            'appurl'    => $appurl,
            'sender'    => $sender['name'],
            'user'      => $user,
        );
        $partialVars = array_merge($partialVars, $vars);
        
        $mail = new Zend_Mail();
        $mail->addTo($user->mail);
        $mail->setSubject("[$appname] $subject");
        $mail->setBodyText($view->partial($template . '-mail.txt', $partialVars));
        $mail->setBodyHtml($view->partial($template . '-mail.phtml', $partialVars));
        $mail->send();
    }
}
