<?php
/**
 * Plugin Name: Produ Subscription
 * Plugin URI: https://produ.com/
 * Description: Produ Subscription makes possible to manage subscription custom post type for PRODU
 * Version: 0.9.0
 * Author: PRODU Dev Team
 * License: GPLv2 or later
 * Text Domain: produ-subscription
 */

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (file_exists(__DIR__ . '/lib/dompdf/autoload.inc.php')) {
    require_once __DIR__ . '/lib/dompdf/autoload.inc.php';
}

function my_plugin_admin_notice(){
    if ( !is_plugin_active('advanced-custom-fields-pro/acf.php') ) {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Por favor, activa Advanced Custom Fields Pro para utilizar este plugin.', 'text-domain'); ?></p>
        </div>
        <?php
    }
}

add_action('admin_notices', 'my_plugin_admin_notice');

define('PRODUSUBSCRIPTION_VERSION', '0.1.0');
define('PRODUSUBSCRIPTION__MINIMUM_WP_VERSION', '4.0');
define('PRODUSUBSCRIPTION__PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRODUSUBSCRIPTION__PLUGIN_URL', plugins_url('produ-subscription/'));
define('PRODUSUBSCRIPTION_DELETE_LIMIT', 100000);

require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/class.produ-subscription.php';
require_once PRODUSUBSCRIPTION__PLUGIN_DIR . 'classes/class.produ-roles.php';

function produ_subscription_plugin_activate() {
    $roles = new PRODUSubscriptionRoles(); 
    $roles->add_produ_subscription_roles();
    PRODUSubscription::flush_rewrite();
}

function produ_subscription_plugin_deactivate() {
    PRODUSubscriptionRoles::remove_produ_subscription_roles(); 
    PRODUSubscription::flush_rewrite();
}

register_activation_hook( __FILE__, 'produ_subscription_plugin_activate' );
register_deactivation_hook( __FILE__, 'produ_subscription_plugin_deactivate' );


$subscription = new PRODUSubscription();