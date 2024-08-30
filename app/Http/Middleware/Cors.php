<?php

// this was created by me, and it is never worked and never used

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Illuminate\Http\Response;

// class Cors
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  \Closure  $next
//      * @return \Illuminate\Http\Response
//      */
//     public function handle(Request $request, Closure $next)
//     {
//         $response = $next($request);

//         $response->header('Access-Control-Allow-Origin', '*');
//         $response->header('Access-Control-Allow-Methods', 'PUT, POST, DELETE, GET, OPTIONS');
//         $response->header('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type', 'X-Requested-With');

//         return $response;
//     }
// }