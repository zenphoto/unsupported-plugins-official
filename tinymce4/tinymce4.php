<?php

/**
 * Back-end <i>WYSIWYG</i> editor TinyMCE 4.x.
 *
 * You can place your own additional custom configuration files within
 * <var>%USER_PLUGIN_FOLDER%/tiny_mce/config</var>
 * There is a naming convention since there is a difference between Zenphoto (gallery) and Zenpage (news/pages) editor configurations.
 * <var>zenphoto-<yourcustomname>.js.php</var>
 * <var>zenpage-<yourcustomname>.js.php</var>
 *
 * @author Malte Müller (acrylian)
 * @package zpcore\plugins\tinymce4
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext("Text editor TinyMCE 4.x");
$plugin_author = "Malte Müller (acrylian)";
$plugin_category = gettext('Admin');
$plugin_disable = (extensionEnabled('tinymce')) ? sprintf(gettext('Only one TinyMCE editor plugin may be enabled. <a href="#%1$s"><code>%1$s</code></a> is already enabled.'), 'tinymce') : '';
$plugin_deprecated = true;
$option_interface = 'tinymce4Options';

if (!defined('EDITOR_SANITIZE_LEVEL'))
	define('EDITOR_SANITIZE_LEVEL', 4);
zp_register_filter('texteditor_config', 'tinymce4ConfigJS');

/**
 * Plugin option handling class
 *
 */
class tinymce4Options {

	function __construct() {
		setOptionDefault('tinymce4_zenphoto', 'zenphoto-ribbon.js.php');
		setOptionDefault('tinymce4_zenpage', 'zenpage-slim.js.php');
		setOptionDefault('tinymce4_entitiyencoding', 'raw');
		setOptionDefault('tinymce4_textfield-height', 400);
		setOptionDefault('tinymce4_browser-spellcheck', false);
		setOptionDefault('tinymce4_browser-menu', false);
		if (getOption('zp_plugin_tiny_mce')) {
			setOptionDefault('zp_plugin_tinymce4', 5 | ADMIN_PLUGIN);
			purgeOption('zp_plugin_tiny_mce');
		}
	}

	function getOptionsSupported() {
		$configs_zenpage = getTinyMCE4ConfigFiles('zenpage');
		$configs_zenphoto = getTinyMCE4ConfigFiles('zenphoto');
		$options = array(
				gettext('Text editor configuration - Zenphoto') => array(
						'key' => 'tinymce4_zenphoto',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $configs_zenphoto,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to <em>admin</em> editable text other than for Zenpage pages and news articles.')),
				gettext('Text editor configuration - Zenpage') => array(
						'key' => 'tinymce4_zenpage',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $configs_zenpage,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to editing on the Zenpage <em>pages</em> and <em>news</em> tabs.')),
				gettext('Text editor height') => array(
						'key' => 'tinymce4_textfield-height',
						'type' => OPTION_TYPE_CLEARTEXT,
						'order' => 1,
						'desc' => gettext('Predefined height (px) for Zenphoto and Zenpage textfields. Default is 400px.')),
				gettext('Custom image size') => array(
						'key' => 'tinymce_tinyzenpage_customimagesize',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext("Predefined size (px) for custom size images included using tinyZenpage.")),
				gettext('Custom thumb crop - size') => array(
						'key' => 'tinymce_tinyzenpage_customthumb_size',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext("Predefined size (px) for custom cropped thumb images included using tinyZenpage.")),
				gettext('Custom thumb crop - width') => array(
						'key' => 'tinymce_tinyzenpage_customthumb_cropwidth',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext("Predefined crop width (%) for custom cropped thumb  images included using tinyZenpage.")),
				gettext('Custom thumb crop - height') => array(
						'key' => 'tinymce_tinyzenpage_customthumb_cropheight',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext("Predefined crop height (%) for custom cropped thumb images included using tinyZenpage.")),
				gettext('Entity encoding') => array(
						'key' => 'tinymce4_entityencoding',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 3,
						'selections' => array(
								gettext('Raw') => 'raw',
								gettext('Numeric') => 'numeric',
								gettext('Named') => 'named',
								gettext('Named and numeric') => 'named+numeric'
								),
						'null_selection' => gettext('Default (raw)'),
						'desc' => gettext('If encountering issues with special chars and other character entities change this. Note that this applies on re-saving content only. More info on the <a href="https://github.com/tinymce/tinymce-docs-4x/blob/main/_includes/configuration/entity-encoding.md">TinyMCE docs</a>.')),
				gettext('Entities') => array(
						'key' => 'tinymce4_entities',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 3,
						'desc' => gettext('You can adjust how entities are processed. More info on the <a href="https://github.com/tinymce/tinymce-docs-4x/blob/main/_includes/configuration/entities.md">TinyMCE docs</a>.')),
            gettext('Browser spellcheck') => array(
						'key' => 'tinymce4_browser-spellcheck',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 4,
						'desc' => gettext('Check to use your browser spellchecker.<br><strong>Note:</strong> Spellcheck feature should be available in the browser and enabled (location of settings to do so vary from browser to browser).<br><strong>Warning:</strong> Depending on browser, this can be a privacy concern. Do your research on the implementation of spellchek in your browser of choice.')),
            gettext('Browser context menu') => array(
						'key' => 'tinymce4_browser-menu',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 4,
						'desc' => gettext('Check to disable TinyMCE right-click menu, browser right-click menu will be used instead.<br><strong>Note:</strong> If TinyMCE right-click menu is used - browser right-click menu can still be accessed by pressing <em>Ctrl+Right click</em>.')),
		);
		return $options;
	}

	function handleOption($option, $currentValue) {

	}

}

function tinymce4ConfigJS($mode) {
	global $_editorconfig;
	if (empty($_editorconfig)) { // only if we get here first!
		$locale = 'en';
		$loc = getOption("locale");
		if ($loc) {
			if (file_exists(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/tinymce4/langs/' . $loc . '.js')) {
				$locale = $loc;
			} else {
				$loc = substr($loc, 0, 2);
				if (file_exists(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/tinymce4/langs/' . $loc . '.js')) {
					$locale = $loc;
				}
			}
		}

		$_editorconfig = getOption('tinymce4_' . $mode);
		if (!empty($_editorconfig)) {
			$_editorconfig = getPlugin('/tinymce4/config/' . $_editorconfig, true);
			if (!empty($_editorconfig)) {
				require_once($_editorconfig);
			}
		}
	}
	return $mode;
}

function getTinyMCE4ConfigFiles($mode) {
	// get only those that work!
	$files = getPluginFiles($mode . '-*.js.php', 'tinymce4/config/');
	$array = array();
	foreach ($files as $file) {
		$filename = strrchr($file, '/');
		$filename = substr($filename, 1);
		$option = preg_replace('/^' . $mode . '-/', '', $filename);
		$option = ucfirst(preg_replace('/.js.php$/', '', $option));
		$array[$option] = $filename;
	}
	return $array;
}

?>