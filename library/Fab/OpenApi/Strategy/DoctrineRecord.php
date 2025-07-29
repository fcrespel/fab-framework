<?php

class Fab_OpenApi_Strategy_DoctrineRecord extends Fab_OpenApi_Strategy_Abstract
{

    /**
     * Check if this strategy supports a given PHP type.
     * @param string $type PHP type
     * @return bool true if this strategy supports the type, false otherwise
     */
    public function supportsType($type)
    {
        return is_subclass_of($type, 'Doctrine_Record');
    }

    /**
     * Collect properties with their types for a given PHP type.
     * @param string $type PHP type
     * @return array Associative array of property names and their types
     */
    protected function _buildTypedProperties($type)
    {
        $typedProperties = array();
        $table = Doctrine_Core::getTable($type);

        // Enumerate columns
        foreach ($table->getColumns() as $column => $columnDef) {
            $columnName = $table->getColumnName($column);
            $propertyName = $table->getFieldName($columnName);
            if (!isset($typedProperties[$propertyName])) {
                $typedProperties[$propertyName] = array($columnDef['type']);
                if (!isset($columnDef['notnull']) || $columnDef['notnull'] !== true) {
                    $typedProperties[$propertyName][] = 'null';
                }
            }
        }

        // Enumerate relations
        foreach ($table->getRelations() as $relation) {
            if ($relation->isRefClass())
                continue;

            $relationType = $relation->getClass();
            if (!$relation->isOneToOne())
                $relationType .= '[]';

            $propertyName = $relation->getAlias();
            if (!isset($typedProperties[$propertyName])) {
                $typedProperties[$propertyName] = array($relationType, 'null');
            }
        }

        return $typedProperties;
    }

}
