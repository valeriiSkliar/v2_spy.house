<?php

namespace App\Http\Controllers\Frontend\Service;

use App\Http\Controllers\FrontendController;
use App\Models\Frontend\Service\Service;

class ServiceRedirectController extends FrontendController
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
