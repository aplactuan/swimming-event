<?php

namespace App\Http\Middleware;

use App\Models\Competition;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompetitionIsOpen
{
    /**
     * Block changes to a competition that has already been closed.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $competition = $request->route('competition');

        abort_if(
            $competition instanceof Competition && $competition->isClosed(),
            403,
            'This competition is closed.',
        );

        return $next($request);
    }
}
