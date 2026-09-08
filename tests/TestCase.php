<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot every test with an isolated in-memory database, even when the
     * surrounding Docker service exports development environment variables.
     */
    public function createApplication(): Application
    {
        $this->setEnvironmentVariable('APP_ENV', 'testing');
        $this->setEnvironmentVariable('CACHE_STORE', 'array');
        $this->setEnvironmentVariable('DB_CONNECTION', 'sqlite');
        $this->setEnvironmentVariable('DB_DATABASE', ':memory:');
        $this->setEnvironmentVariable('DB_URL', '');

        return parent::createApplication();
    }

    private function setEnvironmentVariable(string $name, string $value): void
    {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
