<?php

namespace App\Finance\Http\Controllers;

use App\Finance\Models\Subscription;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tariffs = Subscription::all();

        return view('pages.tariffs.index', [
            'currentTariff' => $user->currentTariff(),
            'tariffs' => $tariffs,
            'payments' => $user->subscriptionPayments()->paginate(10),
            'activeSubscriptions' => $user->activeSubscriptions(),
        ]);
    }
}
