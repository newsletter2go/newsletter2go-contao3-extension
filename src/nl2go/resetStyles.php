<?php

/**
 * Initialize the system
 */
define('TL_MODE', 'NEWSLETTER2GO_RESETSTYLES');
define('BYPASS_TOKEN_CHECK', true);
require '../system/initialize.php';
define('TL_FILES_URL', null);

\Contao\Newsletter2GoResetStyles::getInstance()->run();