<?php

namespace App\Jobs;

use App\Models\AdvertismentNetwork;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class SyncAdvertismentNetworksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting synchronization of advertisement networks');

        try {
            // Получаем данные с внешнего API
            $apiNetworks = $this->fetchNetworksFromApi();

            if (empty($apiNetworks)) {
                Log::warning('No networks received from API');
                return;
            }

            // Получаем существующие коды сетей из базы
            $existingCodes = AdvertismentNetwork::pluck('network_name')->toArray();

            // Находим новые сети
            $newNetworks = collect($apiNetworks)->filter(function ($apiNetwork) use ($existingCodes) {
                return !in_array($apiNetwork['code'], $existingCodes);
            });

            if ($newNetworks->isEmpty()) {
                Log::info('No new advertisement networks found. Synchronization completed successfully.');
                return;
            }

            // ЗАГЛУШКА: Логируем информацию о новых сетях
            // В будущем здесь будет отправка уведомления администратору
            $newNetworkNames = $newNetworks->pluck('name')->toArray();
            $newNetworkCodes = $newNetworks->pluck('code')->toArray();

            Log::info('NEW ADVERTISEMENT NETWORKS DETECTED! Administrator notification required.', [
                'count' => $newNetworks->count(),
                'networks' => $newNetworkNames,
                'codes' => $newNetworkCodes,
                'details' => $newNetworks->toArray(),
                'message' => 'These networks should be reviewed and manually added to the database after approval.',
            ]);

            // TODO: Implement administrator notification system
            // TODO: Send email/notification to admin about new networks
            Log::warning('ADMIN ACTION REQUIRED: New advertisement networks need manual review and approval.');
        } catch (RequestException $e) {
            Log::error('Failed to fetch networks from API', [
                'error' => $e->getMessage(),
                'status' => $e->response?->status(),
                'body' => $e->response?->body(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error during advertisement networks synchronization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch networks from external API
     *
     * @return array
     * @throws RequestException
     */
    private function fetchNetworksFromApi(): array
    {
        $apiUrl = config('feedhouse.api_url');
        $endpoint = config('feedhouse.ad_networks_endpoint');
        $apiKey = config('feedhouse.api_key');

        $url = $apiUrl . $endpoint . '?key=' . $apiKey;

        Log::info('Fetching networks from API', ['url' => $url]);

        $response = Http::timeout(30)
            ->retry(3, 1000)
            ->get($url);

        if (!$response->successful()) {
            throw new RequestException($response);
        }

        $data = $response->json();

        if (!is_array($data)) {
            Log::warning('Invalid API response format', ['response' => $response->body()]);
            return [];
        }

        Log::info('Successfully fetched networks from API', ['count' => count($data)]);

        return $data;
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncAdvertismentNetworksJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
