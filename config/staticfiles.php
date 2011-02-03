<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'js' => array(
        //минимизация скриптов
        'min' => TRUE,
        //сборка в один файл по типу (external, inline, onload)
        'build' => TRUE,
    ),
    'css' => array(
        //минимизация стилей
        'min' => TRUE,
        //сборка в один файл по типу (external, inline)
        'build' => TRUE,
    ),
    //полный путь до DOCUMENT_ROOT домена со статикой
    //(естественно он должен находиться на том же физическом сервере,
    // что и сам сайт)
    // например так:
    'path' => realpath(DOCROOT) . DIRECTORY_SEPARATOR,
    //сюда будут копироваться статические файлы если не требуется их сборка в билды
    'url' => '/!/static/',
    //сюда будут складываться сгенерированные скрипты и файлы стилей
    'cache' => '/!/cache/',
    /*
     * Для использования Coral CDN
     * добавьте в имени текущего домена со статикой суффикс ".nyud.net"
     * например для домена "google.com" установите хост "google.com.nyud.net"
     * Больше информации тут: http://habrahabr.ru/blogs/i_recommend/82739/
     * Пример заполнения:
     * 1) "" - ссылки будут иметь вид: "/pic.jpg"
     * 2) "http://ya.ru" - ссылки будут иметь вид: "http://ya.ru/pic.jpg"
     * 3) "http://ya.ru.nyud.net" - ссылки будут иметь вид: "http://ya.ru.nyud.net/pic.jpg"
     */
    'host' => URL::base(FALSE, TRUE),
);