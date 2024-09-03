<?php

namespace Vod\Vod\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vod\Vod\VodServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            VodServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {


    }
}
