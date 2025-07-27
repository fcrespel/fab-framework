<?php

class Fab_OpenApi_Strategy_DocComment extends Fab_OpenApi_Strategy_Abstract
{

    /**
     * Collect properties with their types for a given PHP type.
     * @param string $type PHP type
     * @return array Associative array of property names and their types
     */
    protected function _buildTypedProperties($type)
    {
        $class = new ReflectionClass($type);
        $typedProperties = array();
        if (preg_match_all('/@property\s+(\S+)\s+\$(\S+)/m', $class->getDocComment(), $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $propertyName = $match[2];
                if (!isset($typedProperties[$propertyName])) {
                    $typedProperties[$propertyName] = explode('|', $match[1]);
                }
            }
        }
        return $typedProperties;
    }

}
