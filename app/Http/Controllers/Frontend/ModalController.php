<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModalController extends Controller
{
    /**
     * Load a modal content via AJAX
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function loadModal(Request $request)
    {
        $modalType = $request->input('type');
        $data = [];

        // Dynamically load modal content based on requested type
        switch ($modalType) {
            case 'contact':
                $data = [
                    'managers' => [
                        [
                            'name' => 'Maksim',
                            'telegram' => '@Max_spy_house',
                            'photo' => '/img/manager-1.png'
                        ],
                        [
                            'name' => 'Telegram chat',
                            'telegram' => '@spy_house_chat',
                            'photo' => '/img/manager-2.svg'
                        ]
                    ]
                ];
                return view('modals.contact', $data);

            case 'delete-confirmation':
                $itemId = $request->input('item_id');
                $itemType = $request->input('item_type');
                $deleteUrl = $request->input('delete_url');

                return view('modals.delete-confirmation', [
                    'itemId' => $itemId,
                    'itemType' => $itemType,
                    'deleteUrl' => $deleteUrl
                ]);

            case 'service-details':
                $serviceId = $request->input('service_id');
                // In real app, you would load the service from database
                $service = [
                    'id' => $serviceId,
                    'name' => 'Service Demo',
                    'description' => 'Full service description loaded via AJAX'
                ];

                return view('modals.service-details', ['service' => $service]);

            default:
                return response()->json(['error' => 'Invalid modal type'], 400);
        }
    }
}
