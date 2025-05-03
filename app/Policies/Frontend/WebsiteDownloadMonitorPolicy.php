<?php

namespace App\Policies\Frontend;

use App\Models\User;
use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebsiteDownloadMonitorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WebsiteDownloadMonitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create downloads
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WebsiteDownloadMonitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WebsiteDownloadMonitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Determine whether the user can download the landing.
     */
    public function download(User $user, WebsiteDownloadMonitor $monitor): bool
    {
        return $user->id === $monitor->user_id && $monitor->status === 'completed';
    }

    /**
     * Determine whether the user can check the status.
     */
    public function checkStatus(User $user, WebsiteDownloadMonitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }
}
