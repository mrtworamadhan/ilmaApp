<?php

namespace App\Http\Middleware;

use App\Models\School; // <-- TAMBAHKAN INI
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSchoolApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json(['message' => 'API Key tidak ditemukan.'], 401);
        }

        $school = School::where('api_key', $apiKey)->first();

        if (!$school) {
            return response()->json(['message' => 'API Key tidak valid.'], 401);
        }

        $request->attributes->add(['school' => $school]);

        return $next($request);
    }
}