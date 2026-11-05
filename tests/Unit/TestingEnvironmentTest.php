<?php

namespace Tests\Unit;

use Tests\TestCase;

class TestingEnvironmentTest extends TestCase
{
    public function test_phpunit_configuration_uses_safe_testing_database_environment(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }
}
