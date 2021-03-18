<?php
return [
    'is_me_cookie' => env('IS_ME_COOKIE', 'no is me cookie'),
    'telegram_channel_id' => env('TELEGRAM_CHANNEL_ID', 'no telegram channel id'),
    'webwork_seed' =>env('WEBWORK_SEED', 'no webwork seed'),
    'imathas_seed' =>env('IMATHAS_SEED_SEED', 'no imathas seed'),
    'jwt_secret' => env('JWT_SECRET',null)
];