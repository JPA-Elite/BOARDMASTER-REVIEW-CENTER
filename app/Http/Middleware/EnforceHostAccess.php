<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Symfony\Component\HttpFoundation\Response;

class EnforceHostAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $landingUrl = (string) config('app.landing_url');
        $adminUrl = (string) config('app.admin_url');
        $landingHost = $this->extractHost($landingUrl);
        $adminHost = $this->extractHost($adminUrl);
        $isApiRequest = $request->is('api/*');
        $apiEndpoints = $this->apiEndpoints();
        $apiHosts = collect($apiEndpoints)
            ->pluck('host')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($this->shouldEnforceApiHostAccess() && $apiHosts !== []) {
            if (in_array($host, $apiHosts, true) && ! $isApiRequest) {
                abort(404);
            }

            if ($isApiRequest && ! $this->matchesApiEndpoint($request, $apiEndpoints)) {
                abort(404);
            }
        }

        if ($adminHost !== '' && $host === $adminHost) {
            if ($this->isAdminHostAllowedPath($request)) {
                return $next($request);
            }

            return $this->redirectToAdmin($request, $adminUrl);
        }

        if ($landingHost !== '' && $host === $landingHost && $this->isAdminPath($request)) {
            return $this->redirectToAdmin($request, $adminUrl);
        }

        return $next($request);
    }

    private function isAdminPath(Request $request): bool
    {
        return $request->is('admin') || $request->is('admin/*');
    }

    private function isAdminHostAllowedPath(Request $request): bool
    {
        if ($this->isAdminPath($request)) {
            return true;
        }

        $route = $request->route();
        if (! $route instanceof RoutingRoute) {
            return false;
        }

        return $this->isAdminRoute($route)
            || $this->isAuthRoute($route)
            || $this->isDashboardOrProfileRoute($route)
            || $this->isFrameworkUtilityRoute($route);
    }

    private function isAdminRoute(RoutingRoute $route): bool
    {
        $uri = ltrim($route->uri(), '/');

        return str_starts_with((string) ($route->getName() ?? ''), 'admin.')
            || $uri === 'admin'
            || str_starts_with($uri, 'admin/');
    }

    private function isAuthRoute(RoutingRoute $route): bool
    {
        return str_starts_with((string) $route->getActionName(), 'App\\Http\\Controllers\\Auth\\');
    }

    private function isDashboardOrProfileRoute(RoutingRoute $route): bool
    {
        $action = (string) $route->getActionName();

        return $action === 'App\\Http\\Controllers\\DashboardRedirectController'
            || str_starts_with($action, 'App\\Http\\Controllers\\ProfileController@');
    }

    private function isFrameworkUtilityRoute(RoutingRoute $route): bool
    {
        $uri = ltrim($route->uri(), '/');

        return $uri === 'up' || $uri === 'sanctum/csrf-cookie';
    }

    private function redirectToAdmin(Request $request, string $adminUrl): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(404);
        }

        $base = rtrim($adminUrl, '/');
        $target = $base !== '' ? "{$base}/admin" : '/admin';

        return redirect()->to($target);
    }

    private function extractHost(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }

    /**
     * @return array<int, array{host: string, scheme: string}>
     */
    private function apiEndpoints(): array
    {
        $configured = config('app.api_allowed_urls', []);
        $urls = is_array($configured) ? $configured : [];

        return collect($urls)
            ->map(function ($url): array {
                $host = $this->extractHost((string) $url);
                $scheme = parse_url((string) $url, PHP_URL_SCHEME);

                return [
                    'host' => $host,
                    'scheme' => is_string($scheme) ? strtolower($scheme) : '',
                ];
            })
            ->filter(fn (array $endpoint): bool => $endpoint['host'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{host: string, scheme: string}>  $endpoints
     */
    private function matchesApiEndpoint(Request $request, array $endpoints): bool
    {
        if ($endpoints === []) {
            return false;
        }

        $host = strtolower($request->getHost());
        $scheme = strtolower($request->getScheme());

        foreach ($endpoints as $endpoint) {
            if ($endpoint['host'] !== $host) {
                continue;
            }

            if ($endpoint['scheme'] === '' || $endpoint['scheme'] === $scheme) {
                return true;
            }
        }

        return false;
    }

    private function shouldEnforceApiHostAccess(): bool
    {
        return strtolower((string) config('app.env')) === 'production';
    }
}
