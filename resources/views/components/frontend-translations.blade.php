@php
// Get all frontend translations for current locale
$locale = app()->getLocale();
$translationFile = base_path("lang/{$locale}/frontend.php");
$translations = [];

if (file_exists($translationFile)) {
$translations = include $translationFile;
} else {
\Log::warning("Translation file not found: {$translationFile}");
}
@endphp

<script>
    window.laravelTranslations = {
    frontend: @json($translations),
    locale: '{{ $locale }}',
    debug: {
        fileExists: {{ file_exists($translationFile) ? 'true' : 'false' }},
        filePath: '{{ $translationFile }}',
        translationsCount: {{ count($translations) }}
    }
};
console.log('Translations loaded:', window.laravelTranslations);
</script>