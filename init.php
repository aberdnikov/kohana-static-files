<?php defined('SYSPATH') or die('No direct script access.');

Route::set(
   'static_files',
   trim(Kohana::config('staticfiles.url'), '/').'/<file>',
   array('file'=>'.*')
)->defaults(array(
	'controller' => 'staticfiles',
	'action' => 'index'
	));

define('STATICFILES_URL', Kohana::config('staticfiles.host').Kohana::config('staticfiles.url'));