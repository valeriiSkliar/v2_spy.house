<?php

namespace App\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Handle Pay2House webhook.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function pay2house(Request $request)
    {
        $data = $request->all();

        dd($data);
    }
}
