<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_root_renders_the_application_brand(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeText('Sistema de Gestión Dental');
    }
}
