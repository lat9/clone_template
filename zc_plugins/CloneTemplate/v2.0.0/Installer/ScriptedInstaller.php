<?php
// -----
// Part of the "Clone Template" encapsulated plugin for Zen Cart v1.5.8 or later.
//
// Last updated: v2.0.0
//
// Copyright (c) 2016-2023, Vinos de Frutas Tropicales (lat9)
//
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        // -----
        // Since the zc_plugins directories are loaded last, check to see if a 'legacy' version
        // of the plugin is already installed.  If so, let the admin know (can't use a language constant
        // since this plugin's language file isn't loaded!) and return (bool)false in the hopes that
        // eventually there will be a means for a zc_plugin to indicate that it's not to be installed.
        //
        if (defined('FILENAME_CLONE_TEMPLATE')) {
            global $messageStack;
            $messageStack->add_session('A <em>Clone a Template</em> plugin prior to v2.0.0 is already installed and must be removed before the current version can be used.', 'error');
            return false;
        }
        zen_deregister_admin_pages(['toolsCloneTemplate']);
        zen_register_admin_page(
            'toolsCloneTemplate',
            'BOX_TOOLS_CLONE_TEMPLATE',
            'FILENAME_CLONE_TEMPLATE',
            '',
            'tools',
            'Y'
        );
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(['toolsCloneTemplate']);
    }
}
