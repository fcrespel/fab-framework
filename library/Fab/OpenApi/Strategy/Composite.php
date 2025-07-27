<?php

class Fab_OpenApi_Strategy_Composite implements Fab_OpenApi_Strategy_Interface
{

    /** @var Fab_OpenApi_Strategy_Interface[] */
    protected $_strategies = array();

    /**
     * Construct a new composite strategy with the given strategies.
     * @param Fab_OpenApi_Strategy_Interface[]|string[] $strategies Strategies to consider, in priority order
     */
    public function __construct(array $strategies)
    {
        foreach ($strategies as $strategy) {
            if (is_string($strategy) && is_subclass_of($strategy, 'Fab_OpenApi_Strategy_Interface')) {
                $this->_strategies[] = new $strategy();
            } else if ($strategy instanceof Fab_OpenApi_Strategy_Interface) {
                $this->_strategies[] = $strategy;
            } else {
                throw new InvalidArgumentException('All strategies must implement Fab_OpenApi_Strategy_Interface');
            }
        }
    }

    /**
     * Set the context object this strategy resides in.
     * @param Fab_OpenApi_AutoDiscover $context
     */
    public function setContext(Fab_OpenApi_AutoDiscover $context)
    {
        foreach ($this->_strategies as $strategy) {
            $strategy->setContext($context);
        }
    }

    /**
     * Check if this strategy supports a given PHP type.
     * @param string $type PHP type
     * @return bool true if this strategy supports the type, false otherwise
     */
    public function supportsType($type)
    {
        foreach ($this->_strategies as $strategy) {
            if ($strategy->supportsType($type)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build an OpenAPI schema for a given PHP type.
     * @param string $type PHP type
     * @return Schema OpenAPI schema
     */
    public function buildSchemaForType($type)
    {
        foreach ($this->_strategies as $strategy) {
            if ($strategy->supportsType($type)) {
                return $strategy->buildSchemaForType($type);
            }
        }
        throw new Fab_OpenApi_Exception("No strategy found for type: " . $type);
    }

}
