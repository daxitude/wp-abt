<?php
// this seems to be the preferred way to handle an uninstall
if (!defined(WP_UNINSTALL_PLUGIN) exit();

require_once('db.php');

ABT_DB::uninstall();
