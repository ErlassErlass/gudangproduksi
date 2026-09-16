<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Middleware\QueryTokenMiddleware;

Route::get('/', function () {
    return view('app');
});

Route::get('/test-middleware', function (Request $request) {
    return response()->json([
        'header' => $request->header('Authorization'),
        'token' => $request->query('token'),
    ]);
})->middleware(QueryTokenMiddleware::class);
