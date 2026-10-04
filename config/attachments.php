<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attachment Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk that new attachments are stored on. Use a private
    | disk: files are only ever served through the app, after checking that
    | the person may see the issue. Each attachment remembers the disk it was
    | saved to, so changing this doesn't strand files that already exist.
    | Using an S3-compatible disk needs `league/flysystem-aws-s3-v3`.
    |
    */

    'disk' => env('ATTACHMENTS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | The largest a single file can be, in kilobytes, and how many files can be
    | uploaded at once. Your PHP `upload_max_filesize` and `post_max_size`
    | settings must be at least as large as these. There is no limit per
    | board or per issue.
    |
    */

    'max_size_kb' => (int) env('ATTACHMENTS_MAX_SIZE_KB', 10240),

    'max_files' => 10,

    /*
    |--------------------------------------------------------------------------
    | Allowed Types
    |--------------------------------------------------------------------------
    |
    | File extensions that can be uploaded. Anything that a browser would run
    | or render as a page (HTML, SVG, scripts) is left out and also refused by
    | content, whatever it is named.
    |
    */

    'extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'txt', 'md', 'csv', 'json', 'log', 'rtf',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
        'zip', 'gz', 'tar', '7z',
        'mp4', 'mov', 'webm', 'mp3', 'wav', 'm4a',
    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    |
    | Pictures of these types are shown in the browser and get a thumbnail;
    | every other file is downloaded. The pixel limit protects against images
    | that are small to upload but huge to decode: larger ones are stored
    | without a thumbnail.
    |
    */

    'inline_mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],

    'max_thumbnail_pixels' => 36_000_000,

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | Deleted attachments stay on disk for this many days before the daily
    | `attachments:prune` command removes the files for good.
    |
    */

    'prune_after_days' => (int) env('ATTACHMENTS_PRUNE_AFTER_DAYS', 30),

];
