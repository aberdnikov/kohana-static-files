<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * @uses JSMin
 * @package Kohana-static-files
 * @author Berdnikov Alexey <aberdnikov@gmail.com>
 */
class Kohana_StaticCss extends StaticFile {

	/**
	 * Class instance
	 * @static
	 * @var $instance
	 */
	protected static $_instance;

	/**
	 * Внешние подключаемые файлы стилей
	 * @var array
	 */
	protected $_css;
	/**
	 * inline CSS
	 *
	 * @var string
	 */
	protected $_css_inline;

	/**
	 * Class instance initiating
	 *
	 * @static
	 * @return StaticCss
	 */
	public static function instance()
	{
		if ( ! is_object(self::$_instance))
		{
			self::$_instance = new StaticCss();
		}

		return self::$_instance;
	}

	/**
	 * Добавление внешнего файла стилей
	 *
	 * @param string $css_inline
	 * @param string $condition - условие подключения скрипта, например [IE7]
	 * @return Css
	 */
	function addCss($css_file, $condition = NULL)
	{
		$this->_css[$css_file] = $condition;
		return $this;
	}

	function addCssStatic($css_file, $condition = NULL)
	{
		$css_file = $this->_config->url . $css_file;
		$this->_css[$css_file] = $condition;
		return $this;
	}

	/**
	 * Добавление inline CSS
	 *
	 * @param string $css_inline
	 * @return Css
	 */
	function addCssInline($css_inline)
	{
		$css_inline = str_replace('{staticfiles_url}', STATICFILES_URL, $css_inline);
		$this->_css_inline[$css_inline] = $css_inline;
		return $this;
	}

	/**
	 * Сжатие файла стилей
	 * @param string $v
	 * @return string
	 */
	protected function minify($v)
	{
		$v       = trim($v);
		$v       = str_replace("\r\n", "\n", $v);
		$search  = array("/\/\*[\d\D]*?\*\/|\t+/", "/\s+/", "/\}\s+/");
		$replace = array(null, " ", "}\n");
		$v       = preg_replace($search, $replace, $v);
		$search  = array("/\\;\s/", "/\s+\{\\s+/", "/\\:\s+\\#/", "/,\s+/i", "/\\:\s+\\\'/i", "/\\:\s+([0-9]+|[A-F]+)/i");
		$replace = array(";", "{", ":#", ",", ":\'", ":$1");
		$v       = preg_replace($search, $replace, $v);
		$v       = str_replace("\n", null, $v);

		return $v;
	}

	/**
	 * Препарируем CSS
	 * пожмем, исправим пути к картинкам
	 */
	protected function prepareCss($style)
	{
		/**
		 * каждый файл стилей должен содержать плейсхолдеры для замены,
		 * чтобы мы могли иметь возможность передвигать папку со статикой
		 * от проекта к проекту по своему желанию
		 * Пример:
		 * a:hover{background:url({staticfiles_url}dir/file.jpeg) no-repeat left top;}
		 */
		$style = str_replace('{staticfiles_url}', STATICFILES_URL, $style);

		if ($this->_config->css['min'])
		{
			$style = $this->minify($style);
		}

		return trim($style);
	}

	protected function getLink($css, $condition = NULL)
	{
		$css = trim($css, '/');
		if (mb_substr($css, 0, 4) != 'http')
		{
			$css = $this->_config->host . $css;
		}

		return '        '
		. ($condition ? '<!--[' . $condition . ']>' : '')
		. HTML::style($css, array('media' => 'all'))
		. ($condition ? '<![endif]-->' : '');
	}

	/**
	 * Внешние стили
	 * @return string
	 */
	function getCss()
	{
		$benchmark = Profiler::start(__CLASS__, __FUNCTION__);

		if ( ! count($this->_css))
		{
			Profiler::stop($benchmark);
			return NULL;
		}

		$css_code = '';
		/* если не надо собирать файлы в один */
		if ( ! $this->_config->css['build'])
		{
			foreach ($this->_css as $css => $condition)
			{
				$css_code .= $this->getLink($css, $condition);
			}
		}
		else
		{
			$build = array();
			$css_code = '';
			foreach ($this->_css as $css => $condition)
			{
				$build[$condition][] = $css;
			}

			foreach ($build as $condition => $css)
			{
				$build_name = $this->makeFileName($css, $condition, 'css');
				if ( ! file_exists($this->cache_file($build_name)))
				{
					//соберем билд в первый раз
					$build = '';
					foreach ($css as $url)
					{
						$_css = $this->getSource($url);
						$_css = $this->prepareCss($_css);
						$build .= $_css;
					}

					$this->save($this->cache_file($build_name), $build);
				}

				$css_code .= $this->getLink($this->cache_url($build_name), $condition);
			}
		}

		Profiler::stop($benchmark);
		return $css_code;
	}

	/**
	 * Формирование инлайновых стилей
	 * @return <type>
	 */
	function getCssInline()
	{
		$benchmark = Profiler::start(__CLASS__, __FUNCTION__);

		if ( ! count($this->_css_inline))
		{
			Profiler::stop($benchmark);
			return NULL;
		}

		$css_inline = implode("\n", $this->_css_inline);

		if ($this->_config->css['min'])
		{
			$css_inline = $this->minify($css_inline);
		}

		if ($this->_config->css['build'])
		{
			$build_name = $this->makeFileName($css_inline, 'inline', 'css');

			if ( ! file_exists($this->cache_file($build_name)))
			{
				$this->save($this->cache_file($build_name), $css_inline);
			}
		}
		Profiler::stop($benchmark);
		return $this->getLink($this->cache_url($build_name));
	}

	/**
	 * Формирование обоих списков (внешние и инлайн стили)
	 * @return string
	 */
	function getCssAll()
	{
		return $this->getCss() . "\n" . $this->getCssInline();
	}
}