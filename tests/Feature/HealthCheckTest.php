<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_native_health_check_is_available_without_application_middleware(): void
    {
        $this->get('/up')->assertOk();
    }
}
