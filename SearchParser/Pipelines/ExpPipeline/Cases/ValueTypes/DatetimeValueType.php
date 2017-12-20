<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases\ValueTypes;

use Doctrine\DBAL\Types\Type;

class DatetimeValueType extends AbstractValueType
{
    static public $allowedFieldTypes = [
        Type::DATE,
        Type::DATETIME,
        Type::DATETIMETZ,
        Type::TIME,
        Type::INTEGER,
        Type::BIGINT,
    ];

    static public function match($builder, $table, $field, $value = null)
    {
        $fieldType = static::getFieldType($builder, $table, $field);

        if (static::checkIsFieldTypeSupported($fieldType) && strtotime($value) !== false) {
            return new static($fieldType, $value);
        }

        return null;
    }

    public function explain()
    {
        switch ($this->fieldType) {
            case Type::DATE:
            case Type::DATETIME:
            case Type::DATETIMETZ:
                return date('Y-m-d H:i:s', strtotime($this->value));
            case Type::TIME:
            case Type::INTEGER:
            case Type::BIGINT:
                return strtotime($this->value);
            default:
                return strtotime($this->value);
        }
    }
}