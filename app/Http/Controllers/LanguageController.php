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
            Session::flash('success', __('Language changed successfully.'));
        } else {
            Session::flash('error', __('Invalid language selected.'));
        }

        return redirect()->back();
    }
}
