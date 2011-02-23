<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * @uses JSMin
 * @package Kohana-static-files
 * @author Berdnikov Alexey <aberdnikov@gmail.com>
 */
class Kohana_StaticJs extends StaticFile {

	/**
	 * Class instance
	 *
	 * @static
	 * @var StaticJs
	 */
	protected static $_instance;

	/**
	 * Javascript files
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
	 * StaticFiles config object
	 *
	 * @var Kohana_Config
	 */
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

	/**
	 * Adds real existing file (in docroot)
	 *
	 * @param  string      $js
	 * @param  string|null $condition
	 * @return StaticJs
	 */
	public function addJs($js, $condition = NULL)
	{
		$this->_js[$js] = $condition;
		return $this;
	}

	/**
	 * Adds external server script
	 *
	 * @param  string      $js
	 * @param  string|null $condition
	 * @return StaticJs
	 */
	public function addJsStatic($js, $condition = NULL)
	{
		$js = $this->_config->url . $js;
		$this->_js[$js] = $condition;
		return $this;
	}

	/**
	 * Adds inline javascript code
	 *
	 * @param  string      $js
	 * @param  string|null $id to avoid infinite loops (?)
	 * @return void
	 */
	public function addJsInline($js, $id = NULL)
	{
		$js = str_replace('{static_url}', STATICFILES_URL, $js);

		if ($id !== NULL)
		{
			$this->_js_inline[$id] = $js;
		}
		else
		{
			$this->_js_inline[] = $js;
		}
	}

	/**
	 * Adds on page load javascript
	 *
	 * @param  string      $js
	 * @param  string|null $id
	 * @return StaticJs
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
	 * Gets all javascripts that was loaded earlier
	 * @return string
	 */
	public function getJsAll()
	{
		return $this->getJs() . "\n" . $this->getJsInline() . "\n" . $this->getJsOnload();
	}

	/**
	 * Gets html code of the script loading
	 *
	 * @param  string $js
	 * @param  script|null $condition
	 * @return string
	 */
	public function getLink($js, $condition = NULL)
	{
		$js = trim($js, '/');
		if (mb_substr($js, 0, 4) != 'http')
		{
			$js = ($this->_config->host == '/') ? $js : $this->_config->host . $js;
		}

		return ''
		. ($condition ? '<!--[if ' . $condition . ']>' : '')
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
                    // first time building
                    $build = '';
                    foreach ($js as $url)
                    {
                        $_js = $this->getSource($url);

	                    // look if file name has 'min' suffix to avoid extra minification
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
	 * Gets inline scripts
	 *
	 * @return null|string
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
			if ($this->_config->js['min'])
			{
				$js = JSMin::minify($js);
			}
			$js_code .= $js;
		}

		$js_code = $this->prepare($js_code);

		if ( ! $this->_config->js['build'])
		{
			return '<script language="JavaScript" type="text/javascript">' . trim($js_code) . '</script>';
		}

		// If one file building of inline scripts is needed
		$build_name = $this->makeFileName($this->_js_inline, 'inline', 'js');
		if ( ! file_exists($this->cache_file($build_name)))
		{
			$this->save($this->cache_file($build_name), $js_code);
		}

		Profiler::stop($benchmark);
		return $this->getLink($this->cache_url($build_name));
	}

	/**
	 * Prepares javascript code
	 *
	 * @param  string $js_code
	 * @return mixed
	 */
	public function prepare($js_code)
	{
		return str_replace('{staticfiles_url}', STATICFILES_URL, $js_code);
	}

	/**
	 * Gets javascript code that must be loaded on page load
	 *
	 * @return null|string
	 */
	public function getJsOnload()
	{
		if ( ! count($this->_js_onload))
			return NULL;

		$js = implode("\n", $this->_js_onload);
		if ($this->_config->js['min'])
		{
			$js = JSMin::minify($js);
		}

		$js = $this->prepare($js);
		if ( ! $this->_config->js['build'])
		{
			return '<script language="JavaScript" type="text/javascript">' . "\n\t" . 'jQuery(document).ready(' . "\n\t\t" .
			'function(){' . "\n\t\t\t" . trim(str_replace("\n", "\n\t\t\t", $js)) . "\n\t\t" . '}' . "\n\t" . ');' . "\n" . '</script>';
		}

		// If one file building of inline scripts is needed
		$build_name = $this->makeFileName($this->_js_onload, 'onload', 'js');
		if ( ! file_exists($this->cache_file($build_name)))
		{
			$this->save($this->cache_file($build_name), $js);
		}

		return $this->getLink($this->cache_url($build_name));
	}

	/**
	 * Adds common libraries (for now it works only to jquery)
	 *
	 * @todo Make common method to load most popular frameworks and libraries
	 * @return void
	 */
	public function needJquery()
	{
		$this->addJs('https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js');
	}

} // END Kohana_StaticJs