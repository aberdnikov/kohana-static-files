<?php

defined('SYSPATH') or die('No direct script access.');

Route::set('static_files', substr(Kohana::config('staticfiles.url'), 1).'<file>', array('file'=>'.*'))
        ->defaults(array(
            'controller' => 'staticfiles',
            'action' => 'index'
        ));
define('STATICFILES_URL', Kohana::config('staticfiles.host').Kohana::config('staticfiles.url'));