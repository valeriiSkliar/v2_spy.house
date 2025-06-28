<?php

namespace Database\Seeders;

use App\Models\AdvertismentNetwork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdvertismentNetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Disable foreign key checks before truncating
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate the AdvertismentNetwork table before seeding
        AdvertismentNetwork::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $adNetworks = [
            'rexrtb',
            'adoperator',
            'adeum',
            'rollerads',
            'richads',
            'clickadilla',
            'galaksion',
            'ezmob',
            'mondiad',
            'pushground',
            'kadam',
            'evadav',
            'adbison',
            'dao',
            'adscompass',
            'ppcbuzz',
            'pushub',
            'mintads',
            'adright',
            'clikaine',
            'adtarget',
            'adguru',
            'adx_ad',
            'targeleon',
            'hilltopads',
            'epom',
            'ads2bid',
            'popcash',
            'clickstar',
            'adskeeper',
            'powerpush',
            'traffic_nomads',
            'adsteroid',
            'rivertraffic',
            '22_bet',
            'asoads',
            'adsbravo',
            'exoclick',
            'mgid',
            'inhousead',
            'pushflow',
            'heartbid',
            'ksaazaks_test',
            'mybidwebpush',
            'idenzu',
            'plugrush',
            'pushkeeper',
            'admeking',
            'hastraffic',
            'mobivion',
            'time2ads',
            'r2d',
            'pushatomic',
            'yeesshh',
            'harambe',
            'pushhouse',
        ];

        $sourceDirectory = database_path('mockData/networks_icons');
        $destinationDirectory = 'assets/images/adNetworksIcons';

        // Ensure destination directory exists
        if (!Storage::disk('public')->exists($destinationDirectory)) {
            Storage::disk('public')->makeDirectory($destinationDirectory);
            Log::info("Created directory: $destinationDirectory");
        }

        foreach ($adNetworks as $name) {
            $iconPath = "$sourceDirectory/$name.ico";
            $publicPath = "$destinationDirectory/$name.ico";

            $networkDisplayName = ucwords(str_replace('_', ' ', $name));
            if (file_exists($iconPath)) {
                if (!Storage::disk('public')->exists($publicPath)) {
                    Storage::disk('public')->put($publicPath, file_get_contents($iconPath));
                    Log::info("File $iconPath was copied to $publicPath");
                } else {
                    Log::info("File $publicPath already exists, using existing file.");
                }
                AdvertismentNetwork::factory()->create([
                    'network_display_name' => $networkDisplayName,
                    'network_name' => $name,
                    'network_logo' => Storage::url($publicPath),
                    'is_active' => true
                ]);
            } else {
                Log::info("File $iconPath does not exist");
                AdvertismentNetwork::factory()->create([
                    'network_display_name' => $networkDisplayName,
                    'network_name' => $name,
                    'is_active' => true
                ]);
            }
        }
    }
}
