<?php

namespace App\Http\Controllers\Test\API;

use App\Http\Controllers\Controller;
use App\Services\Api\TokenService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class BaseTokenController extends Controller
{
    use AuthorizesRequests;

    public function testBaseToken()
    {
        $token = '1234567890';
        $user = Auth::user();

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function testBaseToken2()
    {
        $user = Auth::user();
        $tokens = app(TokenService::class)->getUserTokens($user);

        return response()->json($tokens);
    }
}
