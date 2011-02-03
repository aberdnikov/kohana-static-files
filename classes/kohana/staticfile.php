<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * @package Kohana-static-files
 * @author Berdnikov Alexey <aberdnikov@gmail.com>
 */
class Kohana_StaticFile {

	protected $_config;

	public function __construct()
	{
		$this->_config = Kohana::config('staticfiles');
	}

    function save($file, $data)
    {
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
    function getSource($url)
    {
        //если подключен файл с удаленного сервера "http://google.com/style.css"
        if (mb_substr($url, 0, 4) == 'http')
        {
            if (function_exists('curl_init'))
            {
                $curl       = curl_init($url);
                $user_agent = 'Kohana: module "static-files"';
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $raw_data   = curl_exec($curl);
                curl_close($curl);

                $responce = $raw_data;
            }
            else
            {
                $responce = file_get_contents($url);
            }

            return $responce;
        }
        else
        {
            //если подключен виртуальный статический файл
            //пример: "module-name/style.css"
            if (mb_strpos($url, $this->_config->url) === 0)
            {
                $orig = Controller_Staticfiles::static_original(str_replace($this->_config->url, '', $url));
            }
            else
            {
                //если подключен реально лежащий в корне сайта файл
                // пример: "/css/style.css"
                $orig = realpath(DOCROOT) . preg_replace('/\//', DIRECTORY_SEPARATOR, $url);
            }

            return file_get_contents($orig);
        }
    }

	/**
     * получить полный путь до файла билда
	 *
     * @param string $build_name - имя билда, сгенерированное по массиву файлов, входящих в него
     * @return string
     */
    public function cache_file($build_name)
    {
        $cache_file = $this->_config->path . substr($this->_config->cache, 1) . $build_name;

        if ( ! file_exists(dirname($cache_file)))
            mkdir(dirname($cache_file), 0777, true);

        return $cache_file;
    }

	 /**
     * получить ссылку до файла билда
     * @param string $build_name - имя билда, сгенерированное по массиву файлов,
     * входящих в него
     * @return string
     */
    public function cache_url($build_name)
    {
        return $this->_config->cache . $build_name;
    }

	/**
	 * Generates unique file name for a build file
	 *
	 * @param  array       $file_array
	 * @param  string|null $condition_prefix
	 * @param  string      $type (css|js)
	 * @return string
	 */
    protected function makeFileName(array $file_array, $condition_prefix = NULL, $type)
    {
        $condition_prefix = strtolower(preg_replace('/[^A-Za-z0-9_\-]/', '-', $condition_prefix));
        $condition_prefix = $condition_prefix ? ($condition_prefix . '/') : '';
        $file_name        = md5($this->_config->host . serialize($file_array));

        return $type . '/'
             . $condition_prefix
             . substr($file_name, 0, 1) . '/'
             . substr($file_name, 1, 1) . '/'
             . $file_name . '.' . $type;
    }

}