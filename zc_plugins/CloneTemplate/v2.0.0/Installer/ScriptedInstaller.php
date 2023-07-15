<?php
// -----
// Part of the "Clone Template" plugin for Zen Cart v1.5.8 or later.  Now an 'encapsulated' plugin.
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
