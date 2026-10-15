<?php

return [
    // Fallbacks used until a SuperAdmin sets the values from Admin → Branding.
    'app_name' => env('BRANDING_APP_NAME', 'NA E-Ticket'),
    'app_motto' => env('BRANDING_APP_MOTTO', 'Secure • Offline-Capable • Nigerian Army'),

    // Expo EAS build page for the soldier APK.
    'download_url' => env('BRANDING_DOWNLOAD_URL', 'https://expo.dev/accounts/adetone007/projects/na-soldier-app/builds/a8e62541-65ba-44aa-af00-a31e0555ee5a'),
];
