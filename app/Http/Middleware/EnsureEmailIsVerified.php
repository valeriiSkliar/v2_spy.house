<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

// class EnsureEmailIsVerified
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  \Closure  $next
//      * @param  string|null  $redirectToRoute
//      * @return mixed
//      */
//     public function handle(Request $request, Closure $next, $redirectToRoute = null)
//     {
//         if (
//             ! $request->user() ||
//             ($request->user() instanceof MustVerifyEmail &&
//                 ! $request->user()->hasVerifiedEmail())
//         ) {
//             return $request->expectsJson()
//                 ? response()->json(['message' => 'Your email address is not verified.'], 409)
//                 : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
//         }

//         return $next($request);
//     }
// }
