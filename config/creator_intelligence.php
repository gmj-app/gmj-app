<?php

return [
    'import_disk' => env('CREATOR_INTELLIGENCE_IMPORT_DISK', 'local'),
    'max_upload_kilobytes' => (int) env('CREATOR_INTELLIGENCE_MAX_UPLOAD_KB', 51200),
    'max_archive_entries' => (int) env('CREATOR_INTELLIGENCE_MAX_ARCHIVE_ENTRIES', 100),
    'max_archive_uncompressed_bytes' => (int) env('CREATOR_INTELLIGENCE_MAX_ARCHIVE_BYTES', 268435456),
    'max_csv_line_bytes' => (int) env('CREATOR_INTELLIGENCE_MAX_CSV_LINE_BYTES', 1048576),
];
