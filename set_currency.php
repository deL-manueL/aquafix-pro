<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/currency.php';

$code = $_GET['c'] ?? 'USD';
set_currency($code);

/* Redirect back to where the user came from */
$back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
redirect($back);