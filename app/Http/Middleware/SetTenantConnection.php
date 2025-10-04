<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetTenantConnection
{
    private const DEFAULT_SCHEMA = 'public';
    private const TENANT_CONFIG_KEY = 'database.connections.tenant.search_path';
    private const TENANT_INSTANCE_KEY = 'tenant.schema';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        
        if (!$user || !isset($user->tenant_schema)) {
            Log::warning('User authenticated but tenant schema not found', [
                'user_id' => $user->id ?? null
            ]);
            return $next($request);
        }

        try {
            $this->setTenantSchema($user->tenant_schema);
        } catch (\Exception $e) {
            Log::error('Failed to set tenant schema', [
                'user_id' => $user->id,
                'tenant_schema' => $user->tenant_schema,
                'error' => $e->getMessage()
            ]);
            
            // Continue with default schema on error
            $this->setTenantSchema(self::DEFAULT_SCHEMA);
        }

        return $next($request);
    }

    /**
     * Set the tenant schema for database operations.
     */
    private function setTenantSchema(string $schema): void
    {
        // Set PostgreSQL search path
        DB::statement("SET search_path TO {$schema}, " . self::DEFAULT_SCHEMA);

        // Update configuration
        config([
            self::TENANT_CONFIG_KEY => $schema
        ]);

        // Set global instance for application use
        app()->instance(self::TENANT_INSTANCE_KEY, $schema);
    }

    /**
     * Get the current tenant schema.
     */
    public static function getCurrentSchema(): ?string
    {
        return app(self::TENANT_INSTANCE_KEY, null);
    }

    /**
     * Reset to default schema.
     */
    public static function resetToDefaultSchema(): void
    {
        DB::statement("SET search_path TO " . self::DEFAULT_SCHEMA);
        
        config([
            self::TENANT_CONFIG_KEY => self::DEFAULT_SCHEMA
        ]);
        
        app()->instance(self::TENANT_INSTANCE_KEY, self::DEFAULT_SCHEMA);
    }
}
