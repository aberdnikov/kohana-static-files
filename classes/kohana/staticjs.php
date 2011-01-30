<?php

class Kohana_StaticJs extends StaticFile {
    /* внешние подключаемые скрипты */

    public $js = array();
    /* инлайн скрипты */
    public $js_inline = array();
    /* скрипты, которые должны быть выполнены при загрузке странице */
    public $js_onload = array();

    /*
     * Получение singleton
     * $js = StaticJs::instance();
     */

    static function instance() {
        static $js;
        if (!isset($js)) {
            $js = new StaticJs();
        }
        return $js;
    }

    /**
     * Подключение внешнего скрипта, реально лежащего в корне сайта
     * @param string $js
     */
    function addJs($js, $condition=null) {
        $this->js[$js] = $condition;
    }

    /**
     * Подключение внешнего скрипта, по технологии "static-files"
     * т.е. без учета префикса из конфига
     * @param string $js
     */
    function addJsStatic($js, $condition=null) {
        $js = Kohana::config('staticfiles.url') . $js;
        $this->js[$js] = $condition;
    }

    /**
     * Добавление куска инлайн джаваскрипта
     * @param <type> $js
     * @param mixed $id - уникальный флаг куска кода, чтобы можно
     * было добавлять в цикле и не бояться дублей
     */
    function addJsInline($js, $id=null) {
        $js = str_replace('{static_url}', STATICFILES_URL, $js);
        if ($id) {
            $this->js_inline[$id] = $js;
        } else {
            $this->js_inline[] = $js;
        }
    }

    /**
     * Добавление кода, который должен выполниться при загрузке страницы
     * @param string $js
     * @param mixed $id - уникальный флаг куска кода, чтобы можно
     * было добавлять в цикле и не бояться дублей
     */
    function addJsOnload($js, $id=null) {
        $js = str_replace('{static_url}', STATICFILES_URL, $js);
        $this->needJquery();
        if ($id) {
            $this->js_onload[$id] = $js;
        } else {
            $this->js_onload[] = $js;
        }
    }

    /**
     * Использовать во View для вставки вызова всех скриптов
     * @return string
     */
    function getJsAll() {
        return $this->getJs() . "\n" .
        $this->getJsInline() . "\n" .
        $this->getJsOnload();
    }

    function getLink($js, $condition=null) {
        if (mb_substr($js, 0, 4) != 'http') {
            $js = Kohana::config('staticfiles.host') . $js;
        }
        return '        '
        . ($condition ? '<!--[' . $condition . ']>' : '')
        . '<script language="JavaScript" type="text/javascript" '
        . "" . 'src="' . $js . '"></script>'
        . ($condition ? '<![endif]-->' : '') . "\n";

        ;
    }

    /**
     * Только внешние скрипты
     * @return string
     */
    function getJs() {
        if (!count($this->js))
            return '';
        //если не надо собирать все в один билд-файл
        if (!Kohana::config('staticfiles.js.build')) {
            $js_code = '';
            foreach ($this->js as $js => $condition) {
                //если надо подключать все по отдельности
                $js_code .= $this->getLink($js, $condition) . "\n";
            }
            return $js_code;
        } else {
            $build = array();
            $js_code = '';
            foreach ($this->js as $js => $condition) {
                $build[$condition][] = $js;
            }
            foreach ($build as $condition => $js) {
                $build_name = $this->makeFileName($js, $condition);
                if (!file_exists(Controller_Staticfiles::cache_file($build_name))) {
                    //соберем билд в первый раз
                    $build = '';
                    foreach ($js as $url) {
                        $_js = $this->getSource($url);
                        //если надо сжимать и он еще не сжат
                        //(общепринятое соглашение: у всех сжатых файлов есть в имени ".min.")
                        if ((Kohana::config('staticfiles.js.min')) && (!mb_strpos($url, '.min.'))) {
                            $_js = JSMin::minify($_js);
                        }
                        $build .= $_js;
                    }
                    $this->save(Controller_Staticfiles::cache_file($build_name), $build);
                }
                $js_code .= $this->getLink(Controller_Staticfiles::cache_url($build_name), $condition);
            }
            return $this->getLink(Controller_Staticfiles::cache_url($build_name));
        }
    }

    /**
     * Формирование уникального имени для билда подключаемых файлов
     * @return string
     */
    protected function makeFileName($js, $prefix) {
        $prefix = strtolower(preg_replace('/[^A-Za-z0-9_\-]/', '-', $prefix));
        $prefix = $prefix ? ($prefix . '/') : '';
        $file_name = md5(Kohana::config('staticfiles.host') . serialize($js));
        return 'js/' . $prefix
        . substr($file_name, 0, 1)
        . '/' . substr($file_name, 1, 1)
        . '/' . $file_name . '.js';
    }

    /**
     * Только инлайн
     * @return <type>
     */
    function getJsInline() {
        if (!count($this->js_inline))
            return '';
        $js_code = '';
        foreach ($this->js_inline as $js) {
            if (Kohana::config('staticfiles.js.min')) {
                $js = JSMin::minify($js);
            }
            $js_code .= $js;
        }
        $js_code = $this->prepare($js_code);
        if (!Kohana::config('staticfiles.js.build')) {
            return '<script language="JavaScript" type="text/javascript">' .
            trim($js_code) . '</script>';
        }
        //если требуется собирать инлайн скрипты в один внешний файл
        $build_name = $this->makeFileName($this->js_inline, 'inline');
        if (!file_exists(Controller_Staticfiles::cache_file($build_name))) {
            $this->save(Controller_Staticfiles::cache_file($build_name), $js_code);
        }
        return $this->getLink(Controller_Staticfiles::cache_url($build_name));
    }

    function prepare($js_code) {
        return str_replace('{staticfiles_url}', STATICFILES_URL, $js_code);
    }

    /**
     * Только онлоад
     * @return <type>
     */
    function getJsOnload() {
        if (!count($this->js_onload))
            return '';
        $js = implode("\n", $this->js_onload);
        if (Kohana::config('staticfiles.js.min')) {
            $js = JSMin::minify($js);
        }
        $js = $this->prepare($js);
        if (!Kohana::config('staticfiles.js.build')) {
            return '<script language="JavaScript" type="text/javascript">' . "\n\t" . 'jQuery(document).ready(' . "\n\t\t" .
            'function(){' . "\n\t\t\t" . trim(str_replace("\n", "\n\t\t\t", $js)) . "\n\t\t" . '}' . "\n\t" . ');' . "\n" . '</script>';
        }
        //если требуется собирать инлайн скрипты в один внешний файл
        $build_name = $this->makeFileName($this->js_onload, 'onload');
        if (!file_exists(Controller_Staticfiles::cache_file($build_name))) {
            $this->save(Controller_Staticfiles::cache_file($build_name), $js);
        }
        return $this->getLink(Controller_Staticfiles::cache_url($build_name));
    }

    function needJquery() {
        $this->addJs('https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js');
    }

}

?>