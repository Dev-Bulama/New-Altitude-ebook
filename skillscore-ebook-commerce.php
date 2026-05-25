<?php
/**
 * Plugin Name: SkillScore Ebook Commerce
 * Plugin URI: https://skillscoreit.com
 * Description: A comprehensive ebook commerce solution with audio previews, multiple payment gateways, and secure downloads.
 * Version: 1.0.0
 * Author: SkillScore IT Solutions and Training
 * Author URI: https://skillscoreit.com
 * Developer: Tijani Bulama
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: skillscore-ebook
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('SKILLSCORE_EBOOK_VERSION', '1.0.0');
define('SKILLSCORE_EBOOK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SKILLSCORE_EBOOK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SKILLSCORE_EBOOK_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_skillscore_ebook() {
    require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-activator.php';
    SkillScore_Ebook_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_skillscore_ebook() {
    require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-deactivator.php';
    SkillScore_Ebook_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_skillscore_ebook');
register_deactivation_hook(__FILE__, 'deactivate_skillscore_ebook');

/**
 * The core plugin class.
 */
require SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-ebook-core.php';

/**
 * Begins execution of the plugin.
 */
function run_skillscore_ebook() {
    $plugin = new SkillScore_Ebook_Core();
    $plugin->run();
}

run_skillscore_ebook();
