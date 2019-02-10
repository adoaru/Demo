<?php
    /**
     * Класс для загрузки файлов с других серверов с возможностью установить макс.размер файла
     * При превышении указанного лимита например 5Mb загрузка будет прервана
     * Позволяет избегать загрузки больших файлов - экономит время и дисковое пространство
     */

    class CurlDownloader
    {
        private $localFileName = NULL,
        		$ch = NULL,
        		$headers = array();
        private $response = NULL;
        private $fp = NULL;
        private $debug = FALSE;
        private $fileSize = 0;
        private $maxFileSize = 0;
        private $image = 0;
        private $errorMessage = 'Непредвиденная ошибка. К сожалению файл не загружен!';

        /**
         * CurlDownloader constructor.
         * @param string $url
         * @param string $localFileName
         * @param int $maxFileSize
         * @param int $image
         * @throws BaseException
         * @throws UserMessageException
         */
        public function __construct($url='', $localFileName='', $maxFileSize=0, $image=0)
        {
	        if(!$url)
    	        throw new BaseException("Не указан URL для загрузки!");

	        if(!$localFileName)
    	        throw new BaseException("Не указан локальный файл для загрузки!");

        	$this->localFileName = $localFileName;
        	$this->maxFileSize = $maxFileSize ? $maxFileSize : MF::$conf->importMaxFileSize;
        	$this->image = $image;

            $this->init($url);

        }

        public function toggleDebug()
        {
            $this->debug = !$this->debug;
        }

        /**
         * @param $url
         * @throws UserMessageException
         */
        public function init($url)
        {
            if( !filter_var($url, FILTER_VALIDATE_URL) )
                throw new UserMessageException("Некорректный URL-адрес файла!");

            $this->ch = curl_init();
            curl_setopt($this->ch, CURLOPT_URL, $url);
            curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array($this, 'headerCallback'));
            curl_setopt($this->ch, CURLOPT_WRITEFUNCTION, array($this, 'bodyCallback'));
			curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

           	$tcp_timeout = $this->image ? 1 : 5;
           	$con_timeout = $this->image ? 10 : 60;
            /* время установления tcp-соединения */
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $tcp_timeout);
            /* время установления соединения + время отправки запроса + время ответа до его получения */
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $con_timeout);
        }

        /**
         * @param $ch
         * @param $string
         * @return int
         */
        public function headerCallback($ch, $string)
        {
	        $len = strlen($string);
    	    if( !strstr($string, ':') )
        	{
            	$this->response = trim($string);
	            return $len;
    	    }
        	list($name, $value) = explode(':', $string, 2);
	        if( strcasecmp($name, 'Content-Disposition') == 0 )
    	    {
        	    $parts = explode(';', $value);
            	if( count($parts) > 1 )
	            {
    	            foreach($parts AS $crumb)
        	        {
            	        if( strstr($crumb, '=') )
                	    {
                    	    list($pname, $pval) = explode('=', $crumb);
                        	$pname = trim($pname);
	                        if( strcasecmp($pname, 'filename') == 0 )
    	                    {
                        	    $this->fp = fopen($this->localFileName, 'wb');
                        	}
	                    }
    	            }
        	    }
	        }

        	$this->headers[$name] = trim($value);
	        return $len;
        }

        /**
         * @param $ch
         * @param $string
         * @return bool|int
         * @throws BaseException
         * @throws UserMessageException
         */
        public function bodyCallback($ch, $string)
        {
            if( !$this->fp )
            {
                $this->fp = fopen($this->localFileName, 'wb');
                if(!$this->fp )
                    throw new BaseException("Не могу открыть файл");
            }
            $len = fwrite($this->fp, $string);
            $this->fileSize += $len;
            /* Проверка превышения размера файла на лету */
            if($this->maxFileSize && $this->fileSize > $this->maxFileSize)
            	throw new UserMessageException('Файл превышает допустимый размер '.FBytes($this->maxFileSize).'!');

            return $len;
        }

        /**
         * @return int
         * @throws UserMessageException
         */
        public function download()
        {
            curl_exec($this->ch);
            if($this->debug)
                var_dump($this->headers);
            if($this->checkError())
	            fclose($this->fp);
            curl_close($this->ch);

            /* На случай непредвиденной ошибки */
            if($this->fileSize<=0 || !is_file($this->localFileName)) {
            	throw new UserMessageException($this->errorMessage);
            }

            return $this->fileSize;
        }

        /**
         * @return bool
         */
        public function checkError() {
        	if($this->fileSize>0)
        		return true;

       		$httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
			if(in_array($httpCode, [404, 500])) {
				$this->errorMessage = 'Такого файла не существует! Проверьте URL!';
			}
        }

        /**
         * @return null|string
         */
        public function getFileName() { return $this->localFileName; }

        /**
         * @param $string
         * @return mixed
         */
        private function unquote($string)
        {
            return str_replace(array("'", '"'), '', $string);
        }
    }
