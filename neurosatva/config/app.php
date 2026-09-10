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
    'storage_disk' => env('STORAGE_DISK', 'r2'),
    'r2_account_id' => env('R2_ACCOUNT_ID', ''),
    'r2_access_key_id' => env('R2_ACCESS_KEY_ID', ''),
    'r2_secret_access_key' => env('R2_SECRET_ACCESS_KEY', ''),
    'r2_bucket' => env('R2_BUCKET', 'modules'),
    'r2_public_url' => env('R2_PUBLIC_URL', ''),
    'supabase_url' => env('SUPABASE_URL', 'https://qdzjlqyzppwkcjlvkbnr.supabase.co'),
    'supabase_key' => env('SUPABASE_KEY', 'sb_publishable_41mduo6brjTVTCyupvPEGA_Lbkbbagd'),
    'supabase_bucket' => env('SUPABASE_BUCKET', 'modules'),
];
