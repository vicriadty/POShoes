<?php

namespace App\Providers;

use App\Domain\Messaging\Adapters\FakeMessagingService;
use App\Domain\Messaging\Adapters\MetaWhatsAppCloudService;
use App\Domain\Messaging\Contracts\MessagingService;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessagingService::class, function () {
            $provider = (string) config('messaging.provider', 'fake');

            if ($provider === 'meta') {
                return new MetaWhatsAppCloudService(
                    (string) config('messaging.default.access_token'),
                    (string) config('messaging.default.phone_number_id'),
                    (string) config('messaging.default.api_version', 'v21.0'),
                    config('messaging.default.graph_url'),
                );
            }

            return new FakeMessagingService(
                config('messaging.webhook.verify_token'),
            );
        });
    }
}
