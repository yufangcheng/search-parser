<?php

namespace Tests\SearchParser;

use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository;

abstract class AbstractTestCase extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->initialConfig();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    protected function initialConfig()
    {
        $app = app();
        $app->instance('config', $repository = new Repository([]));
        $config = require __DIR__ . '/../../SearchParser/Config/parser.php';
        $repository->set('search_parser', $config);
    }
}