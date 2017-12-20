<?php

namespace SearchParser\Pipelines\ExpPipeline\Resolvers;

use Inno\Lib\SearchParser\Pipelines\ExpPipeline\Cases\SetCase;

class SetResolver extends AbstractResolver
{
    protected static $resolverReg = '/^\s*(?:(OR|AND)\s+)?(?:(\w+)\.)?(\w+)\s*:\|\s*(?:(NOT)\s+)?((?:\-?\d+(?:\.\d+)?|".+?")(?:\s*,\s*(?:\-?\d+(?:\.\d+)?|".+?"))+)\s*$/i';

    protected function getCaseClasses()
    {
        return SetCase::class;
    }
}
