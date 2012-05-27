<?php

class Fab_Application_Resource_Tinymce extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var Fab_View_Helper_TinyMce
     */
    protected $_tinymce;

    /**
     * @var Zend_View
     */
    protected $_view;

    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return Fab_View_Helper_TinyMce
     */
    public function init()
    {
        return $this->getTinyMCE();
    }

    /**
     * Retrieve TinyMCE View Helper
     *
     * @return Fab_View_Helper_TinyMce
     */
    public function getTinyMCE()
    {
        if (null === $this->_tinymce) {
            $this->getBootstrap()->bootstrap('view');
            $this->_view = $this->getBootstrap()->view;
            $this->_view->tinyMce()->setOptions($this->getOptions());
            $this->_tinymce = $this->_view->tinyMce();
        }

        return $this->_tinymce;
    }
}
