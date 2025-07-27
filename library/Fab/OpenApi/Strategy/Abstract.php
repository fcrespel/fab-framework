<?php

use \cebe\openapi\spec\Schema;

abstract class Fab_OpenApi_Strategy_Abstract implements Fab_OpenApi_Strategy_Interface
{

    /** @var Fab_OpenApi_AutoDiscover */
    protected $_context;

    /**
     * Set the context object this strategy resides in.
     * @param Fab_OpenApi_AutoDiscover $context
     */
    public function setContext(Fab_OpenApi_AutoDiscover $context)
    {
        $this->_context = $context;
    }

    /**
     * Check if this strategy supports a given PHP type.
     * @param string $type PHP type
     * @return bool true if this strategy supports the type, false otherwise
     */
    public function supportsType($type)
    {
        $properties = $this->_buildTypedProperties($type);
        return !empty($properties);
    }

    /**
     * Build an OpenAPI schema for a given PHP type.
     * @param string $type PHP type
     * @return Schema OpenAPI schema
     */
    public function buildSchemaForType($type)
    {
        $typedProperties = $this->_buildTypedProperties($type);
        return $this->_buildSchemaForTypedProperties($typedProperties);
    }

    /**
     * Collect properties with their types for a given PHP type.
     * @param string $type PHP type
     * @return array Associative array of property names and their types
     */
    protected abstract function _buildTypedProperties($type);

    /**
     * Build an OpenAPI schema for a list of properties with their types.
     * @param array $typedProperties Associative array of property names and their types
     * @return Schema OpenAPI schema
     */
    protected function _buildSchemaForTypedProperties($typedProperties)
    {
        // Build properties
        $properties = array();
        $requiredProperties = array();
        foreach ($typedProperties as $propertyName => $propertyTypes) {
            $required = true;
            foreach ($propertyTypes as $propertyType) {
                if ($propertyType == 'null') {
                    $required = false;
                } else if (!isset($properties[$propertyName])) {
                    $properties[$propertyName] = $this->_context->mapType($propertyType);
                }
            }
            if ($required) {
                $requiredProperties[] = $propertyName;
            }
        }

        // Build schema
        $schema = new Schema(array('properties' => $properties));
        if (!empty($requiredProperties))
            $schema->required = $requiredProperties;

        return $schema;
    }

}
