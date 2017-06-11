<?php

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/vendor/autoload.php';

$f3 = $f3 = Base::instance();

$f3->set('DEBUG', 0);
$f3->set('version', '2.17-SNAPSHOT');
$f3->set('AUTOLOAD', false);
$f3->set('cache', __DIR__ . '/data/cache');
$f3->set('BASEDIR', __DIR__);
$f3->set('LOCALES', __DIR__ . '/public/lang/');

// read defaults
$f3->config('defaults.ini');

// read config, if it exists
if (file_exists('config.ini')) {
    $f3->config('config.ini');
}

// overwrite config with ENV variables
$env_prefix = $f3->get('env_prefix');
foreach ($f3->get('ENV') as $key => $value) {
    if (strncasecmp($key, $env_prefix, strlen($env_prefix)) == 0) {
        $f3->set(strtolower(substr($key, strlen($env_prefix))), $value);
    }
}

// init logger
$log = new Logger('selfoss');
if ($f3->get('logger_level') !== 'NONE') {
    $handler = new StreamHandler(__DIR__ . '/data/logs/default.log', $f3->get('logger_level'));
    $formatter = new LineFormatter(null, null, true, true);
    $formatter->includeStacktraces(true);
    $handler->setFormatter($formatter);
    $log->pushHandler($handler);
}
$f3->set('logger', $log);

// init error handling
$f3->set('ONERROR',
    function($f3) {
        $exception = $f3->get('EXCEPTION');

        if ($exception) {
            \F3::get('logger')->error($exception->getMessage(), ['exception' => $exception]);
        } else {
            \F3::get('logger')->error($f3->get('ERROR.text'));
        }

        if (\F3::get('DEBUG') != 0) {
            echo $f3->get('lang_error') . ': ';
            echo $f3->get('ERROR.text') . "\n";
            echo $trace;
        } else {
            echo $f3->get('lang_error');
        }
    }
);

if (\F3::get('DEBUG') != 0) {
    ini_set('display_errors', 0);
}
