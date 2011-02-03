<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Static js forming class
 */
class Kohana_StaticJs extends StaticFile {

	/**
	 * External scripts
	 * @var array
	 */
	protected $_js = array();

	/**
	 * Inline scripts
	 * @var array
	 */
	protected $_js_inline = array();

	/**
	 * Page onload scripts
	 * @var array
	 */
	protected $_js_onload = array();


	/**
	 * Class instance
	 * @static
	 * @var $instance
	 */
	protected static $_instance;

	protected $_config;

	/**
	 * Class instance initiating
	 *
	 * @static
	 * @return StaticJs
	 */
	public static function instance()
	{
		if ( ! is_object(self::$_instance))
		{
			self::$_instance = new StaticJs();
		}

		return self::$_instance;
	}

	public function __construct()
	{
		$this->_config = Kohana::config('staticfiles');
	}

	/**
	 * Adds real existing file (in docroot)
	 *
	 * @param  string      $js
	 * @param  string|null $condition
	 * @return void
	 */
	public function addJs($js, $condition = NULL)
	{
		$this->_js[$js] = $condition;
	}

	/**
	 * Подключение внешнего скрипта, по технологии "static-files"
	 * т.е. без учета префикса из конфига
	 * @param string $js
	 */
	public function addJsStatic($js, $condition = NULL)
	{
		$js = $this->_config->url . $js;
		$this->_js[$js] = $condition;
	}

	/**
	 * Добавление куска инлайн джаваскрипта
	 * @param <type> $js
	 * @param mixed $id - уникальный флаг куска кода, чтобы можно
	 * было добавлять в цикле и не бояться дублей
	 */
	public function addJsInline($js, $id = NULL)
	{
		$js = str_replace('{static_url}', STATICFILES_URL, $js);

		if ($id)
		{
			$this->_js_inline[$id] = $js;
		}
		else
		{
			$this->_js_inline[] = $js;
		}
	}

	/**
	 * Добавление кода, который должен выполниться при загрузке страницы
	 * @param string $js
	 * @param mixed $id - уникальный флаг куска кода, чтобы можно
	 * было добавлять в цикле и не бояться дублей
	 */
	public function addJsOnload($js, $id = NULL)
	{
		$js = str_replace('{static_url}', STATICFILES_URL, $js);

		$this->needJquery();
		if ($id)
		{
			$this->_js_onload[$id] = $js;
		}
		else
		{
			$this->_js_onload[] = $js;
		}
	}

	/**
	 * Использовать во View для вставки вызова всех скриптов
	 * @return string
	 */
	public function getJsAll()
	{
		return $this->getJs() . "\n" . $this->getJsInline() . "\n" . $this->getJsOnload();
	}

	public function getLink($js, $condition = NULL)
	{
		$js = trim($js, '/');
		if (mb_substr($js, 0, 4) != 'http')
		{
			$js = $this->_config->host . $js;
		}

		return '        '
		. ($condition ? '<!--[' . $condition . ']>' : '')
		. HTML::script($js)
		. ($condition ? '<![endif]-->' : '') . "\n";
	}

	/**
	 * Gets external scripts
	 *
	 * @return null|string
	 */
	public function getJs()
	{
		$benchmark = Profiler::start(__CLASS__, __FUNCTION__);

		if ( ! count($this->_js))
		{
			Profiler::stop($benchmark);
			return NULL;
		}

		// Not need to build one js file
		if ( ! $this->_config->js['build'])
		{
			$js_code = '';
			foreach ($this->_js as $js => $condition)
			{
				$js_code .= $this->getLink($js, $condition) . "\n";
			}
			return $js_code;
		}
		else
		{
			$build = array();
            $js_code = '';
            foreach ($this->_js as $js => $condition)
            {
                $build[$condition][] = $js;
            }

			$js_code = '';
            foreach ($build as $condition => $js)
            {
                $build_name = $this->makeFileName($js, $condition, 'js');

                if ( ! file_exists($this->cache_file($build_name)))
                {
                    //соберем билд в первый раз
                    $build = '';
                    foreach ($js as $url)
                    {
                        $_js = $this->getSource($url);

                        //если надо сжимать и он еще не сжат
                        //(общепринятое соглашение: у всех сжатых файлов есть в имени ".min.")
                        if ($this->_config->js['min'] AND ! mb_strpos($url, '.min.'))
                        {
                            $_js = JSMin::minify($_js);
                        }

                        $build .= $_js;
                    }

                    $this->save($this->cache_file($build_name), $build);
                }

                $js_code .= $this->getLink($this->cache_url($build_name), $condition);
            }

			Profiler::stop($benchmark);
            return $js_code;
		}
	}

	/**
	 * Только инлайн
	 * @return <type>
	 */
	public function getJsInline()
	{
		$benchmark = Profiler::start(__CLASS__, __FUNCTION__);
		if ( ! count($this->_js_inline))
		{
			Profiler::stop($benchmark);
			return NULL;
		}

		$js_code = '';
		foreach ($this->_js_inline as $js)
		{
			if (Kohana::config('staticfiles.js.min'))
			{
				$js = JSMin::minify($js);
			}
			$js_code .= $js;
		}

		$js_code = $this->prepare($js_code);

		if ( ! Kohana::config('staticfiles.js.build'))
		{
			return '<script language="JavaScript" type="text/javascript">' . trim($js_code) . '</script>';
		}

		//если требуется собирать инлайн скрипты в один внешний файл
		$build_name = $this->makeFileName($this->_js_inline, 'inline', 'js');
		if ( ! file_exists($this->cache_file($build_name)))
		{
			$this->save($this->cache_file($build_name), $js_code);
		}

		Profiler::stop($benchmark);
		return $this->getLink($this->cache_url($build_name));
	}

	public function prepare($js_code)
	{
		return str_replace('{staticfiles_url}', STATICFILES_URL, $js_code);
	}

	/**
	 * Только онлоад
	 * @return <type>
	 */
	public function getJsOnload()
	{
		if ( ! count($this->_js_onload))
			return NULL;

		$js = implode("\n", $this->_js_onload);
		if (Kohana::config('staticfiles.js.min'))
		{
			$js = JSMin::minify($js);
		}

		$js = $this->prepare($js);
		if ( ! Kohana::config('staticfiles.js.build'))
		{
			return '<script language="JavaScript" type="text/javascript">' . "\n\t" . 'jQuery(document).ready(' . "\n\t\t" .
			'function(){' . "\n\t\t\t" . trim(str_replace("\n", "\n\t\t\t", $js)) . "\n\t\t" . '}' . "\n\t" . ');' . "\n" . '</script>';
		}

		//если требуется собирать инлайн скрипты в один внешний файл
		$build_name = $this->makeFileName($this->_js_onload, 'onload', 'js');
		if ( ! file_exists(Controller_Staticfiles::cache_file($build_name)))
		{
			$this->save(Controller_Staticfiles::cache_file($build_name), $js);
		}

		return $this->getLink(Controller_Staticfiles::cache_url($build_name));
	}

	public function needJquery()
	{
		$this->addJs('https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js');
	}
}