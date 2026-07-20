<?php

return [
    // 应用凭证（对应 .env [FEISHU] 段）
    'app_id'     => env('FEISHU.APP_ID', ''),
    'app_secret' => env('FEISHU.APP_SECRET', ''),
    // 授权回调地址（须与飞书开放平台配置一致，指向前端登录页）
    'redirect_uri' => env('FEISHU.REDIRECT_URI', ''),
];
