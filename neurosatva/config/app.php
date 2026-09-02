<?php

return [
    'name' => env('APP_NAME', 'Neurosatva'),
    'url' => rtrim(env('APP_URL', ''), '/'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'admin_video_email' => env('ADMIN_VIDEO_EMAIL', 'videos@neurosatva.local'),
    'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL', 'contactus@raaksapphire.com'),
    'resend_api_key' => env('RESEND_API_KEY', 're_UjxVfKnT_6iykNtVsJJhzDXr1eGRDRPCE'),
    'resend_from_email' => env('RESEND_FROM_EMAIL', 'Neurosatva <onboarding@resend.dev>'),
    'storage_disk' => env('STORAGE_DISK', 'server'),
];
