<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'js' => array(

        // scripts minimization
        'min' => TRUE,

        // building all scripts in one file by types (external, inline, onload)
        'build' => TRUE,
    ),
    'css' => array(

        // styles minimization
        'min' => TRUE,

        // building all styles in one file by types (external, inline)
        'build' => TRUE,
    ),

    // Full path to site DOCROOT
    'path' => realpath(DOCROOT) . DIRECTORY_SEPARATOR,

    // Path to copy static files that are not build in one file
    'url' => '/!/static/',

    // Path to styles and scripts builds
    'cache' => '/!/cache/',

    // Host address (base or CDN)
    'host' => URL::base(FALSE, TRUE),
);