<?php
// -----
// Part of the "Clone Template" encapsulated plugin for Zen Cart v1.5.8 or later.
//
// Last updated: v2.0.0
//
// Copyright (c) 2016-2023, Vinos de Frutas Tropicales (lat9)
//
define('CLONE_TEMPLATE_VERSION', 'v2.0.0');

require 'includes/application_top.php';
/*
$psr4Autoloader->setClassFile(
    'Zencart\Plugins\Admin\CloneTemplate\cloneTemplate',
    $filePathPluginAdmin['CloneTemplate'] . 'class.admin.cloneTemplate.php'
);
*/
$languages = zen_get_languages();
$default_language_id = 1;
foreach ($languages as $current_language) {
    if ($current_language['code'] === DEFAULT_LANGUAGE) {
        $default_language_id = $current_language['id'];
        break;
    }
}

$templates = $db->Execute('SELECT DISTINCT layout_template FROM ' . TABLE_LAYOUT_BOXES);
$template_list_dropdown = [];
$template_remove_dropdown = [];
$template_name_list = [];
foreach ($templates as $next_template) {
    $template_name = $next_template['layout_template'];
    if (!($template_name === 'template_default' || $template_name === 'default_template_settings') && is_dir(DIR_FS_CATALOG_TEMPLATES . $template_name . DIRECTORY_SEPARATOR)) {
        $template_list_dropdown[] = ['id' => $template_name, 'text' => $template_name];
        $template_name_list[] = $template_name;
        if ($template_name !== 'classic' && $template_name !== 'responsive_classic') {
            $template_remove_dropdown[] = ['id' => $template_name, 'text' => $template_name];
        }
    }
}
$current_template = $db->Execute('SELECT * FROM ' . TABLE_TEMPLATE_SELECT . " WHERE template_language IN (0, $default_language_id) LIMIT 1");
$current_template_dir = ($current_template->EOF) ? '' : $current_template->fields['template_dir'];

$action = 'choose_template';
if (isset($_POST['template_action'])) {
    $template_source = $_POST['template_source'];
    if ($_POST['template_action'] === 'clone') {
        $cloned_name = $_POST['cloned_name'];
        $cloned_display_name = zen_clean_html($_POST['cloned_display_name']);
        $errors_present = $messageStack->size;

        if (empty($cloned_name)) {
            $messageStack->add(ERROR_TEMPLATE_TARGET_BLANK, 'error');
        } elseif (!preg_match('~^[a-zA-Z0-9_]+$~', $cloned_name)) {
            $messageStack->add(ERROR_TEMPLATE_TARGET_INVALID_CHARS, 'error');
        }

        if (empty($cloned_display_name)) {
            $messageStack->add(ERROR_TEMPLATE_TARGET_NAME_BLANK, 'error');
        } elseif (in_array($cloned_display_name, $template_name_list, true)) {
            $messageStack->add(ERROR_TEMPLATE_TARGET_NAME_DUPLICATE, 'error');
        }
        if ($messageStack->size === $errors_present) {
            $action = 'copy_template';
        }
    } elseif ($_POST['template_action'] === 'remove') {
        $action = 'remove_template';
    }

    // -----
    // Create the array that contains the file-system folders that are template-overrideable.
    //
    $override_folders = [
        DIR_FS_CATALOG . 'includes/index_filters/' => 'normal',
        DIR_FS_CATALOG_MODULES => 'normal',
        DIR_FS_CATALOG_MODULES . 'sideboxes/' => 'normal',
        DIR_FS_CATALOG_TEMPLATES  => 'normal',
        DIR_FS_CATALOG_LANGUAGES => 'language',
        DIR_FS_CATALOG_LANGUAGES . '%s/' => 'language',
        DIR_FS_CATALOG_LANGUAGES . '%s/extra_definitions/' => 'language',
        DIR_FS_CATALOG_LANGUAGES . '%s/html_includes/' => 'language',
        DIR_FS_CATALOG_LANGUAGES . '%s/modules/order_total/' => 'language',
        DIR_FS_CATALOG_LANGUAGES . '%s/modules/payment/' => 'language',
        DIR_FS_CATALOG_LANGUAGES . '%s/modules/shipping/' => 'language',
    ];
}
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
  <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
<style>
table, td {
    border-collapse: collapse;
    width: 100%;
    padding: 0.5em;
}
.heading {
    font-weight: bold;
    background-color: #ebebeb;
    border: 1px solid #444;
}
</style>
<script>
function issueWarnings()
{
    return confirm('<?php echo JS_CONFIRMATION_MESSAGE; ?>');
}

function issueRemoveWarnings()
{
    return confirm('<?php echo JS_CONFIRM_REMOVAL_MESSAGE; ?>');
}
</script>
</head>
<body>
<!-- header //-->
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<!-- header_eof //-->

