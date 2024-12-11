<?php 
/**
 * Class PRODUSubscriptionRoles
 * 
 * Includes the @PRODUSubscriptionRoles class that manages the creation and removal of roles, 
 * as well as the assignment of capabilities for subscribers, 
 * and disables the default WordPress "subscriber" role.
 */

class PRODUSubscriptionRoles {

    public function __construct() {
        add_action('init', [$this, 'add_produ_subscription_roles']);
        add_action('init', [$this, 'remove_wp_subscriber_capabilities']);
        add_action('admin_init', [$this, 'block_admin_access']);
    }

    public function block_admin_access() {
        if (current_user_can('produ_subscriber') && is_admin()) {
            wp_redirect(home_url());
            exit;
        }
    }

    /**
     * Create produ subscription roles.
     */
    public function add_produ_subscription_roles() {
        $caps = array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => false,
            'edit_pages' => false,
            'delete_pages' => false,
        );

        add_role('produ_subscriber', __('Produ Subscriber', 'textdomain'), $caps);
    }

    /**
     * Remove capabilities from the default WordPress subscriber role.
     */
    public function remove_wp_subscriber_capabilities() {
        $role = get_role('subscriber');

        if (!empty($role)) {
            foreach ($role->capabilities as $cap => $value) {
                $role->remove_cap($cap);
            }
        }
    }

    /**
     * Remove the produ subscription roles when the plugin is deactivated.
     */
    public static function remove_produ_subscription_roles() {
        remove_role('produ_subscriber');
    }
}
