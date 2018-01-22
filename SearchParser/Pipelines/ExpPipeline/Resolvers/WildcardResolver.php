<?php

namespace SearchParser\Pipelines\ExpPipeline\Resolvers;

use SearchParser\Pipelines\ExpPipeline\Cases\WildcardCase;

class WildcardResolver extends AbstractResolver
{
    protected static $resolverReg = '/^\s*(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*~\s*(?:(NOT)\s+)?(".*?")\s*$/i';

    protected function getCaseClasses()
    {
        return WildcardCase::class;
    }
}
