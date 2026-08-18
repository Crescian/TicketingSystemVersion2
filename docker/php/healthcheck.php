<?php

// Lightweight container health probe: verifies the app can actually boot
// and reach Postgres, not just that a PHP process exists. Deliberately not
// `artisan tinker` — its psysh REPL tries to write a history/config file
// under the executing user's home dir, which fails for non-root users
// (queue-worker runs as uid 33) even when the DB connection itself is fine.

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\DB::connection()->getPdo();

echo "ok\n";
