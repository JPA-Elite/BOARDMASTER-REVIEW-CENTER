<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    |
    | Max upload size in kilobytes. 10 MB = 10240 KB.
    |
    */
    'max_file_kb' => (int) env('UPLOAD_MAX_FILE_KB', 10240),

    /*
    |--------------------------------------------------------------------------
    | Image Compression
    |--------------------------------------------------------------------------
    |
    | Default quality used when compressing images with Intervention Image.
    | 0 = lowest quality, 100 = highest quality.
    |
    */
    'image_quality' => (int) env('UPLOAD_IMAGE_QUALITY', 75),

    /*
    |--------------------------------------------------------------------------
    | Upload Disks
    |--------------------------------------------------------------------------
    |
    | Local environment uploads use the local disk. Non-local environments
    | use the remote disk (S3).
    |
    */
    'local_disk' => env('UPLOAD_LOCAL_DISK', 'public'),
    'remote_disk' => env('UPLOAD_REMOTE_DISK', 's3'),
];

