<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PRODUSubscriberList extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Subscriber'),
            'plural'   => __('Subscribers'),
            'ajax'     => FALSE
        ]);
    }

    public function get_columns() {
        return [
            'cb'        => '<input type="checkbox" />',
            'name'      => __('Name'),
            'login'     => __('Username'),
            'email'     => __('Email'),
            'suscripto' => __('Suscripción'),
            'plan'      => __('Plan'),
            'member'    => __('Member'),
            'actions'   => __('Actions'),
            // 'date'      => __('Date')
        ];
    }

    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="subscriber[]" value="%s" />', $item->ID
        );
    }

    public function get_sortable_columns() {
        return [
            'name'  => ['name', TRUE],
            'email' => ['email', FALSE],
            'login' => ['login', FALSE],
            'plan'  => ['plan', FALSE]
        ];
    }

    protected function column_name($item) {
        $edit_link = admin_url('subscription-dashboard&page=subscription-member&member_id='.$item->ID);
        $name = sprintf('<a href="%s">%s</a>', esc_url($edit_link), esc_html($item->display_name));

        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>', esc_url($edit_link), __('Edit')),
            // 'delete' => sprintf('<a href="?page=%s&action=%s&subscriber=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->ID),
        ];

        return sprintf('<b>%s</b> %s', $name, $this->row_actions($actions));
    }

    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'login':
                printf('%s', esc_html($item->data->user_login));
                break;
            case 'email':
                printf('%s', esc_html($item->data->user_email));
                break;
            case 'suscripto':
                $subscribed = SubscriptionHelper::getSubscriptionByUserMeta($item->ID);
                $url = '';
                if ($subscribed) {
                    $status = get_field('subscriptions_sub_status', $subscribed->ID);
                    switch ($status) {
                        case 'activa':
                            $status = '<span title="Activa" class="dashicons dashicons-yes icons-status active"></span>';
                            if (isset($end_date) && $today == $end_date) {
                                $status = '<span title="Activa, por vencer" class="dashicons dashicons-update icons-status update"></span>';
                            }
                            break;
                        case 'inactiva':
                            $status = '<span title="Inactiva" class="dashicons dashicons-no icons-status inactive"></span>';
                            break;
                        case 'vencida':
                            $status = '<span title="Vencida" class="dashicons dashicons-clock icons-status defeated"></span>';
                            break;
                    }
                    $edit_link = get_edit_post_link($subscribed->ID);

                    $url = '<a title="Ver suscripción" href="'.esc_url($edit_link).'">'.$status.'</a>';
                }
                printf('%s', $url);
                break;
            case 'plan':
                $plan = SubscriptionHelper::getPlanByUserMeta( $item->ID );
                if ($plan) {
                    printf('%s', esc_html($plan->name));
                }
                break;
            case 'member':
                $isMember = SubscriptionHelper::corporateBeneficiary( $item->ID );
                if ( $isMember > 0 ) {
                    $edit_link = get_edit_post_link( $isMember );
                    $url = '<a title="Ver suscripción" href="'.esc_url($edit_link).'"><span class="dashicons dashicons-superhero-alt icons-status update"></span></a>';
                    printf('%s', $url);
                }
                break;
            case 'actions':
                break;
            case 'date':
                return $item->$column_name;
            default:
                return print_r($item, TRUE);
        }
    }

    private function get_subscribers_data($orderby = 'display_name', $order = 'asc', $per_page = 20, $paged = 1, $search = '') {
        $args = array(
            'role'              => 'subscriber',
            'orderby'           => $orderby,
            'order'             => $order,
            'number'            => $per_page,
            'offset'            => ($paged - 1) * $per_page,
            'count_total'       => TRUE,
            'search'            => '*' . $search . '*',
            'search_columns'    => array('display_name', 'user_login', 'user_nicename', 'user_email'),
        );

        $user_query = new WP_User_Query($args);
        return $user_query;
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->process_bulk_action();

        # Ordenación
        $orderby = !empty($_GET['orderby']) ? $_GET['orderby'] : 'display_name';
        $order = !empty($_GET['order']) ? $_GET['order'] : 'asc';

        # Paginación
        $per_page = $this->get_items_per_page('subscribers_per_page', 20);
        $current_page = $this->get_pagenum();

        # Búsqueda
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        # Filtros
        $role_filter = isset($_REQUEST['role_filter']) ? $_REQUEST['role_filter'] : '';

        $user_query = $this->get_subscribers_data($orderby, $order, $per_page, $current_page, $search);

        $this->items = $user_query->get_results();
        $total_items = $user_query->get_total();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }
}
