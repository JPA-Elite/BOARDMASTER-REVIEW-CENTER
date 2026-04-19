<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApiHostAccessSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'production');
        config()->set('app.api_url', 'http://api.boardmasters.local:8080');
        config()->set('app.api_allowed_urls', ['http://api.boardmasters.local:8080']);
        config()->set('app.landing_url', 'http://boardmasters.local:8080');
        config()->set('app.admin_url', 'http://app.boardmasters.local:8080');
    }

    public function test_api_routes_are_accessible_on_api_host(): void
    {
        $response = $this->apiGetOnHost('api.boardmasters.local', '/api/mobile/plans');

        $response->assertOk();
    }

    public function test_api_routes_are_blocked_on_landing_host(): void
    {
        $response = $this->apiGetOnHost('boardmasters.local', '/api/mobile/plans');

        $response->assertStatus(404);
    }

    public function test_api_routes_allow_configured_lan_ip_host(): void
    {
        config()->set('app.api_allowed_urls', [
            'http://api.boardmasters.local:8080',
            'http://192.168.1.18:8080',
        ]);

        $response = $this->apiGetOnHost('192.168.1.18', '/api/mobile/plans');

        $response->assertOk();
    }

    public function test_api_host_rule_is_not_enforced_outside_production(): void
    {
        config()->set('app.env', 'local');

        $response = $this->apiGetOnHost('boardmasters.local', '/api/mobile/plans');

        $response->assertOk();
    }

    public function test_non_api_routes_are_blocked_on_api_host(): void
    {
        $response = $this->webGetOnHost('api.boardmasters.local', '/partnership');

        $response->assertStatus(404);
    }

    public function test_swagger_docs_are_available_on_api_host(): void
    {
        $response = $this->webGetOnHost('api.boardmasters.local', '/api/docs');
        $response->assertOk();

        $json = $this->apiGetOnHost('api.boardmasters.local', '/api/docs/openapi.json');
        $json
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonStructure([
                'paths' => [
                    '/api/mobile/plans',
                ],
            ]);
    }

    public function test_swagger_docs_are_blocked_on_non_api_host(): void
    {
        $response = $this->webGetOnHost('boardmasters.local', '/api/docs');
        $response->assertStatus(404);
    }

    public function test_prod_api_host_rule_is_enforced(): void
    {
        config()->set('app.api_url', 'https://api.boardmasters.com');
        config()->set('app.api_allowed_urls', ['https://api.boardmasters.com']);

        $allowed = $this->apiGetOnHost('api.boardmasters.com', '/api/mobile/plans', 'https');
        $allowed->assertOk();

        $wrongScheme = $this->apiGetOnHost('api.boardmasters.com', '/api/mobile/plans');
        $wrongScheme->assertStatus(404);

        $wrongHost = $this->apiGetOnHost('boardmasters.com', '/api/mobile/plans', 'https');
        $wrongHost->assertStatus(404);
    }

    private function apiGetOnHost(string $host, string $uri, string $scheme = 'http'): TestResponse
    {
        return $this->call(
            'GET',
            $this->absoluteUrl($host, $uri, $scheme),
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );
    }

    private function webGetOnHost(string $host, string $uri, string $scheme = 'http'): TestResponse
    {
        return $this->call('GET', $this->absoluteUrl($host, $uri, $scheme));
    }

    private function absoluteUrl(string $host, string $uri, string $scheme = 'http'): string
    {
        return strtolower($scheme).'://'.$host.'/'.ltrim($uri, '/');
    }
}
