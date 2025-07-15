<?php

return [
    'index' => [
        'title' => 'Landings',
    ],
    'success' => [
        'download_ready' => 'Landing is ready for download',
        'successfully_deleted_message_text' => 'Landing successfully deleted',
        'successfully_loaded_message_text' => 'Landings successfully loaded',
    ],
    'errors' => [
        'file_not_found' => 'File not found',
        'not_completed' => 'Landing not completed',
        'error_occurred' => 'An error occurred',
        'download_failed' => 'Download failed',
    ],
    'form' => [
        'urlPlaceholder' => 'Enter link to download landing',
        'waitForDownloads' => 'Wait for current downloads to complete',
        'submitButton' => 'Download',
    ],
    'table' => [
        'header' => [
            'id' => 'ID',
            'downloadLink' => 'Landing link',
            'dateAdded' => 'Date added',
        ],
        'confirmDelete' => [
            'message' => 'Are you sure you want to delete this landing? This action is irreversible.',
            'title' => 'Confirm deletion',
            'confirmButton' => 'Delete',
            'cancelButton' => 'Cancel',
        ],
        'status' => [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ],
    ],
    'downloadFailedAuthorization' => [
        'title' => 'Download error',
        'description' => 'You do not have permission to download this landing.',
    ],
    'downloadException' => [
        'title' => 'Download error',
        'description' => 'An error occurred while downloading the landing.',
    ],
    'sourceFolderNotFound' => [
        'title' => 'Source folder not found',
        'description' => 'Source folder for the landing was not found.',
    ],
    'downloadCancelledByUser' => [
        'title' => 'Download cancelled',
        'description' => 'Download cancelled by user.',
    ],
    'alreadyInProgress' => [
        'title' => 'Download already in progress',
        'description' => 'Download for this URL is already in progress or queued.',
    ],
    'alreadyDownloaded' => [
        'title' => 'Already downloaded',
        'description' => 'This landing has already been downloaded.',
    ],
    'duplicateDownload' => [
        'title' => 'Duplicate request',
        'description' => 'Download request for this URL already exists.',
    ],
    'downloadFailedUrlDisabled' => [
        'title' => 'Download error',
        'description' => 'Could not access the specified URL. It may be unavailable.',
    ],
    'antiFlood' => [
        'title' => 'Limit reached',
        'description' => 'You have reached the download limit. Please try again later.',
    ],
    'downloadStarted' => [
        'title' => 'Download started',
        'description' => 'Your landing download has started.',
    ],
    'generalError' => [
        'title' => 'Error',
        'description' => 'An unexpected error occurred. Please try again.',
    ],
    'downloadCancelled' => [
        'title' => 'Download cancelled',
        'description' => 'Download was successfully cancelled.',
    ],
    'downloadAlreadyCompleted' => [
        'title' => 'Already completed',
        'description' => 'This download has already been completed and cannot be cancelled.',
    ],
    'downloadNotInProgress' => [
        'title' => 'Not in progress',
        'description' => 'This download is not currently pending or in progress.',
    ],
    'download' => [
        'error_details' => 'Error details',
        'status' => [
            'started' => [
                'title' => 'Website download started: :url',
                'message' => 'Download of website :url has started. You will receive a notification when completed.',
            ],
            'completed' => [
                'title' => 'Website download completed: :url',
                'message' => 'Download of website :url has been successfully completed.',
            ],
            'failed' => [
                'title' => 'Website download failed: :url',
                'message' => 'Failed to download website :url.',
            ],
        ],
    ],
];
