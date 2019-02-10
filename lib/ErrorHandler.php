<?php
 /**
  * Перехватываем и логирируем ошибки + необработанные исключения
  *
  * Условия использования - инициализация в самом начале index.php
  * ERROR_REPORTING = E_ALL
 */

 class ErrorHandler
 {
 	protected $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
        E_COMPILE_WARNING, E_USER_ERROR, E_RECOVERABLE_ERROR];

	public function __construct()
    {
		// обычные ошибки
		set_error_handler(array($this, 'otherErrorHandler'));

		// регистрация критических ошибок
		register_shutdown_function(array($this, 'fatalErrorHandler'));

		// перехват исключений
		set_exception_handler(array($this, 'exceptionHandler'));

		// создание буфера вывода
		ob_start();
	}

	/**
     * Обычные ошибки
     * @param
     *
     */
	private function otherErrorHandler($errno, $errmsg, $filename, $linenum)
    {
		$error = [
			'type' => $errno,
			'message' => $errmsg,
			'file'	=> $filename,
			'line'	=> $linenum
		];

		if(!in_array($errno, $this->fatalErrors))
			$this->processing('', $error);

		return;
	}

	/**
     * Фатальные ошибки
	*/
	public function fatalErrorHandler()
    {
		$error = error_get_last();
		if(isset($error) && in_array($error['type'], $this->fatalErrors)) {
			ob_end_clean();

			// обработка ошибки
			$this->processing('_fatal', $error);

			// выдаем страницу 500
			header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING).' 500 Internal Server Error', true, 500);
			include(ROOT.'/template/500.php');
			exit;
		}
		else {
			ob_end_flush();	// вывод буфера, завершить работу буфера
		}
	}

	/**
	 * Exception handler
	 *
	 * @param Exception $e
	 */
	public function exceptionHandler($e) {
		$error = [
			'type' => $e->getCode(),
			'message' => $e->getMessage(),
			'file'	=> $e->getFile(),
			'line'	=> $e->getLine()
		];

		$this->processing('_exception', $error);
	}

	/**
	 * My Exception handler для сохранения исключений
	 *
	 * @param array $error
	 */
	public static function baseExceptionHandler($error=[]) {
		self::processing('_base_exception', $error);
	}

     /**
      * Сделан статичной для вызова в своих Exceptions
      *
      * @param string $postfix
      * @param array $error
      *
      */
	protected static function processing($postfix, $error=[]) {
		if(empty($error))
			return;

		/* Заголовки ошибок для фиксации в логах */
		$errors = [
            E_ERROR => 'Fatal error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Fatal error (PHP core)',
            E_CORE_WARNING => 'Warning (PHP core)',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'Strict standarts',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    	];


		$error['error_type'] = $type = !empty($errors[$error['type']]) ? '<b>'.$errors[$error['type']].'</b>: ' : '';
		$message = '<div style="margin:10px 0; width:100%">'.$type.$error['message'].' ('.$error['file'].' on line <b>'.$error['line'].'</b>)</div>'.EOL.EOL;

		/* В режиме разработки просто показываем */
		if(IN_DEV) {
			echo $message;
		}
		else {
			$error_file = 'error'.$postfix.'.log';
			self::saveErrorData($error, $error_file);
		}

	}

	/**
	 * User Message Exception handler - фиксируем что юзеры получают
     *
     * @param array $error
     * @param int $fatal Флаг для фиксации в логе фатальных ошибок
	 */
	public static function userExceptionHandler($error=[], $fatal=0) {

		$fatal = $fatal ? '_fatal' : '';
		$error_file = 'user'.$fatal.'_exception.log';

		self::saveErrorData($error, $error_file);
	}

	/**
	* Подготовка и сохранение подробных данных ошибки
     *
     * @param array $error
     * @param string $error_file Имя файла лога
	*/
	public static function saveErrorData($error=[], $error_file='') {

        if(empty($error_file))
        	$error_file = 'error_fatal.log';

        $data = [
			'url' => filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING),
			'referer' => filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING)
		];

		/* Фиксируем user_id если пользователь был залогинен */
		if(class_exists('MF') && !empty(MF::$user->user_id))
			$data['user_id'] = MF::$user->user_id;

		$add[time()] = $error + $data;

		$error_file = ROOT.'/data/logs/errors/'.$error_file;
		/* В каждой строке json-объект - потом легко анализировать */
		if(!is_file($error_file) || (is_file($error_file) && filesize($error_file)<1048576))
			file_put_contents($error_file, json_encode($add, JSON_UNESCAPED_UNICODE).EOL, FILE_APPEND);
	}

 }
