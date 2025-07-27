<?php

use \cebe\openapi\spec\Schema;

interface Fab_OpenApi_Strategy_Interface
{

    /**
     * Set the context object this strategy resides in.
     * @param Fab_OpenApi_AutoDiscover $context
     */
    public function setContext(Fab_OpenApi_AutoDiscover $context);

    /**
     * Check if this strategy supports a given PHP type.
     * @param string $type PHP type
     * @return bool true if this strategy supports the type, false otherwise
     */
    public function supportsType($type);

    /**
     * Build an OpenAPI schema for a given PHP type.
     * @param string $type PHP type
     * @return Schema OpenAPI schema
     */
    public function buildSchemaForType($type);

}
