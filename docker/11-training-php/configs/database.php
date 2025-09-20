<?php
define('DB_HOST', getenv('DB_HOST') ?: 'web-mysql');
define('DB_USER', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'pass');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_DATABASE') ?: 'app_web1');
