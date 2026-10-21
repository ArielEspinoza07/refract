<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

/**
 * A handler with every kind of entry point a container can call.
 */
final class SendNewsletter
{
    public function __invoke(Newsletter $newsletter): string
    {
        return "Newsletter sent from {$newsletter->mailer->from}";
    }

    public function handle(Mailer $mailer, int $copies = 1): string
    {
        return "{$copies} copies sent from {$mailer->from}";
    }

    public static function preview(Logger $logger): string
    {
        $logger->log('Preview requested');

        return 'Preview logged';
    }
}
