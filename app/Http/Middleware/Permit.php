<?php

namespace App\Http\Middleware;

use App\Models\SettingAction;
use App\Models\SettingCont;
use App\Models\SettingPermission;
use App\Services\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class Permit
{
    private const ADMIN_ROLE = 'admin';
    private const CONTROLLER_NAMESPACE = 'App\Http\Controllers\\';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return ResponseService::unauthorized('User not authenticated');
        }

        $userRole = $request->user()->role->name ?? null;
        
        if (!$userRole) {
            return ResponseService::forbidden('User role not found');
        }

        // Admin users have full access
        if ($userRole === self::ADMIN_ROLE) {
            return $next($request);
        }

        $routeInfo = $this->parseCurrentRoute();
        
        if (!$routeInfo) {
            return ResponseService::forbidden('Invalid route');
        }

        [$controllerName, $actionName] = $routeInfo;

        // Check controller permission
        $controller = $this->findController($controllerName);
        if (!$controller) {
            return ResponseService::forbidden('Access denied - Controller not found');
        }

        // Check action permission
        $action = $this->findAction($actionName, $controller->id);
        if (!$action) {
            return ResponseService::forbidden('Access denied - Action not found');
        }

        // Check user permission
        if (!$this->hasPermission($request->user()->role->id, $controller->id, $action->id)) {
            return ResponseService::forbidden('Access denied - Insufficient permissions');
        }

        return $next($request);
    }

    /**
     * Parse the current route to extract controller and action names.
     */
    private function parseCurrentRoute(): ?array
    {
        $currentRoute = Route::getCurrentRoute();
        
        if (!$currentRoute) {
            return null;
        }

        $action = $currentRoute->getActionName();
        $route = str_replace(self::CONTROLLER_NAMESPACE, '', $action);
        $routeParts = explode('@', $route);

        if (count($routeParts) !== 2) {
            return null;
        }

        return $routeParts;
    }

    /**
     * Find controller by name.
     */
    private function findController(string $controllerName): ?SettingCont
    {
        return SettingCont::where('name', $controllerName)->first();
    }

    /**
     * Find action by code and controller ID.
     */
    private function findAction(string $actionCode, int $controllerId): ?SettingAction
    {
        return SettingAction::where('code', $actionCode)
            ->where('conts_id', $controllerId)
            ->first();
    }

    /**
     * Check if user has permission for the given controller and action.
     */
    private function hasPermission(int $roleId, int $controllerId, int $actionId): bool
    {
        return SettingPermission::where('role_id', $roleId)
            ->where('conts_id', $controllerId)
            ->where('action_id', $actionId)
            ->exists();
    }
}