<!-- body //-->
<div class="container-fluid">
    <!-- body_text //-->
    <div class="row">
        <h1><?php echo sprintf(HEADING_TITLE, CLONE_TEMPLATE_VERSION); ?></h1>
        <?php echo TEXT_DESCRIPTION; ?>
    </div>

    <hr>
    <div class="row">
<?php
$zc_plugins_path = '';
$zc_plugins_path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
if ($action === 'copy_template') {
    $source_template = $_POST['template_source'] . DIRECTORY_SEPARATOR;
    $target_template = $_POST['cloned_name'] . DIRECTORY_SEPARATOR;

    require $zc_plugins_path . DIR_WS_CLASSES . 'class.admin.cloneTemplate.php';
    $template_cloner = new cloneTemplate($source_template, $target_template);
//    $template_cloner = new Zencart\Plugins\Admin\CloneTemplate\cloneTemplate($source_template, $target_template);
?>
        <div class="row text-right">
            <a href="<?php echo zen_href_link(FILENAME_CLONE_TEMPLATE); ?>" class="btn btn-default" role="button">
                <?php echo IMAGE_BACK; ?>
            </a>
        </div>

        <hr>
        <table>
            <tr>
                <td class="heading"><?php echo sprintf(MESSAGE_COPYING_FILES, $_POST['template_source'], $_POST['cloned_name']); ?></td>
            </tr>
            <tr>
                <td><?php echo sprintf(MESSAGE_FILE_LOG, $template_cloner->getLogFileName ()); ?></td>
            </tr>
<?php
    foreach ($override_folders as $override_folder_name => $folder_type) {
        if ($folder_type === 'language') {
            foreach ($languages as $current_language) {
                $status = $template_cloner->copyDirectoryFiles(sprintf($override_folder_name, $current_language['directory']) . $source_template);
                foreach ($status as $message => $class) {
?>
            <tr>
                <td class="<?php echo $class; ?>"><?php echo $message; ?></td>
            </tr>
<?php
                }
            }
        } else {
            $status = $template_cloner->copyDirectoryFiles($override_folder_name . $source_template);
            foreach ($status as $message => $class) {
?>
            <tr>
                <td class="<?php echo $class; ?>"><?php echo $message; ?></td>
            </tr>
<?php
            }
        }
    }
?>
        </table>

        <div class="row text-right">
            <a href="<?php echo zen_href_link(FILENAME_CLONE_TEMPLATE); ?>" class="btn btn-default" role="button">
                <?php echo IMAGE_BACK; ?>
            </a>
        </div>
<?php
    $layout_boxes = $db->Execute("SELECT * FROM " . TABLE_LAYOUT_BOXES . " WHERE layout_template = '" . rtrim ($source_template, DIRECTORY_SEPARATOR) . "'");
    $target_template = rtrim($target_template, DIRECTORY_SEPARATOR);
    foreach ($layout_boxes as $next_box) {
        $sql_data_array = $next_box;
        $sql_data_array['layout_template'] = $target_template;
        unset($sql_data_array['layout_id']);

        $check = $db->Execute("SELECT * FROM " . TABLE_LAYOUT_BOXES . " WHERE layout_template = '$target_template' AND layout_box_name = '" . $next_box['layout_box_name'] . "' LIMIT 1");
        if ($check->EOF) {
            zen_db_perform(TABLE_LAYOUT_BOXES, $sql_data_array);
        } else {
            zen_db_perform(TABLE_LAYOUT_BOXES, $sql_data_array, 'update', 'layout_id=' . $next_box['layout_id']);
        }
    }

    if (!file_exists(DIR_FS_CATALOG_TEMPLATES . $source_template . 'template_info.php')) {
        $template_name = 'Missing.';
        $template_version = '?.?';
        $template_author = 'Unknown';
        $template_description = 'Missing.';
        $template_screenshot = '';
    } else {
        require DIR_FS_CATALOG_TEMPLATES . $source_template . 'template_info.php';
    }
    $file_contents  = '<?php' . "\n";
    $file_contents .= '$template_name = \'' . addslashes($cloned_display_name) . '\';' . "\n";
    $file_contents .= '$template_version = \'' . $template_version . '\';' . "\n";
    $file_contents .= '$template_author = \'' . addslashes($template_author) . '\';' . "\n";
    $file_contents .= '$template_description = \'' . addslashes($template_description) . sprintf(TEXT_TEMPLATE_CLONED, substr($source_template, 0, -1), date(PHP_DATE_TIME_FORMAT)) . '\';' . "\n";
    $file_contents .= '$template_screenshot = \'' . $template_screenshot . '\';' . "\n";
    file_put_contents(DIR_FS_CATALOG_TEMPLATES . $target_template . DIRECTORY_SEPARATOR . 'template_info.php', $file_contents);
} elseif ($action === 'remove_template') {
    $source_template = $_POST['template_source'];
    $db->Execute(
        "DELETE FROM " . TABLE_LAYOUT_BOXES . "
          WHERE layout_template = '$source_template'"
    );
    $source_template .= DIRECTORY_SEPARATOR;
    require $zc_plugins_path . DIR_WS_CLASSES . 'class.admin.cloneTemplate.php';
    $template_cloner = new cloneTemplate($source_template);
//    $template_cloner = new Zencart\Plugins\Admin\CloneTemplate\cloneTemplate($source_template);
?>
        <div class="row">
            <div class="row text-right">
                <a href="<?php echo zen_href_link(FILENAME_CLONE_TEMPLATE); ?>" class="btn btn-default" role="button">
                    <?php echo IMAGE_BACK; ?>
                </a>
            </div>
            <hr>
            <table>
                <tr>
                    <td class="heading"><?php echo sprintf(MESSAGE_REMOVING_FILES, $_POST['template_source']); ?></td>
                </tr>
                <tr>
                    <td><?php echo sprintf(MESSAGE_FILE_LOG, $template_cloner->getLogFileName()); ?></td>
                </tr>
<?php
    foreach ($override_folders as $override_folder_name => $folder_type) {
        if ($folder_type === 'language') {
            foreach ($languages as $current_language) {
                $status = $template_cloner->removeDirectoryFiles(sprintf($override_folder_name, $current_language['directory']) . $source_template);
                foreach ($status as $message => $class) {
?>
                <tr>
                    <td class="<?php echo $class; ?>"><?php echo $message; ?></td>
                </tr>
<?php
                }
            }
        } else {
            $status = $template_cloner->removeDirectoryFiles($override_folder_name . $source_template);
            foreach ($status as $message => $class) {
?>
                <tr>
                    <td class="<?php echo $class; ?>"><?php echo $message; ?></td>
                </tr>
<?php
            }
        }
    }
?>
            </table>
            <div class="row text-right">
                <a href="<?php echo zen_href_link(FILENAME_CLONE_TEMPLATE); ?>" class="btn btn-default" role="button">
                    <?php echo IMAGE_BACK; ?>
                </a>
            </div>
<?php
} else {
    if (isset ($template_source)) {
        $current_template_dir = $template_source;
    }
?>
            <div class="row">
                <?php echo zen_draw_form('template_clone', FILENAME_CLONE_TEMPLATE, '', 'post');
                echo TEXT_INSTRUCTIONS_CLONE; ?>
                <div class="form-group">
                    <div class="col-md-3">
                        <label><?php echo TEXT_TEMPLATE_SOURCE; ?></label>
                        <?php echo zen_draw_pull_down_menu('template_source', $template_list_dropdown, $current_template_dir); ?>
                    </div>
                    <div class="col-md-3">
                        <label><?php echo TEXT_TEMPLATE_TARGET; ?></label>
                        <?php echo zen_draw_input_field('cloned_name'); ?>
                    </div>
                    <div class="col-md-4">
                        <label><?php echo TEXT_TEMPLATE_TARGET_NAME; ?></label>
                        <?php echo zen_draw_input_field('cloned_display_name') . zen_draw_hidden_field('template_action', 'clone'); ?>
                    </div>
                    <div class="text-right col-md-2">
                        <button type="submit" class="btn btn-primary" title="<?php echo CLONE_TEMPLATE_GO_ALT; ?>" onclick="return issueWarnings();">
                            <i class="fa fa-copy" aria-hidden="true"></i>
                            <?php echo IMAGE_COPY; ?>
                        </button>
                    </div>
                </div>
                <?php echo '</form>'; ?>
            </div>
            <hr>
            <div class="row">
<?php
    if (count($template_remove_dropdown) === 0) {
        echo TEXT_NOTHING_TO_REMOVE;
    } else {
        echo TEXT_INSTRUCTIONS_REMOVE;
        echo zen_draw_form('template_remove', FILENAME_CLONE_TEMPLATE, '', 'post'); ?>
                <div class="form-group">
                    <div class="col-md-4">
                        <label><?php echo TEXT_TEMPLATE_REMOVE_SOURCE; ?></label>
                        <?php echo zen_draw_pull_down_menu('template_source', $template_remove_dropdown, $current_template_dir) . zen_draw_hidden_field('template_action', 'remove'); ?>
                    </div>
                    <div class="col-md-8">
                        <button type="submit" class="btn btn-danger" title="<?php echo CLONE_TEMPLATE_GO_REMOVE_ALT; ?>" onclick="return issueRemoveWarnings();">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                            <?php echo IMAGE_DELETE; ?>
                        </button>
                    </div>
                </div>
                <?php echo '</form>';
    } ?>
            </div>
<?php
}
?>
        </div>
    <!-- body_text_eof //-->
    </div>
    <!-- body_eof //-->
</div>
<!-- footer //-->
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
<!-- footer_eof //-->
</body>
</html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
