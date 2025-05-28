<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch(Request $request, $locale)
    {
        $supportedLocales = config('languages', []);

        if (array_key_exists($locale, $supportedLocales)) {
            Session::put('locale', $locale);
        } else {
            Session::flash('error', __('common.error.invalid_language_selected'));
        }

        return redirect()->back();
    }
}
