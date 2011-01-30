<?php

class Kohana_StaticCss extends StaticFile {

    /**
     * Внешние подключаемые файлы стилей
     * @var array
     */
    public $css;
    /**
     * inline CSS
     *
     * @var string
     */
    public $css_inline;

    /**
     * singleton
     * @staticvar StaticCss $css
     * @return StaticCss
     */
    static function instance() {
        static $css;
        if (!isset($css)) {
            $css = new StaticCss();
        }
        return $css;
    }

    /**
     * Добавление внешнего файла стилей
     *
     * @param string $css_inline
     * @param string $condition - условие подключения скрипта, например [IE7]
     * @return Css
     */
    function addCss($css_file, $condition=null) {
        $this->css[$css_file] = $condition;
        return $this;
    }

    function addCssStatic($css_file, $condition=null) {
        $css_file = Kohana::config('staticfiles.url') . $css_file;
        $this->css[$css_file] = $condition;
        return $this;
    }

    /**
     * Добавление inline CSS
     *
     * @param string $css_inline
     * @return Css
     */
    function addCssInline($css_inline) {
        $css_inline = str_replace('{staticfiles_url}', STATICFILES_URL, $css_inline);
        $this->css_inline[$css_inline] = $css_inline;
        return $this;
    }

    /**
     * Сжатие файла стилей
     * @param string $v
     * @return string
     */
    protected function minify($v) {
        $v = trim($v);
        $v = str_replace("\r\n", "\n", $v);
        $search = array("/\/\*[\d\D]*?\*\/|\t+/", "/\s+/", "/\}\s+/");
        $replace = array(null, " ", "}\n");
        $v = preg_replace($search, $replace, $v);
        $search = array("/\\;\s/", "/\s+\{\\s+/", "/\\:\s+\\#/", "/,\s+/i", "/\\:\s+\\\'/i", "/\\:\s+([0-9]+|[A-F]+)/i");
        $replace = array(";", "{", ":#", ",", ":\'", ":$1");
        $v = preg_replace($search, $replace, $v);
        $v = str_replace("\n", null, $v);
        return $v;
    }

    /**
     * Формирование уникального имени для билда подключаемых файлов
     * @return string
     */
    protected function makeFileName($styles, $prefix) {
        $prefix = strtolower(preg_replace('/[^A-Za-z0-9_\-]/', '-', $prefix));
        $prefix = $prefix ? ($prefix . '/') : '';
        $file_name = md5(Kohana::config('staticfiles.host') . serialize($styles));
        return 'css/' . $prefix
        . substr($file_name, 0, 1)
        . '/' . substr($file_name, 1, 1)
        . '/' . $file_name . '.css';
    }

    /**
     * Препарируем CSS
     * пожмем, исправим пути к картинкам
     */
    protected function prepareCss($style) {
        /**
         * каждый файл стилей должен содержать плейсхолдеры для замены,
         * чтобы мы могли иметь возможность передвигать папку со статикой
         * от проекта к проекту по своему желанию
         * Пример:
         * a:hover{background:url({staticfiles_url}dir/file.jpeg) no-repeat left top;}
         */
        $style = str_replace('{staticfiles_url}', STATICFILES_URL, $style);
        if (Kohana::config('staticfiles.css.min')) {
            $style = $this->minify($style);
        }
        return trim($style);
    }

    protected function getLink($css, $condition=null) {
        if (mb_substr($css, 0, 4) != 'http') {
            $css = Kohana::config('staticfiles.host') . $css;
        }
        return '        '
        . ($condition ? '<!--[' . $condition . ']>' : '')
        . '<link rel="stylesheet" href="'
        . $css
        . '" media="all" type="text/css" />'
        . ($condition ? '<![endif]-->' : '');
    }

    /**
     * Внешние стили
     * @return string
     */
    function getCss() {
        $benchmark = Profiler::start(__CLASS__, __FUNCTION__);
        if (!count($this->css)) {
            Profiler::stop($benchmark);
            return '';
        }
        $css_code = '';
        /* если не надо собирать файлы в один */
        if (!Kohana::config('staticfiles.css.build')) {
            foreach ($this->css as $css => $condition) {
                $css_code .= $this->getLink($css, $condition);
            }
        } else {
            $build = array();
            $css_code = '';
            foreach ($this->css as $css => $condition) {
                $build[$condition][] = $css;
            }
            foreach ($build as $condition => $css) {
                $build_name = $this->makeFileName($css, $condition);
                if (!file_exists(Controller_Staticfiles::cache_file($build_name))) {
                    //соберем билд в первый раз
                    $build = '';
                    foreach ($css as $url) {
                        $_css = $this->getSource($url);
                        $_css = $this->prepareCss($_css);
                        $build .= $_css;
                    }
                    $this->save(Controller_Staticfiles::cache_file($build_name), $build);
                }
                $css_code .= $this->getLink(Controller_Staticfiles::cache_url($build_name), $condition);
            }
        }
        Profiler::stop($benchmark);
        return $css_code;
    }

    /**
     * Формирование инлайновых стилей
     * @return <type>
     */
    function getCssInline() {
        $benchmark = Profiler::start(__CLASS__, __FUNCTION__);
        if (!count($this->css_inline)) {
            Profiler::stop($benchmark);
            return '';
        }
        $css_inline = (implode("\n", $this->css_inline));
        if (Kohana::config('staticfiles.css.min')) {
            $css_inline = $this->minify($css_inline);
        }
        if (Kohana::config('staticfiles.css.build')) {
            $build_name = $this->makeFileName($css_inline, 'inline');
            if (!file_exists(Controller_Staticfiles::cache_file($build_name))) {
                $this->save(Controller_Staticfiles::cache_file($build_name), $css_inline);
            }
        }
        Profiler::stop($benchmark);
        return $this->getLink(Controller_Staticfiles::cache_url($build_name));
    }

    /**
     * Формирование обоих списков (внешние и инлайн стили)
     * @return string
     */
    function getCssAll() {
        return $this->getCss() . "\n" . $this->getCssInline();
    }

}

?>