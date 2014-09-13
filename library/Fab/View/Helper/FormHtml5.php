<?php

class Fab_View_Helper_FormHtml5 extends Zend_View_Helper_FormElement
{
    /**
     * Default input type
     * @var string
     */
    const DEFAULT_TYPE = 'text';
    
    /**
     * Array of allowed input types
     * @var array
     */
    protected $_allowedTypes = array('text', 'color', 'date', 'datetime', 'datetime-local', 'email', 'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week');

    /**
     * Generates an HTML5 input element.
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are used in place of added parameters.
     * @param mixed $value The element value.
     * @param array $attribs Attributes for the element tag.
     * @return string The element XHTML.
     */
    public function formHtml5($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable

        // build the element
        $disabled = '';
        if ($disable) {
            // disabled
            $disabled = ' disabled="disabled"';
        }

        $type = self::DEFAULT_TYPE;
        if ($this->view->doctype()->isHtml5() && isset($attribs['type']) && in_array($attribs['type'], $this->_allowedTypes)) {
            $type = $attribs['type'];
            unset($attribs['type']);
        }

        $xhtml = '<input type="' . $type . '" '
                . ' name="' . $this->view->escape($name) . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . ' value="' . $this->view->escape($value) . '"'
                . $disabled
                . $this->_htmlAttribs($attribs)
                . $this->getClosingBracket();

        return $xhtml;
    }

}
