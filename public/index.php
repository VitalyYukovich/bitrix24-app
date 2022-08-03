<?php
	header('X-Frame-Op4tions: SAMESITE');
	header('X-Frame-Op4tions: 1');
	header('X-Frame-Op4tions: 2');
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
