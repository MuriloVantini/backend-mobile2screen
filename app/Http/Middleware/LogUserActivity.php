<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if ($user
            && ! str_ends_with((string) $request->route()?->getControllerClass(), 'RealtimeController')
            && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $route = $request->route();
            $resource = $this->resourceName($route?->getControllerClass());
            $routeModel = collect($route?->parameters() ?? [])->first(fn ($value) => $value instanceof Model);

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $resource.'.'.$route?->getActionMethod(),
                'resource_type' => $resource,
                'resource_id' => $routeModel?->getKey(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                ],
            ]);
        }

        return $response;
    }

    private function resourceName(?string $controller): string
    {
        if (! $controller) {
            return 'api';
        }

        return str(class_basename($controller))
            ->beforeLast('Controller')
            ->snake()
            ->toString();
    }
}
