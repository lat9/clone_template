<?php
// -----
// Part of the "Clone Template" plugin for Zen Cart v1.5.0 or later
//
// Copyright (c) 2016, Vinos de Frutas Tropicales (lat9)
//
require ('includes/application_top.php');

$languages = zen_get_languages ();
$default_language_id = 1;
foreach ($languages as $current_language) {
    if ($current_language['code'] == DEFAULT_LANGUAGE) {
        $default_language_id = $current_language['id'];
        $default_language_dir = $current_language['directory'];
        break;
    }
}

$templates = $db->Execute ("SELECT DISTINCT layout_template FROM " . TABLE_LAYOUT_BOXES);
$template_list_dropdown = array ();
$template_name_list = array ();
while (!$templates->EOF) {
    $template_name = $templates->fields['layout_template'];
    if (!($template_name == 'template_default' || $template_name == 'default_template_settings') && file_exists (DIR_FS_CATALOG_TEMPLATES . "$template_name/")) {    
        $template_list_dropdown[] = array ( 'id' => $template_name, 'text' => $template_name );
        $template_name_list[] = $template_name;
    }
    $templates->MoveNext ();
}
$current_template = $db->Execute ("SELECT * FROM " . TABLE_TEMPLATE_SELECT . " WHERE template_language IN (0, $default_language_id) LIMIT 1");
$current_template_dir = ($current_template->EOF) ? '' : $current_template->fields['template_dir'];

