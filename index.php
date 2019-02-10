<?php
/* Для перехвата ошибок запускается первым */
include 'lib/ErrorHandler.php';
$errorHandler = new ErrorHandler();

define('ROOT', filter_input(INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING));
define('EOL', "\r\n");

define('IN_DEV', filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING) === '127.0.0.1');

include 'lib/Exceptions.php';

echo 'Some demo files';