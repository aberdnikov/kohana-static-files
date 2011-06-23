<?php defined('SYSPATH') or die('No direct script access.');

// Turn on the minimization and building in PRODUCTION environment
$in_production = (Kohana::$environment === Kohana::PRODUCTION);

return array(
    'js' => array(

        // scripts minimization
        'min' => $in_production,

        // building all scripts in one file by types (external, inline, onload)
        'build' => $in_production,
    ),
    'css' => array(

        // styles minimization
        'min' => $in_production,

        // building all styles in one file by types (external, inline)
        'build' => $in_production,
    ),

    // Full path to site DOCROOT
    'path' => realpath(DOCROOT) . DIRECTORY_SEPARATOR,

    // Path to copy static files that are not build in one file
    'url' => '/media/static/',

    // Path to styles and scripts builds
    'cache' => '/media/cache/',

    // Host address (base or CDN)
    'host' => URL::base(FALSE, TRUE),

	// Cache reset interval
	'cache_reset_interval' => 12*60*60, // 12 hours
);