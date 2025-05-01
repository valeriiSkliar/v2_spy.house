<?php

return [
    'downloadFailedAuthorization' => [
        'title' => 'Download failed',
        'description' => 'You are not authorized to download this landing page.',
    ],
    'downloadException' => [
        'title' => 'Download failed',
        'description' => 'An error occurred while downloading the landing page.',
    ],
    'sourceFolderNotFound' => [
        'title' => 'Source folder not found',
        'description' => 'The source folder for the landing page was not found.',
    ],
    'downloadCancelledByUser' => [
        'title' => 'Download Cancelled',
        'description' => 'Download cancelled by user.',
    ],
    'alreadyInProgress' => [
        'title' => 'Download in Progress',
        'description' => 'A download for this URL is already in progress or pending.',
    ],
    'alreadyDownloaded' => [
        'title' => 'Already Downloaded',
        'description' => 'This landing page has already been downloaded.',
    ],
    'duplicateDownload' => [
        'title' => 'Duplicate Request',
        'description' => 'A download request for this URL already exists.',
    ],
    'downloadFailedUrlDisabled' => [
        'title' => 'Download Failed',
        'description' => 'Failed to access the provided URL. It might be unavailable.',
    ],
    'antiFlood' => [
        'title' => 'Limit Reached',
        'description' => 'You have reached the download limit. Please try again later.',
    ],
    'downloadStarted' => [
        'title' => 'Download Started',
        'description' => 'Your landing page download has started.',
    ],
    'generalError' => [
        'title' => 'Error',
        'description' => 'An unexpected error occurred. Please try again.',
    ],
    'downloadCancelled' => [
        'title' => 'Download Cancelled',
        'description' => 'The download has been successfully cancelled.',
    ],
    'downloadAlreadyCompleted' => [
        'title' => 'Already Completed',
        'description' => 'This download has already been completed and cannot be cancelled.',
    ],
    'downloadNotInProgress' => [
        'title' => 'Not In Progress',
        'description' => 'This download is not currently pending or in progress.',
    ],
    'index' => [
        'title' => 'Landings',
        'sort' => [
            'placeholder' => 'Sort By — :default',
            'options' => [
                'newest' => 'Newest First',
                'oldest' => 'Oldest First',
                'status_az' => 'Status (A-Z)',
                'status_za' => 'Status (Z-A)',
                'url_az' => 'URL (A-Z)',
                'url_za' => 'URL (Z-A)',
            ],
        ],
        'pagination' => [
            'placeholder' => 'On page — :default',
            'options' => [
                '12' => '12',
                '24' => '24',
                '48' => '48',
                '96' => '96',
            ],
        ],
    ],
    'form' => [
        'urlPlaceholder' => 'Enter the link to download the Landing Page',
        'waitForDownloads' => 'Wait for current downloads to finish',
        'submitButton' => 'Download',
    ],
    'table' => [
        'header' => [
            'id' => 'ID',
            'downloadLink' => 'Download Link',
            'dateAdded' => 'Date Added',
        ],
        'confirmDelete' => [
            'message' => 'Are you sure you want to delete this landing? This action cannot be undone.',
            'title' => 'Confirm Deletion',
            'confirmButton' => 'Delete',
            'cancelButton' => 'Cancel',
        ],
    ],
];
