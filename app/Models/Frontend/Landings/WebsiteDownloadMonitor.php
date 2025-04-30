<?php

namespace App\Models\Frontend\Landings;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteDownloadMonitor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'output_path',
        'status',
        'progress',
        'error',
        'user_id',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the download.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update the download progress.
     */
    public function updateProgress(int $progress): void
    {
        $this->update([
            'progress' => $progress,
            'status' => $progress >= 100 ? 'completed' : 'in_progress',
            'completed_at' => $progress >= 100 ? now() : null,
        ]);
    }

    /**
     * Mark the download as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now(),
        ]);
    }

    /**
     * Start the download.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }
}
