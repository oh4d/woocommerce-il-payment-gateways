<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'gateway_url' => 'https://icom.yaad.net/p/',

    'gateway_url_2' => 'https://pay.leumicard.co.il/p/',

    'languages' => [
        'en_US' => 'ENG',
        'he_IL' => 'HEB',
        'default' => 'ENG'
    ],

    'currencies' => [
        'ILS' => 1,
        'USD' => 2,
        'EUR' => 3,
        'GBP' => 3,
        'default' => 1
    ]
];