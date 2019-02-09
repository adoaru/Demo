<?php
    /**
     * Класс для загрузки файлов с других серверов с возможностью установить лимит по загрузке
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

        public function bodyCallback($ch, $string)
        {
            if( !$this->fp )
            {
                $this->localFileName = $this->localFileName;
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

        public function download()
        {
            $retval = curl_exec($this->ch);
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

        public function checkError() {
        	if($this->fileSize>0)
        		return true;

       		$httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
			if(in_array($httpCode, [404, 500])) {
				$this->errorMessage = 'Такого файла не существует! Проверьте URL!';
			}
        }

        public function getFileName() { return $this->localFileName; }

        private function unquote($string)
        {
            return str_replace(array("'", '"'), '', $string);
        }
    }
