<?php

/**
 * Register the classes
 */
ClassLoader::addClasses(array(
    // Classes
    'Contao\Newsletter2GoApi' => 'system/modules/newsletter2go/classes/Newsletter2GoApi.php',
    'Contao\Nl2go_ResponseHelper' => 'system/modules/newsletter2go/classes/Nl2go_ResponseHelper.php',
    'Contao\Newsletter2GoTags' => 'system/modules/newsletter2go/classes/Newsletter2GoTags.php',

    // Modules
    'Contao\ModuleNewsletter2GoBackend' => 'system/modules/newsletter2go/modules/ModuleNewsletter2GoBackend.php',
    'Contao\ModuleNewsletter2GoFrontend' => 'system/modules/newsletter2go/modules/ModuleNewsletter2GoFrontend.php',

    // Models
    'Contao\Newsletter2GoModel' => 'system/modules/newsletter2go/models/Newsletter2GoModel.php',
));

/**
 * Register the templates
 */
TemplateLoader::addFiles(array(
    'be_newsletter2go' => 'system/modules/newsletter2go/templates/modules',
    'mod_newsletter2go' => 'system/modules/newsletter2go/templates/modules',
));
