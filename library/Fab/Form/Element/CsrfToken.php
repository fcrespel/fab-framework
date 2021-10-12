<?php

class Fab_Form_Element_CsrfToken extends Zend_Form_Element_Hash
{
    public function initCsrfToken()
    {
        $session = $this->getSession();
        if (isset($session->hash) && !empty($session->hash)) {
            $this->_hash = $session->hash;
            $this->setValue($this->_hash);
        } else {
            $session->hash = $this->getHash();
        }
    }

    public function initCsrfValidator()
    {
        $session = $this->getSession();
        $rightHash = isset($session->hash) ? $session->hash : null;
        $this->addValidator('NotEmpty', true, array('messages' => array(
            Zend_Validate_NotEmpty::IS_EMPTY => 'Missing CSRF token, try again',
        )));
        $this->addValidator('Identical', true, array($rightHash, 'messages' => array(
            Zend_Validate_Identical::NOT_SAME => 'Invalid CSRF token, try again',
            Zend_Validate_Identical::MISSING_TOKEN => 'Missing CSRF token, try again',
        )));
        return $this;
    }
}
