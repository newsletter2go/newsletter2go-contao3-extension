<?php

/**
 * Back end modules
 */
array_insert($GLOBALS['BE_MOD']['content'], 5, array
(
    'Newsletter2Go' => array
    (
        'icon' => 'system/modules/newsletter2go/assets/icon.gif',
        'stylesheet' => array(
            'system/modules/newsletter2go/assets/styles.css',
            'system/modules/newsletter2go/assets/lib/farbtastic.css',
        ),
        'javascript' => array(
            'system/modules/newsletter2go/assets/lib/jquery-1.11.3.min.js',
            'system/modules/newsletter2go/assets/lib/farbtastic.js',
            'system/modules/newsletter2go/assets/script.js',
        ),
        'callback' => 'ModuleNewsletter2GoBackend',
    ),
));

/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['miscellaneous']['Newsletter2Go'] = 'ModuleNewsletter2GoFrontend';