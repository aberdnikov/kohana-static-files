<?php

class Kohana_StaticFile {

    function save($file, $data) {
        /**
         * Блокируем файл при записи
         * http://forum.dklab.ru/viewtopic.php?p=96622#96622
         */
        // Вначале создаем пустой файл, ЕСЛИ ЕГО ЕЩЕ НЕТ.
        // Если же файл существует, это его не разрушит.
        fclose(fopen($file, "a+b"));
        // Блокируем файл.
        $f = fopen($file, "r+b") or die("Не могу открыть файл!");
        flock($f, LOCK_EX); // ждем, пока мы не станем единственными
        // В этой точке мы можем быть уверены, что только эта
        // программа работает с файлом.
        fwrite($f, $data);
        fclose($f);
    }

    /**
     * Получение информации о том, где брать содержимое файла
     * @return string
     */
    function getSource($url) {
        //если подключен файл с удаленного сервера "http://google.com/style.css"
        if (mb_substr($url, 0, 4) == 'http') {
            if (function_exists('curl_init')) {
                $curl = curl_init($url);
                $user_agent = 'Kohana: module "static-files"';
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $raw_data = curl_exec($curl);
                curl_close($curl);
                $responce = $raw_data;
            } else {
                $responce = file_get_contents($url);
            }
            return $responce;
        } else {
            //если подключен виртуальный статический файл
            //пример: "module-name/style.css"
            if (mb_strpos($url, Kohana::config('staticfiles.url')) === 0) {
                $orig = Controller_Staticfiles::static_original(
                                str_replace(Kohana::config('staticfiles.url'), '', $url)
                );
            } else {
                //если подключен реально лежащий в корне сайта файл
                // пример: "/css/style.css"
                $orig = realpath(DOCROOT) . $url;
            }
            return file_get_contents($orig);
        }
    }

}

?>