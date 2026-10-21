<?php

declare(strict_types=1);

/*
 * Builds objects from their constructors with a tiny container. Run it with: php examples/container.php
 */

use Refract\Examples\Container\Container;
use Refract\Examples\Container\Logger;
use Refract\Examples\Container\MemoryLogger;
use Refract\Examples\Container\Newsletter;
use Refract\Examples\Container\Report;
use Refract\Examples\Container\UnresolvableDependency;

require __DIR__.'/../vendor/autoload.php';

$container = new Container();
$container->bind(Logger::class, MemoryLogger::class);

$newsletter = $container->make(Newsletter::class);

echo 'Newsletter mailer logs to: ', $newsletter->mailer->logger::class, PHP_EOL;
echo 'Newsletter mailer sends from: ', $newsletter->mailer->from, PHP_EOL;
echo 'Newsletter clock: ', var_export($newsletter->clock, true), PHP_EOL;

try {
    $container->make(Report::class);
} catch (UnresolvableDependency $exception) {
    echo 'Report: ', $exception->getMessage(), PHP_EOL;
}
