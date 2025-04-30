<?php

namespace App\Http\Controllers;

use App\Models\Service\Service;
use Illuminate\Http\Request;

class ServiceRedirectController extends Controller
{
    /**
     * Handle the service redirect and increment transitions
     */
    public function redirect(Service $service)
    {
        // Increment the transitions count
        $service->increment('transitions');

        // Redirect to the actual service URL
        return redirect($service->url);
    }
}
