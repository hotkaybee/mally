<?php

/*
 * `composer dev`: server + queue listener + log tail + Vite, side by side.
 *
 * Windows needs two exceptions (macOS/Linux run exactly as before):
 * - Pail requires the pcntl extension, which PHP on Windows doesn't have; it
 *   would exit at once and --kill-others would stop everything else.
 * - `artisan serve` without --no-reload starts PHP with a stripped
 *   environment and then fails with "Failed to listen ... (reason: ?)".
 */

$windows = PHP_OS_FAMILY === 'Windows';

$processes = [
    'server' => $windows ? 'php artisan serve --no-reload' : 'php artisan serve',
    // --timeout=0 as in the Laravel skeleton: a slow job (or a busy machine)
    // must not hit the 60 s default and take every other process down.
    'queue' => 'php artisan queue:listen --tries=1 --timeout=0',
    'logs' => $windows ? null : 'php artisan pail --timeout=0',
    'vite' => 'npm run dev',
    // Answers the Telegram login bot at once (idles when a webhook is set).
    'bot' => 'php artisan telegram:auth-poll',
    // Admin bot: order alerts, order/SKU lookups (idles when not configured).
    'admin' => 'php artisan telegram:admin-poll',
];
$colors = ['server' => '#93c5fd', 'queue' => '#c4b5fd', 'logs' => '#fb7185', 'vite' => '#fdba74', 'bot' => '#86efac', 'admin' => '#fde047'];

$processes = array_filter($processes);

$command = sprintf(
    'npx concurrently -c "%s" %s --names=%s --kill-others',
    implode(',', array_intersect_key($colors, $processes)),
    implode(' ', array_map(fn (string $cmd) => '"'.$cmd.'"', $processes)),
    implode(',', array_keys($processes)),
);

passthru($command, $exitCode);

exit($exitCode);
