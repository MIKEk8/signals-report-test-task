<?php
// Настройки берутся из переменных окружения (см. docker-compose.yml), иначе значения по умолчанию.

function app_env($key, $default = '')
{
	$value = getenv($key);
	return ($value === false || $value === '') ? $default : $value;
}

define('DB_HOST', app_env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int)app_env('DB_PORT', '3306'));
define('DB_NAME', app_env('DB_NAME', 'signals'));
define('DB_USER', app_env('DB_USER', 'app'));
define('DB_PASS', app_env('DB_PASS', 'app'));

// «Сегодня» для отчёта в формате Y-m-d H:i:s. Пусто = реальная текущая дата.
define('APP_NOW', app_env('APP_NOW', ''));
