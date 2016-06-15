<?php
// -----
// Part of the "Clone Template" plugin for Zen Cart v1.5.0 or later
//
// Copyright (c) 2016, Vinos de Frutas Tropicales (lat9)
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
} 

//----
// Register the tool into the admin menu structure.
//
if (!zen_page_key_exists('toolsCloneTemplate')) {
    $next_sort = $db->Execute ('SELECT MAX(sort_order) as max_sort FROM ' . TABLE_ADMIN_PAGES . " WHERE menu_key='tools'");
    zen_register_admin_page ('toolsCloneTemplate', 'BOX_TOOLS_CLONE_TEMPLATE', 'FILENAME_CLONE_TEMPLATE','' ,'tools', 'Y', $next_sort->fields['max_sort'] + 1);
}    
