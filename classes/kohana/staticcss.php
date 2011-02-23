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
	 * CSS files
	 *
	 * @var array
	 */
	protected $_css = array();

	/**
	 * inline CSS
	 *
	 * @var string
	 */
	protected $_css_inline = array();

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
	 * Adds real existing file (in docroot)
	 *
	 * @param  string      $css_file
	 * @param  string|null $condition
	 * @return Kohana_StaticCss
	 */
	public function addCss($css_file, $condition = NULL)
	{
		$this->_css[$css_file] = $condition;
		return $this;
	}

	/**
	 * Adds external server css
	 *
	 * @param  string      $css_file
	 * @param  string|null $condition
	 * @return Kohana_StaticCss
	 */
	public function addCssStatic($css_file, $condition = NULL)
	{
		$css_file = $this->_config->url . $css_file;
		$this->_css[$css_file] = $condition;
		return $this;
	}

	/**
	 * Adds inline style
	 *
	 * @param  string  $css_inline
	 * @return Kohana_StaticCss
	 */
	public function addCssInline($css_inline)
	{
		$css_inline = str_replace('{staticfiles_url}', STATICFILES_URL, $css_inline);
		$this->_css_inline[$css_inline] = $css_inline;
	}

	/**
	 * Minifies css file content
	 *
	 * @param  string $v
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

	/**
	 * Prepares css file content
	 *
	 * If you want to move static files folder (ie images), you should use a placeholder instead of strict
	 * folder defining.
	 * @examle a:hover{background:url({staticfiles_url}dir/file.jpeg) no-repeat left top;}
	 *
	 * @param  string $style
	 * @return string
	 */
	protected function prepareCss($style)
	{
		$style = str_replace('{staticfiles_url}', STATICFILES_URL, $style);

		if ($this->_config->css['min'])
		{
			$style = $this->minify($style);
		}

		return trim($style);
	}

	/**
	 * Gets html code of the css loading
	 *
	 * @param  string      $css
	 * @param  string|null $condition
	 * @return string
	 */
	public function getLink($css, $condition = NULL)
	{
		$css = trim($css, '/');
		if (mb_substr($css, 0, 4) != 'http')
		{
			$css = ($this->_config->host == '/') ? $css : $this->_config->host . $css;
		}

		return ''
		. ($condition ? '<!--[if ' . $condition . ']>' : '')
		. HTML::style($css, array('media' => 'all'))
		. ($condition ? '<![endif]-->' : '');
	}

	/**
	 * Gets external css
	 *
	 * @return null|string
	 */
	public function getCss()
	{
		$benchmark = Profiler::start(__CLASS__, __FUNCTION__);

		if ( ! count($this->_css))
		{
			Profiler::stop($benchmark);
			return NULL;
		}

		$css_code = '';
		// Not need to build one js file
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
					 // first time building
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
	 * Gets inline styles
	 *
	 * @return null|string
	 */
	public function getCssInline()
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
	 * Gets all css and inline styles that was loaded earlier
	 * @return string
	 */
	public function getCssAll()
	{
		return $this->getCss() . "\n" . $this->getCssInline();
	}

} // END Kohana_StaticCss