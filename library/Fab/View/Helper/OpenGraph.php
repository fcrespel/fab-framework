<?php

class Fab_View_Helper_OpenGraph extends Zend_View_Helper_Abstract
{
    /** @var array */
    protected $_wellKnownPrefixes = array(
        'music'     => 'http://ogp.me/ns/music#',
        'video'     => 'http://ogp.me/ns/video#',
        'article'   => 'http://ogp.me/ns/article#',
        'book'      => 'http://ogp.me/ns/book#',
        'profile'   => 'http://ogp.me/ns/profile#',
        'fb'        => 'http://ogp.me/ns/fb#',
    );

    /** @var array */
    protected $_prefixes = array('og' => 'http://ogp.me/ns#');

    /** @var array */
    protected $_properties = array('og:type' => 'website');

    /**
     * Open Graph protocol helper.
     * @param array $properties Open Graph properties and values
     */
    public function openGraph($properties = array())
    {
        $this->setProperties($properties);
        return $this;
    }

    /**
     * Magic setter.
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ($name === 'prefixes') {
            $this->setPrefixes($value);
        } else {
            $this->setProperty($name, $value);
        }
    }

    /**
     * Magic getter.
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'prefixes') {
            return $this->getPrefixes();
        } else {
            return $this->getProperty($name);
        }
    }

    /**
     * Render meta tags.
     * @return string rendered meta tags
     */
    public function __toString()
    {
        $metaHelper = $this->view->headMeta();
        $meta = array();
    
        $properties = array_unique(array_merge(array('og:title', 'og:url', 'og:type'), array_keys($this->_properties)));
        foreach ($properties as $property) {
            $value = $this->getProperty($property);
            if (!empty($value)) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $meta[] = $metaHelper->itemToString($metaHelper->createData('property', $property, $item, array()));
                    }
                } else {
                    $meta[] = $metaHelper->itemToString($metaHelper->createData('property', $property, $value, array()));
                }
            }
        }

        return PHP_EOL . implode(PHP_EOL, $meta) . PHP_EOL;
    }

    /**
     * Render namespace prefixes.
     */
    public function prefixes()
    {
        $items = array();
        foreach ($this->_prefixes as $prefix => $ns) {
            $items[] = "$prefix: $ns";
        }
        return implode(' ', $items);
    }

    /**
     * Normalize a property name to its canonical version.
     * Example: 'imageSecureUrl' => 'og:image:secure_url'
     * @param string $property property name
     * @return string canonical property name
     */
    protected function _getCanonicalPropertyName($property)
    {
        $property = strtolower(preg_replace('/([A-Z])/', ':$1', lcfirst($property)));
        $property = str_replace(array('secure:url', ':name', ':date', ':time', ':id'), array('secure_url', '_name', '_date', '_time', '_id'), $property);
        $property = preg_replace('/^(title|type|image|url|audio|description|determiner|locale|site_name|video$|video:(secure_url|type|width|height))/', 'og:$1', $property);
        return $property;
    }

    /**
     * Normalize a property name to its simple version.
     * Example: 'og:image:secure_url' => 'imageSecureUrl'
     * @param string $property property name
     * @return string simple property name
     */
    protected function _getSimplePropertyName($property)
    {
        $property = preg_replace('/^og:/', '', $property);
        $property = lcfirst(ucwords($property, ':_'));
        $property = str_replace(array(':', '_'), '', $property);
        return $property;
    }

    /**
     * Get the value of a property identified by a key.
     * @param string $key
     * @return mixed
     */
    public function getProperty($key)
    {
        $property = $this->_getCanonicalPropertyName($key);
        $method = 'get' . ucfirst($this->_getSimplePropertyName($key));
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return $this->_properties[$property];
        }
        return null;
    }

    /**
     * Set the value of a property identified by a key.
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setProperty($key, $value)
    {
        $property = $this->_getCanonicalPropertyName($key);
        $method = 'set' . ucfirst($this->_getSimplePropertyName($key));
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->_properties[$property] = $value;
            $propertyParts = explode(':', $property);
            $prefix = $propertyParts[0];
            if (isset($this->_wellKnownPrefixes[$prefix])) {
                $this->_prefixes[$prefix] = $this->_wellKnownPrefixes[$prefix];
            }
        }
        return $this;
    }

    /**
     * Set the values of an array of properties.
     * @param array $properties
     * @return self
     */
    public function setProperties($properties = array())
    {
        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }
        return $this;
    }

    /**
     * Get namespace prefixes.
     */
    public function getPrefixes()
    {
        return $this->_prefixes;
    }

    /**
     * Set namespace prefixes.
     * @param array $prefixes
     * @return self
     */
    public function setPrefixes($prefixes)
    {
        $this->_prefixes = $prefixes;
        return $this;
    }

    /**
     * Get the page canonical URL.
     */
    public function getUrl()
    {
        $url = isset($this->_properties['og:url']) ? $this->_properties['og:url'] : null;
        if (empty($url)) {
            $url = $this->view->serverUrl(true);
        }
        return $url;
    }

    /**
     * Get the page title.
     */
    public function getTitle()
    {
        $title = isset($this->_properties['og:title']) ? $this->_properties['og:title'] : null;
        if (empty($title)) {
            $titleParts = $this->view->headTitle()->getArrayCopy();
            array_shift($titleParts);
            $title = implode($this->view->headTitle()->getSeparator(), $titleParts);
        }
        return $title;
    }

    /**
     * Get the page type (website, article, book, profile, etc.)
     */
    public function getType()
    {
        $type = isset($this->_properties['og:type']) ? $this->_properties['og:type'] : null;
        if (empty($type)) {
            $type = 'website';
        }
        return $type;
    }

}
