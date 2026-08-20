<?php

namespace App\Http\Middleware;

use App\Models\SurveyResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A response URL is only usable by the session that created it. A shared
 * or guessed URL 404s rather than leaking that the response exists; the
 * email channel re-establishes the session via its token on entry.
 */
class EnsureSurveySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $request->route('response');

        if (! $response instanceof SurveyResponse
            || $request->session()->get('survey_response_id') !== $response->id) {
            abort(404);
        }

        return $next($request);
    }
}
