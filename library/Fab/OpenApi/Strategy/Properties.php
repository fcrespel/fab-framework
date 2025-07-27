<?php

class Fab_OpenApi_Strategy_Properties extends Fab_OpenApi_Strategy_Abstract
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
        $defaultProperties = $class->getDefaultProperties();
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (preg_match_all('/@var\s+([^\s]+)/m', $property->getDocComment(), $matches)) {
                $propertyName = $property->getName();
                if (!isset($typedProperties[$propertyName])) {
                    $typedProperties[$propertyName] = explode('|', trim($matches[1][0]));
                    if ($defaultProperties[$propertyName] === null) {
                        $typedProperties[$propertyName][] = 'null';
                    }
                }
            }
        }
        return $typedProperties;
    }

}
