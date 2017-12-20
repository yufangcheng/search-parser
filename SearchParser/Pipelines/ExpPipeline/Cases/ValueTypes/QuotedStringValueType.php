<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases\ValueTypes;

use Doctrine\DBAL\Types\Type;

class QuotedStringValueType extends AbstractValueType
{
    static public $allowedFieldTypes = [
        Type::STRING,
        Type::TEXT,
        Type::GUID,
        Type::TARRAY,
        Type::SIMPLE_ARRAY,
        Type::JSON_ARRAY,
        Type::JSON,
    ];

    static public function match($builder, $table, $field, $value = null)
    {
        $fieldType = static::getFieldType($builder, $table, $field);

        if (static::checkIsFieldTypeSupported($fieldType) && is_scalar($value)) {
            return new static($fieldType, $value);
        }

        return null;
    }

    public function explain()
    {
        return $this->value;
    }
}