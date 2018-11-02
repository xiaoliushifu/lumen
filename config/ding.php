<?php

return [

    // 默认发送的机器人

    'default' => [
        // 是否要开启机器人，关闭则不再发送消息
        'enabled' => env('DING_ENABLED',true),
        // 机器人的access_token
        'token' => env('DING_TOKEN','4822f242fa710c7e43568bd8f1548fa1953e8b047a23422d5521c919d63c1284'),
        // 钉钉请求的超时时间
        'timeout' => env('DING_TIME_OUT',2.0)
    ],

    'other' => [
        'enabled' => env('OTHER_DING_ENABLED',true),

        'token' => env('OTHER_DING_TOKEN',''),

        'timeout' => env('OTHER_DING_TIME_OUT',2.0)
    ]

];