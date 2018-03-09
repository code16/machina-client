<?php

namespace Code16\MachinaClient\Tests;

use Code16\MachinaClient\MachinaClientServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class MachInaClientTestCase extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [MachinaClientServiceProvider::class];
    }
}
