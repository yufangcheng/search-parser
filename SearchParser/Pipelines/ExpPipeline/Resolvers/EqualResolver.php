<?php

namespace SearchParser\Pipelines\ExpPipeline\Resolvers;

use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\EqualCase;

class EqualResolver extends AbstractResolver
{
    protected static $resolverReg = '/^\s*(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\s*(?:(NOT)\s+)?(\-?\d+(?:\.\d+)?|".+?")\s*$/i';

    protected function getCaseClasses()
    {
        return EqualCase::class;
    }
}
