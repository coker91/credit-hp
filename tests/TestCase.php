<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached()
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException(
                'Tests require an uncached configuration and SQLite :memory:. Run php artisan config:clear first.'
            );
        }

        return $app;
    }
}
