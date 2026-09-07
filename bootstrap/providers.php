<?php

use App\Providers\AppServiceProvider;
use App\Providers\MessagingServiceProvider;
use Barryvdh\DomPDF\ServiceProvider;

return [
    AppServiceProvider::class,
    MessagingServiceProvider::class,
    ServiceProvider::class,
];
