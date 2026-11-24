<?php

declare(strict_types=1);

use AbTesting\Tests\Support\TestRunner;

require dirname(__DIR__) . '/src/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'AbTesting\\Tests\\';
    if (str_starts_with($class, $prefix)) {
        $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

$reportDirectory = null;
$arguments = array_slice((array) ($_SERVER['argv'] ?? []), 1);
if (count($arguments) === 2 && $arguments[0] === '--report-dir' && is_string($arguments[1])) {
    $reportDirectory = $arguments[1];
} elseif ($arguments !== []) {
    fwrite(STDERR, "Usage: php tests/run.php [--report-dir DIRECTORY]\n");
    exit(2);
}

$runner = new TestRunner(__DIR__ . '/Unit');
$passed = $runner->run();
if ($reportDirectory !== null) {
    $runner->writeReports($reportDirectory);
}
exit($passed ? 0 : 1);
