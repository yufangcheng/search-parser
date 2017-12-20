<?php

namespace SearchParser\Pipelines;

use Inno\Lib\SearchParser\SearchParser;
use Avris\Bag\Bag;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Inno\Lib\SearchParser\Exceptions;

class FilterPipeline extends AbstractPipeline
{
    public function handle(SearchParser $parser, Bag $payload, Closure $next)
    {
        $filteredFields = $this->getRequestParams($parser);
        $nonRelationFields = [];
        $relationFields = $this->getRelationsPayload($payload);

        foreach ($filteredFields as $field) {
            list($relation, $field) = array_pad(
                $this->parseFieldRelation($field), 2, null
            );

            if (!$field) {
                continue;
            }

            if ($relation) {
                $relationData = $this->getRelationPayload($relationFields, $relation);
                $relationData->add($this->addRelationFieldsSelections($field));
            } else {
                $nonRelationFields[] = $field;
            }
        }

        // 必须把关联关系的主键和外键加入 select,否则如果被过滤了则关联数据查询不到数据
        // 例如 users 表中的 profile_id 外键 和 profiles 表的 id 主键关联，则这 2 个字段必须被查询出，不能被过掉

        $builder = $parser->getBuilder();
        $foreignKeys = $this->getForeignKeys($builder, $relationFields);
        $this->addNonRelationFieldsSelections($builder, array_merge($nonRelationFields, $foreignKeys));

        return $next($parser, $payload);
    }

    protected function getRequestParams(SearchParser $parser)
    {
        $request = $parser->getRequest();
        $filter = $request->get(getConfig('filter'), null);

        return array_filter(preg_split('/\s*,\s*/', $filter));
    }

    protected function addRelationFieldsSelections($field)
    {
        return function (Relation $query) use ($field) {
            $table = $query->getRelated()->getTable();
            $query->addSelect([
                $table . '.' . $query->getModel()->getKeyName(),
                $table . '.' . $field
            ]);
        };
    }

    protected function addNonRelationFieldsSelections(Builder $builder, array $nonRelationFields)
    {
        if (!empty($nonRelationFields)) {
            $builder->addSelect(array_unique($nonRelationFields));
        }
    }

    protected function getForeignKeys(Builder $builder, Bag $relationFields)
    {
        $foreignKeys = [];
        $relations = $relationFields->keys();

        try {
            foreach ($relations as $relation) {
                $relationObj = $builder->getRelation($relation);
                switch (true) {
                    case $relationObj instanceof BelongsToMany:
                        $foreignKeys[] = $relationObj->getParent()->getKeyName();
                        break;
                    case $relationObj instanceof BelongsTo:
                        $foreignKeys[] = $relationObj->getForeignKey();
                        break;
                    //TODO 更多的关系类型
                }
            }
        } catch (\Exception $e) {
            throw new Exceptions\SearchParserForeignKeyNotFoundException(sprintf(
                "Failed to find the foreign key of relation %s.",
                $relation
            ));
        }

        return $foreignKeys;
    }
}
