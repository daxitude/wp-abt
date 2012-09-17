<?php

// require needed files in admin folder
$abt_admin_base = $abt_base . 'admin/';

require_once $abt_admin_base . 'helpers.php';
require_once $abt_admin_base . 'mustache.php';
require_once $abt_admin_base . 'admin_page.php';

require_once $abt_admin_base . 'models/experiment.php';
require_once $abt_admin_base . 'models/variation.php';

require_once $abt_admin_base . 'views/list.php';
require_once $abt_admin_base . 'views/experiment.php';
require_once $abt_admin_base . 'views/variation.php';
require_once $abt_admin_base . 'views/settings.php';

