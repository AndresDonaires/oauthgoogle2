<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Brevo SMTP redirige a un servidor regional con CN distinto.
        // Laravel 12 no expone opciones SSL en su config de mail,
        // así que extendemos el driver para desactivar verify_peer.
        Mail::extend('smtp', function (array $config) {
            $scheme = $config['scheme'] ?: 'smtp';

            $transport = (new EsmtpTransportFactory())->create(new Dsn(
                $scheme,
                $config['host'],
                $config['username'] ?? null,
                $config['password'] ?? null,
                $config['port'] ?? null,
                $config
            ));

            $stream = $transport->getStream();
            if ($stream instanceof SocketStream) {
                $stream->setStreamOptions([
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ]);
            }

            return $transport;
        });
    }
}
