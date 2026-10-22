<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LogRequestPerformanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_enabled_performance_log_contains_only_safe_request_metrics(): void
    {
        config()->set('performance.enabled', true);
        Log::shouldReceive('info')
            ->once()
            ->with('performance.http', Mockery::on(function (array $metrics): bool {
                return $metrics['route'] === 'public.home'
                    && $metrics['status'] === 200
                    && $metrics['query_count'] > 0
                    && $metrics['duration_ms'] >= $metrics['query_ms']
                    && ! str_contains(json_encode($metrics), 'private-token')
                    && ! array_key_exists('sql', $metrics)
                    && ! array_key_exists('bindings', $metrics);
            }));

        $this->get(route('public.home', ['token' => 'private-token']))->assertOk();
    }
}
