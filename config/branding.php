<?php

return [
    // Fallbacks used until a SuperAdmin sets the values from Admin → Branding.
    'app_name' => env('BRANDING_APP_NAME', 'NA E-Ticket'),
    'app_motto' => env('BRANDING_APP_MOTTO', 'Secure • Offline-Capable • Nigerian Army'),

    // Expo EAS build page for the soldier APK.
    'download_url' => env('BRANDING_DOWNLOAD_URL', 'https://expo.dev/accounts/adetone007/projects/na-soldier-app/builds/23929e09-c80c-414d-96d2-cb2a98f152df'),
];
