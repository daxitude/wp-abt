<?php

// create new mgr instance
$abt = new ABT_Admin_Mgr();

// register the admin pages by class name
$abt->register('ABT_View_Experiments');
$abt->register('ABT_View_Settings');
$abt->register('ABT_View_Tools');
$abt->register('ABT_View_Experiment');
$abt->register('ABT_View_Variation');

// run the routing to the requested path
$abt->run();
