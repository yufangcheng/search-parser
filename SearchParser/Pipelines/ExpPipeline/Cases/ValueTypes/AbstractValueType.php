<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases\ValueTypes;

use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Builder;

abstract class AbstractValueType
{
    protected $fieldType;
    protected $value;

    static public $allowedFieldTypes = [
        //
    ];

    public function __construct($fieldType, $value)
    {
        $this->fieldType = $fieldType;
        $this->value = $value;
    }

    /**
     * @param Builder|Relation $builder
     * @param $table
     * @param $field
     * @param null $value
     * @return static|null
     */
    static public function match($builder, $table, $field, $value = null)
    {
        return null;
    }

    /**
     * @param Builder|Relation $builder
     * @param $table
     * @param $field
     * @return string
     */
    static public function getFieldType($builder, $table, $field)
    {
        /**
         * @var \Illuminate\Database\Connection $connection
         */
        $connection = $builder->getConnection();
        $schema = $connection->getSchemaBuilder();

        return $schema->getColumnType($table, $field);
    }

    static public function checkIsFieldTypeSupported($fieldType)
    {
        return in_array($fieldType, static::$allowedFieldTypes);
    }

    abstract public function explain();
}