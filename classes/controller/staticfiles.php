<?php

/**
 * Суть контроллера: иметь возможность создания компактных модулей, в которых бы
 * можно было хранить и css, и js, и картинки выше DOCUMENT_ROOT, чтобы
 * при развертывании проекта не забывать копировать их куда надо
 * Просто бросаем модуль в modules, прописываем его в bootstrapper
 * Затем текущий контроллер, при первом же запросе
 */
class Controller_Staticfiles extends Controller {

    /**
     * Развертывание статики по мере необходимости
     */
    function action_index($file) {
        $this->auto_render = FALSE;
        $info = pathinfo($file);
        $dir = ('.' != $info['dirname']) ? $info['dirname'] . '/' : '';
        if (($orig = self::static_original($file))) {
            $deploy = self::static_deploy($file);
            //производим deploy статического файла, в следующий раз его будет
            //отдавать сразу веб-сервер без запуска PHP
            copy($orig, $deploy);
            //а пока отдадим файл руками
            $this->request->check_cache(sha1($this->request->uri) . filemtime($orig));
            $this->request->response = file_get_contents($orig);
            $this->request->headers['Content-Type'] = File::mime_by_ext($info['extension']);
            $this->request->headers['Content-Length'] = filesize($orig);
            $this->request->headers['Last-Modified'] = date('r', filemtime($orig));
        } else {
            // Return a 404 status
            $this->request->status = 404;
        }
    }

    /**
     * Поиск по проекту статичного файла
     * (полный путь к файлу)
     * @param string $file
     * @return string
     */
    static function static_original($file) {
        $info = pathinfo($file);
        $dir = ('.' != $info['dirname']) ? $info['dirname'] . '/' : '';
        return Kohana::find_file('static-files', $dir . $info['filename'], $info['extension']);
    }

    static function static_deploy($file) {
        $info = pathinfo($file);
        $dir = ('.' != $info['dirname']) ? $info['dirname'] . '/' : '';
        $deploy = Kohana::config('staticfiles.path')
                . Kohana::config('staticfiles.url') . $dir
                . $info['filename'] . '.'
                . $info['extension'];
        if (!file_exists(dirname($deploy)))
            mkdir(dirname($deploy), 0777, true);
        return $deploy;
    }

    /**
     * получить полный путь до файла билда
     * @param string $build_name - имя билда, сгенерированное по массиву файлов,
     * входящих в него
     * @return string
     */
    static function cache_file($build_name) {
        $cache_file = Kohana::config('staticfiles.path')
                . substr(Kohana::config('staticfiles.cache'), 1)
                . $build_name;
        if (!file_exists(dirname($cache_file)))
            mkdir(dirname($cache_file), 0777, true);
        return $cache_file;
    }

    /**
     * получить ссылку до файла билда
     * @param string $build_name - имя билда, сгенерированное по массиву файлов,
     * входящих в него
     * @return string
     */
    static function cache_url($build_name) {
        return Kohana::config('staticfiles.cache') . $build_name;
    }

}

?>