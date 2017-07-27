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
            'system/modules/newsletter2go/assets/lib/font-awesome.css',
        ),
        'javascript' => array(
            'system/modules/newsletter2go/assets/lib/jscolor.min.js',
            'system/modules/newsletter2go/assets/newsletter2go.js',
            'system/modules/newsletter2go/assets/newsletter2go_default.js',
        ),
        'callback' => 'ModuleNewsletter2GoBackend',
    ),
));

/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['miscellaneous']['Newsletter2Go'] = 'ModuleNewsletter2GoFrontend';
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Newsletter2GoTags', 'n2gReplaceTags');