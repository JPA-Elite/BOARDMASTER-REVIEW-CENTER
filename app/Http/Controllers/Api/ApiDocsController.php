<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

class ApiDocsController extends Controller
{
    public function openapi(): JsonResponse
    {
        $apiBaseUrl = rtrim((string) config('app.api_url'), '/');
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'api/'))
            ->reject(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'api/docs'))
            ->values();

        $paths = [];
        foreach ($routes as $route) {
            $path = '/'.preg_replace('/\{([^}]+)\?\}/', '{$1}', $route->uri());
            $path = preg_replace('/\{([^}]+)\}/', '{$1}', (string) $path);

            foreach ($this->httpMethods($route) as $method) {
                $paths[$path][strtolower($method)] = [
                    'summary' => $this->summary($route),
                    'operationId' => $this->operationId($route, $method),
                    'tags' => $this->tags($route),
                    'parameters' => $this->pathParameters($path),
                    'requestBody' => $this->requestBody($method),
                    'responses' => [
                        '200' => ['description' => 'Successful response'],
                        '401' => ['description' => 'Unauthorized'],
                        '422' => ['description' => 'Validation error'],
                    ],
                    'security' => $this->security($route),
                ];
            }
        }

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name', 'BoardMasters API'),
                'version' => '1.0.0',
                'description' => 'Auto-generated API docs from routes/api.php',
            ],
            'servers' => [
                [
                    'url' => $apiBaseUrl,
                    'description' => 'API Host',
                ],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Token',
                    ],
                ],
            ],
        ], options: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function ui(): Response
    {
        $openApiUrl = '/api/docs/openapi.json';

        return response(<<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BoardMasters API Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>
    window.ui = SwaggerUIBundle({
      url: '{$openApiUrl}',
      dom_id: '#swagger-ui',
      deepLinking: true,
      docExpansion: 'list',
      persistAuthorization: true
    });
  </script>
</body>
</html>
HTML, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @return array<int, string>
     */
    private function httpMethods(RoutingRoute $route): array
    {
        return collect($route->methods())
            ->reject(fn (string $method) => in_array($method, ['HEAD', 'OPTIONS'], true))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pathParameters(string $path): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        $params = $matches[1] ?? [];

        return collect($params)
            ->map(fn (string $param) => [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestBody(string $method): ?array
    {
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        return [
            'required' => false,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, array<int, string>>>
     */
    private function security(RoutingRoute $route): array
    {
        $middlewares = $route->gatherMiddleware();
        if (! in_array('auth:sanctum', $middlewares, true)) {
            return [];
        }

        return [['sanctum' => []]];
    }

    /**
     * @return array<int, string>
     */
    private function tags(RoutingRoute $route): array
    {
        $uri = $route->uri();
        if (str_starts_with($uri, 'api/mobile/')) {
            return ['Mobile'];
        }
        if (str_starts_with($uri, 'api/paymongo/')) {
            return ['Payments'];
        }
        if (str_starts_with($uri, 'api/webhooks/')) {
            return ['Webhooks'];
        }

        return ['General'];
    }

    private function summary(RoutingRoute $route): string
    {
        $name = (string) ($route->getName() ?? '');
        if ($name !== '') {
            return str_replace(['api.', '.'], ['', ' '], $name);
        }

        $action = $route->getActionName();
        if (is_string($action) && str_contains($action, '@')) {
            [, $method] = explode('@', $action, 2);

            return trim(preg_replace('/([a-z])([A-Z])/', '$1 $2', $method) ?? $method);
        }

        return $route->uri();
    }

    private function operationId(RoutingRoute $route, string $httpMethod): string
    {
        $base = $route->getName() ?: $route->uri();
        $base = str_replace(['/', '.', '-', '{', '}'], '_', $base);
        $base = preg_replace('/_+/', '_', $base) ?? $base;

        return strtolower($httpMethod).'_'.trim($base, '_');
    }
}

