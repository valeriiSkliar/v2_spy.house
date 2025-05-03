<?php

return [
    'index' => [
        'title' => 'Landings',
        'sort' => [
            'placeholder' => 'Ordenar por — :default',
            'options' => [
                'newest' => 'Más reciente primero',
                'oldest' => 'Más antiguo primero',
                'status_az' => 'Estado (A-Z)',
                'status_za' => 'Estado (Z-A)',
                'url_az' => 'URL (A-Z)',
                'url_za' => 'URL (Z-A)',
            ],
        ],
        'pagination' => [
            'placeholder' => 'En la página — :default',
            'options' => [
                '12' => '12',
                '24' => '24',
                '48' => '48',
                '96' => '96',
            ],
        ],
    ],
    'form' => [
        'urlPlaceholder' => 'Introduce el enlace para descargar la Página de Aterrizaje',
        'waitForDownloads' => 'Espera a que finalicen las descargas actuales',
        'submitButton' => 'Descargar',
    ],
    'table' => [
        'header' => [
            'id' => 'ID',
            'downloadLink' => 'Enlace de descarga',
            'dateAdded' => 'Fecha de adición',
        ],
        'confirmDelete' => [
            'message' => '¿Estás seguro de que quieres eliminar este landing? Esta acción no se puede deshacer.',
            'title' => 'Confirmar eliminación',
            'confirmButton' => 'Eliminar',
            'cancelButton' => 'Cancelar',
        ],
        'status' => [
            'pending' => 'Pendiente',
            'completed' => 'Completado',
            'failed' => 'Fallado',
        ],
    ],
    'downloadFailedAuthorization' => [
        'title' => 'Descarga fallida',
        'description' => 'No tienes autorización para descargar esta página de aterrizaje.',
    ],
    'downloadException' => [
        'title' => 'Descarga fallida',
        'description' => 'Ocurrió un error al descargar la página de aterrizaje.',
    ],
    'sourceFolderNotFound' => [
        'title' => 'Carpeta de origen no encontrada',
        'description' => 'La carpeta de origen para la página de aterrizaje no fue encontrada.',
    ],
    'downloadCancelledByUser' => [
        'title' => 'Descarga cancelada',
        'description' => 'Descarga cancelada por el usuario.',
    ],
    'alreadyInProgress' => [
        'title' => 'Descarga en progreso',
        'description' => 'Una descarga para esta URL ya está en progreso o pendiente.',
    ],
    'alreadyDownloaded' => [
        'title' => 'Descarga ya completada',
        'description' => 'Esta página de aterrizaje ya ha sido descargada.',
    ],
    'duplicateDownload' => [
        'title' => 'Solicitud duplicada',
        'description' => 'Ya existe una solicitud de descarga para esta URL.',
    ],
    'downloadFailedUrlDisabled' => [
        'title' => 'Descarga fallida',
        'description' => 'No se pudo acceder al URL proporcionado. Es posible que no esté disponible.',
    ],
    'antiFlood' => [
        'title' => 'Límite alcanzado',
        'description' => 'Has alcanzado el límite de descargas. Por favor, inténtalo de nuevo más tarde.',
    ],
    'downloadStarted' => [
        'title' => 'Descarga iniciada',
        'description' => 'Tu descarga de página de aterrizaje ha comenzado.',
    ],
    'generalError' => [
        'title' => 'Error',
        'description' => 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo.',
    ],
    'downloadCancelled' => [
        'title' => 'Descarga cancelada',
        'description' => 'La descarga se ha cancelado correctamente.',
    ],
    'downloadAlreadyCompleted' => [
        'title' => 'Descarga ya completada',
        'description' => 'Esta descarga ya ha sido completada y no puede ser cancelada.',
    ],
    'downloadNotInProgress' => [
        'title' => 'Descarga no en progreso',
        'description' => 'Esta descarga no está actualmente pendiente o en progreso.',
    ],

];
