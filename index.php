<?php
/**
 * Демо файл
 */

define('ROOT', filter_input(INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING));
define('EOL', "\r\n");

define('IN_DEV', filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING) === '127.0.0.1');

include ROOT.'/lib/ErrorHandler.php';
$errorController = new ErrorHandler();

include ROOT.'/lib/Exceptions.php';

