<?php
    /**
     * Вспомогательные функции с использованием cUrl
    */
    class CurlFunctions
    {
        private $ch;

        private $approved = [200, 201, 202, 203, 204, 205, 206, 233, 304];

        /**
         * Проверка существования страницы/файла по заголовку без скачивания
         *
         * @param string $url URL для проверки
         *
         * @return bool
         */
        public function checkUrl($url='')
        {
			if(empty($url))
				return false;

            $this->ch = curl_init();
            curl_setopt($this->ch, CURLOPT_URL, $url);
			curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);       /* Идем по редиректу если надо */
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);		/* Отключаем проверку SSL */
            curl_setopt($this->ch, CURLOPT_HEADER, true);				/* Включить заголовки */
            curl_setopt($this->ch, CURLOPT_NOBODY, true);				/* Только заголовок */
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);		/* Возврат результата в переменную, а не прямой вывод */
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 5);			/* время установления tcp-соединения (не имеет смысла более 5и секунд) */
			curl_setopt($this->ch, CURLOPT_TIMEOUT, 10);            	/* время установления соединения + время отправки запроса + время ответа до его получения */

			curl_exec($this->ch);

			if(!curl_errno($this->ch)) {
				$info = curl_getinfo($this->ch);
			}

			curl_close($this->ch);

			return !empty($info['http_code']) && in_array($info['http_code'], $this->approved) ? true : false;
		}

    }