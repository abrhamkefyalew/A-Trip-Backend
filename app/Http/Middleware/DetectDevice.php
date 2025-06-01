<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DetectDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $userAgent = $request->header('User-Agent');
        $deviceType = 'other'; // Default value

        // Handle missing User-Agent
        if (!$userAgent) {
            $deviceType = 'unknown';
        }

        // Priority-based detection

        // 1. Postman
        elseif (stripos($userAgent, 'PostmanRuntime') !== false) {
            $deviceType = 'postman';
        }

        // 🌀 2. curl
        elseif (stripos($userAgent, 'curl') !== false) {
            $deviceType = 'curl';
        }

        // 3. Apple (iPhone, iPad, iOS)
        elseif (
            stripos($userAgent, 'iPhone') !== false ||
            stripos($userAgent, 'iPad') !== false ||
            stripos($userAgent, 'iOS') !== false
        ) {
            $deviceType = 'apple';
        }

        // 4. Android
        elseif (stripos($userAgent, 'Android') !== false) {
            $deviceType = 'android';
        }

        // 5. Web browsers (Chrome, Firefox, Safari, Edge, Opera, Brave, etc.)
        elseif (
            stripos($userAgent, 'Chrome') !== false ||
            stripos($userAgent, 'Firefox') !== false ||
            stripos($userAgent, 'Safari') !== false ||
            stripos($userAgent, 'Edg') !== false ||             // Edge includes "Edg"
            stripos($userAgent, 'OPR') !== false ||             // Opera includes "OPR"
            stripos($userAgent, 'Brave') !== false ||
            stripos($userAgent, 'Mozilla') !== false            // Catch-all for unknown browser variants
        ) {
            $deviceType = 'web';
        }

        // Attach the device type to the request so it can be used later
        $request->merge(['device_type' => $deviceType]);

        // Log all details for tracking/debugging
        Log::info('Device detected', [
            'device_type' => $deviceType,
            'user_agent' => $userAgent,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
        ]);
        
        
        return $next($request);
    }
}
