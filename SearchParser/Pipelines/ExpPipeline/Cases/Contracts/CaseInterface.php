<?php

namespace SearchParser\Pipelines\ExpPipeline\Cases\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Avris\Bag\Bag;

interface CaseInterface
{
    public function getMiddlewares();

    public function getTable($builder);
    
    public function getSupportedValueTypes();

    public function assemble(Builder $builder, Bag $payload);
}
