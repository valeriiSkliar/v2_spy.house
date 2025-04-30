<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\Landings\DownloadWebsiteJob;
use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Services\App\AntiFloodService;
use App\Services\Frontend\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

// Import helper functions
use function App\Helpers\sanitize_url;

class WebsiteDownloadController extends Controller
{
    use AuthorizesRequests;

    protected AntiFloodService $antiFloodService;

    public function __construct(AntiFloodService $antiFloodService)
    {
        $this->antiFloodService = $antiFloodService;
    }

    /**
     * Start a new website download
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'url' => [
                    'required',
                    'string',
                    'max:300',
                    function ($attribute, $value, $fail) {
                        // First sanitize the URL
                        $sanitizedUrl = sanitize_url($value);

                        // Replace template variables with dummy values for validation
                        $processedUrl = preg_replace('/\{[^}]+\}/', 'dummy', $sanitizedUrl);

                        if (!filter_var($processedUrl, FILTER_VALIDATE_URL)) {
                            $fail(__('validation.url', ['attribute' => $attribute]));
                        }
                    }
                ],
            ]);

            // Sanitize the URL before using it
            $sanitizedUrl = sanitize_url($validated['url']);

            // Check if a download with the same URL exists in any status for this user
            $existingDownloads = WebsiteDownloadMonitor::where('user_id', Auth::id())
                ->where(function ($query) use ($sanitizedUrl) {
                    // Check both exact URL and transformed URL
                    $query->where('url', $sanitizedUrl)
                        ->orWhere('url', 'like', '%' . parse_url($sanitizedUrl, PHP_URL_HOST) . '%');
                })
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->first();

            if ($existingDownloads) {
                $descriptionKey = match ($existingDownloads->status) {
                    'pending', 'in_progress' => 'landings.alreadyInProgress.description',
                    'completed' => 'landings.alreadyDownloaded.description',
                    default => 'landings.duplicateDownload.description',
                };
                Toast::error(__($descriptionKey));
                return redirect()->back()->withInput();
            }

            // Check website availability
            try {
                $this->checkWebsiteAvailability($sanitizedUrl);
            } catch (\Exception $e) {
                Log::error('Failed to check website availability: ' . $e->getMessage(), ['url' => $sanitizedUrl]);
                Toast::error(__('landings.downloadFailedUrlDisabled.description'));
                return redirect()->back()->withInput();
            }

            if (!$this->antiFloodService->check($request->user()->id, 'website-download', 2, 3600)) {
                Log::error('Limit reached for user ' . $request->user()->id);
                Toast::error(__('landings.antiFlood.description'));
                return redirect()->back()->withInput();
            }

            // Create download monitor with sanitized URL
            $uuid = Str::uuid();
            $monitor = WebsiteDownloadMonitor::create([
                'url' => $sanitizedUrl,
                'output_path' => 'private/website-downloads/' . $uuid,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'progress' => 0,
            ]);

            DownloadWebsiteJob::dispatch(
                $sanitizedUrl,
                $uuid,  // Pass only the UUID, not the full path
                Auth::id(),
                []  // Pass empty array for options instead of monitor ID
            )->onQueue('website-downloads');

            Toast::success(__('landings.downloadStarted.description'));
            return redirect()->back();
        } catch (ValidationException $e) {
            Log::error('URL validation failed', [
                'url' => $request->input('url'),
                'errors' => $e->errors(),
            ]);
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to start download', [
                'url' => $request->input('url'),
                'error' => $e->getMessage(),
            ]);
            Toast::error(__('landings.generalError.description'));
            return redirect()->back()->withInput();
        }
    }


    /**
     * Get the status of a specific download
     */
    public function show(Request $request, WebsiteDownloadMonitor $monitor)
    {
        $this->authorize('view', $monitor);

        return response()->json([
            'status' => $monitor->status,
            'progress' => $monitor->progress,
            'error' => $monitor->error,
            'started_at' => $monitor->started_at,
            'completed_at' => $monitor->completed_at,
        ]);
    }

    /**
     * Cancel a download in progress
     */
    public function destroy(Request $request, WebsiteDownloadMonitor $monitor)
    {
        $this->authorize('delete', $monitor);

        if ($monitor->status === 'in_progress' || $monitor->status === 'pending') {
            $monitor->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'error' => __('landings.downloadCancelledByUser.description')
            ]);

            if ($monitor->output_path) {
                Storage::deleteDirectory($monitor->output_path);
            }

            Toast::success(__('landings.downloadCancelled.description'));
        } elseif ($monitor->status === 'completed') {
            Toast::info(__('landings.downloadAlreadyCompleted.description'));
        } else {
            Toast::info(__('landings.downloadNotInProgress.description'));
        }

        return redirect()->route('landings.index', $request->only(['page', 'per_page']));
    }

    /**
     * Check if a website is available and responding
     *
     * @param string $url The URL to check
     * @throws \Exception if the website is not available
     */
    protected function checkWebsiteAvailability(string $url): void
    {
        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->head($url);

            if (!$response->successful()) {
                $response = Http::timeout(10)->withoutVerifying()->get($url);
                if (!$response->successful()) {
                    throw new \Exception("Website returned status code: " . $response->status());
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Website availability check failed (Connection)', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to connect to website: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Website availability check failed (General)', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
