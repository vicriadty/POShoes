<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Messaging (PRD 7.9)
    |--------------------------------------------------------------------------
    |
    | provider: fake | meta
    | Default fake untuk development/test; meta untuk production.
    */

    'provider' => env('WHATSAPP_PROVIDER', 'fake'),

    'default' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'graph_url' => env('WHATSAPP_GRAPH_URL'),
    ],

    'webhook' => [
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'signature_secret' => env('WHATSAPP_WEBHOOK_SIGNATURE_SECRET'),
    ],

    'templates' => [
        'order_received' => [
            'name' => env('WHATSAPP_TEMPLATE_ORDER_RECEIVED', 'order_received'),
            'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),
        ],
        'invoice' => [
            'name' => env('WHATSAPP_TEMPLATE_INVOICE', 'invoice_ready'),
            'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),
        ],
    ],
];
