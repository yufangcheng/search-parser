<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases\ValueTypes;

use Doctrine\DBAL\Types\Type;

class NumericValueType extends AbstractValueType
{
    static public $allowedFieldTypes = [
        Type::SMALLINT,
        Type::INTEGER,
        Type::BIGINT,
        Type::DECIMAL,
        Type::FLOAT,
        Type::BOOLEAN,
    ];

    static public function match($builder, $table, $field, $value = null)
    {
        $fieldType = static::getFieldType($builder, $table, $field);

        if (static::checkIsFieldTypeSupported($fieldType) && is_numeric($value)) {
            return new static($fieldType, $value);
        }

        return null;
    }

    public function explain()
    {
        return $this->value;
    }
}