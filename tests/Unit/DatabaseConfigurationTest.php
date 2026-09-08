<?php

namespace Tests\Unit;

use Tests\TestCase;

class DatabaseConfigurationTest extends TestCase
{
    public function test_phpunit_uses_only_the_isolated_in_memory_database(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database'),
        );
        $this->assertNotSame(
            'finanzas_personales',
            config('database.connections.sqlite.database'),
        );
    }
}
