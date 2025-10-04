<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class NotePayment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $currentDate = Carbon::now();
        // $oneDayAfterCurrentDate = Carbon::now()->addDay();
        // $timeDifference = $oneDayAfterCurrentDate->diff($currentDate);

        // $month = $timeDifference->m ;
        // $month = $timeDifference->d ;

        return $next($request);
    }
}