$action = 'choose_template';
if (isset ($_POST['copy_template'])) {
    $template_source = $_POST['template_source'];
    $cloned_name = $_POST['cloned_name'];
    $cloned_display_name = zen_clean_html ($_POST['cloned_display_name']);
    if (!zen_not_null ($cloned_name)) {
        $messageStack->add (MESSAGE_BLANK_CLONED_NAME, 'error');
    } elseif (in_array ($cloned_name, $template_name_list)) {
        $messageStack->add (MESSAGE_DUPLICATE_CLONED_NAME, 'error');
    } elseif (!preg_match ('~^[a-zA-Z0-9_]+$~', $cloned_name)) {
        $messageStack->add (MESSAGE_INVALID_CHARS_CLONED_NAME, 'error');
    } else {
        $action = 'copy_template';
        
        // -----
        // Create the array that contains the file-system folders that are template-overrideable.
        //
        $override_folders = array (
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
        );
    }
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<style type="text/css">
<!--
table, td { border-collapse: collapse; }
.full-width { width: 100%; }
.spacing, table.spacing td { padding: 0.5em; }
.no-spacing { padding: 0; }
.v-top { vertical-align: top; }
.right { text-align: right; }
.left { text-align: left; }
.center { text-align: center; }
.heading { font-weight: bold; background-color: #ebebeb; border: 1px solid #444; }
#back-button { text-align: right; }
#back-button a { padding: 0.5em; border: 1px solid #444; background-color: #afafaf; margin-bottom: 0.3em; display: inline-block; font-size: larger; }
-->
</style>
<script type="text/javascript" src="includes/menu.js"></script>
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  
  function issueWarnings ()
  {
      return confirm ('<?php echo JS_CONFIRMATION_MESSAGE; ?>');
  }
  // -->
</script>
</head>
<body onLoad="init();">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table class="full-width spacing">
    <tr>
        <td class="full-width v-top"><table class="full-width spacing">
            <tr>
                <td><table>
                    <tr>
                        <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
                        <td class="pageHeading right"><?php echo zen_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
                    </tr>
<?php
if ($action != 'copy_template') {
?>
                    <tr>
                        <td colspan="2" class="main"><?php echo TEXT_INSTRUCTIONS; ?></td>
                    </tr>
<?php
}
?>
                </table></td>
            </tr>
        </table></td>
    </tr>
<?php
if ($action == 'copy_template') {
    $source_template = $_POST['template_source'] . DIRECTORY_SEPARATOR;
    $target_template = $_POST['cloned_name'] . DIRECTORY_SEPARATOR;
    require (DIR_WS_CLASSES . 'class.admin.cloneTemplate.php');
    $template_cloner = new cloneTemplate ($source_template, $target_template);
?>
    <tr>
        <td class="spacing"><table class="full-width spacing">
            <tr>
                <td id="back-button"><a href="<?php echo zen_href_link (FILENAME_CLONE_TEMPLATE); ?>"><?php echo TC_TEXT_BACK; ?></a></td>
            </tr>
            
            <tr>
                <td class="heading"><?php echo sprintf (MESSAGE_COPYING_FILES, $_POST['template_source'], $_POST['cloned_name']); ?></td>
            </tr>
            
            <tr>
                <td><?php echo sprintf (MESSAGE_FILE_LOG, $template_cloner->getLogFileName ()); ?></td>
            </tr>
<?php
    foreach ($override_folders as $override_folder_name => $folder_type) {
        if ($folder_type == 'language') {
            foreach ($languages as $current_language) {
                $status = $template_cloner->processDirectoryFiles (sprintf ($override_folder_name, $current_language['directory']) . $source_template);
                foreach ($status as $message => $class) {
?>
            <tr><td class="<?php echo $class; ?>"><?php echo $message; ?></td></tr>
<?php
                }
            }
        } else {
            $status = $template_cloner->processDirectoryFiles ($override_folder_name . $source_template);
            foreach ($status as $message => $class) {
?>
            <tr><td class="<?php echo $class; ?>"><?php echo $message; ?></td></tr>
<?php
            }
        }
    }
?>
        </table></td>
    </tr>
<?php
    $layout_boxes = $db->Execute ("SELECT * FROM " . TABLE_LAYOUT_BOXES . " WHERE layout_template = '" . rtrim ($source_template, DIRECTORY_SEPARATOR) . "'");
    $target_template = rtrim ($target_template, DIRECTORY_SEPARATOR);
    while (!$layout_boxes->EOF) {
        $sql_data_array = $layout_boxes->fields;
        $sql_data_array['layout_template'] = $target_template;
        unset ($sql_data_array['layout_id']);
        
        $check = $db->Execute ("SELECT * FROM " . TABLE_LAYOUT_BOXES . " WHERE layout_template = '$target_template' AND layout_box_name = '" . $layout_boxes->fields['layout_box_name'] . "' LIMIT 1");
        if ($check->EOF) {
            zen_db_perform (TABLE_LAYOUT_BOXES, $sql_data_array);
        } else {
            zen_db_perform (TABLE_LAYOUT_BOXES, $sql_data_array, 'update', 'layout_id=' . $check->fields['layout_id']);
        }
        $layout_boxes->MoveNext ();
    }
    
    if (!file_exists (DIR_FS_CATALOG_TEMPLATES . $source_template . 'template_info.php')) {
        $template_name = 'Missing.';
        $template_version = '?.?';
        $template_author = 'Unknown';
        $template_description = 'Missing.';
        $template_screenshot = '';
    } else {
        include (DIR_FS_CATALOG_TEMPLATES . $source_template . 'template_info.php');
    }
    $file_contents  = '<?php' . "\n";
    $file_contents .= '$template_name = \'' . addslashes ($cloned_display_name) . '\';' . "\n";
    $file_contents .= '$template_version = \'' . $template_version . '\';' . "\n";
    $file_contents .= '$template_author = \'' . addslashes ($template_author) . '\';' . "\n";
    $file_contents .= '$template_description = \'' . addslashes ($template_description) . sprintf (TEXT_TEMPLATE_CLONED, substr ($source_template, 0, -1), date (PHP_DATE_TIME_FORMAT)) . '\';' . "\n";
    $file_contents .= '$template_screenshot = \'' . $template_screenshot . '\';' . "\n";
    file_put_contents (DIR_FS_CATALOG_TEMPLATES . $target_template . DIRECTORY_SEPARATOR . 'template_info.php', $file_contents);
} else {
    if (isset ($template_source)) {
        $current_template_dir = $template_source;
    }
?> 
    <tr>    
        <td class="spacing"><?php echo zen_draw_form ('choose_template', FILENAME_CLONE_TEMPLATE, '', 'post'); ?>
            <?php echo '<b>' . TEXT_TEMPLATE_SOURCE . '</b>' . zen_draw_pull_down_menu ('template_source', $template_list_dropdown, $current_template_dir) . '&nbsp;&nbsp;<b>' . TEXT_NEW_TEMPLATE_NAME . '</b>' . zen_draw_input_field ('cloned_name') . '&nbsp;&nbsp;<b>' . TEXT_NEW_TEMPLATE_DISPLAY_NAME . '</b>' . zen_draw_input_field ('cloned_display_name') . zen_draw_hidden_field ('copy_template', 'yes') . '&nbsp;&nbsp;' . zen_image_submit ('button_go.gif', CLONE_TEMPLATE_GO_ALT, 'onclick="return issueWarnings ();"'); ?></td>
        </form></td>
    </tr>
<?php
}
?>           
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br />
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>