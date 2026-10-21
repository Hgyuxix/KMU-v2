<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->setTestingEnvironmentVariable(
            'APP_CONFIG_CACHE',
            sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'kmu-testing-config-'
                . getmypid()
                . '-'
                . bin2hex(random_bytes(8))
                . '.php'
        );
        $this->setTestingEnvironmentVariable('DB_CONNECTION', 'sqlite');
        $this->setTestingEnvironmentVariable('DB_DATABASE', ':memory:');
        $this->setTestingEnvironmentVariable('DB_URL', '');

        /** @var Application $app */
        $app = parent::createApplication();

        if (
            $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new RuntimeException(
                'Feature tests must use the isolated SQLite in-memory database.'
            );
        }

        return $app;
    }

    private function setTestingEnvironmentVariable(string $name, string $value): void
    {
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
