<?php
/*	Для демо сведено в один файл */

/**
 * Базовый класс для своих Exception
 */
class BaseException extends Exception
{
    /**
     * @param string $message
     * @param int code
     */
    public function __construct($message='', $code=0)
    {
        /* Включаем если событие надо передать выше по цепочке */
        //parent::__construct($message, $code);

        $error = [
            'type' => $this->getCode(),
            'message' => __CLASS__ .': <b>'. $message.'</b>',
            'file'	=> $this->getFile(),
            'line'	=> $this->getLine()
        ];
        ErrorHandler::baseExceptionHandler($error);
    }


}

/**
 * Сообщения об ошибках - выдаются на сайте для юзеров
 */
class UserMessageException extends BaseException
{
    /**
     * @param string $message
     * @param int code
     */
    public function __construct($message='Пустое сообщение', $code=0)
    {
        /* Включаем если событие надо передать выше по цепочке */
        //parent::__construct($message, $code);

        /* Бросаем в стек сообщений для юзеров - MF основной класс */
        MF::logErr($message);

        $error = [
            'type' => $this->getCode(),
            'message' => __CLASS__ .': '. $message,
            'file'	=> $this->getFile(),
            'line'	=> $this->getLine()
        ];
        /* Fatal лог - $code=1 */
        ErrorHandler::userExceptionHandler($error, $code);
    }
}
