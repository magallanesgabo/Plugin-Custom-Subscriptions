<?php
/**
 * login-functions.php contains all the functions related to login
 */

/**
 * If the subscriber is logged in, I redirect them to home
 */
function redirect_subscriber_to_home() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $user_roles = get_userdata($user_id)->roles;
        if ( in_array( 'subscriber', $user_roles ) ) {
            if ( isset($_GET['redirect']) ) {
                $redirect_url = esc_url_raw( $_GET['redirect'] );
                wp_redirect($redirect_url);
                exit;
            }
            wp_redirect( home_url() );
            exit;
        }
    }
}

/**
 * I prevent a subscriber user from entering the admin
 */
function prevent_admin_access_to_subscribers() {
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        if ( in_array( 'subscriber', $user->roles ) ) {
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                return;
            }

            wp_redirect( home_url() );
            exit;
        }
    }
}
add_action( 'admin_init', 'prevent_admin_access_to_subscribers' );

/**
 * Hide the top wordpress bar from logged in subscriber users
 */
function hide_top_bar_to_subscribers() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
	    $user_roles = get_userdata( $user_id )->roles;
        if ( in_array( 'subscriber', $user_roles ))  {
            add_filter( 'show_admin_bar', '__return_false' );
        }
    }
}
add_action( 'wp', 'hide_top_bar_to_subscribers' );

/**
 * Redirect visitors who saw more than 5 news items to login
 */
function redirect_visitors_to_login() {
    if ( !is_user_logged_in() ) {
        if ( is_single() ) {
            $post_limit = get_option( 'tv_posts_limit', '5' );
            $desired_category = 'television';
            $count_posts = isset( $_COOKIE['count_posts_all_categories'] ) ? (int) $_COOKIE['count_posts_all_categories'] : 0;
            setcookie( 'count_posts_all_categories', $count_posts, time() + 24 * HOUR_IN_SECONDS, '/' );

            $categories = get_the_category();
            $has_television_category = FALSE;

            foreach ( $categories as $category ) {
                if ( $category->slug === $desired_category ) {
                    $has_television_category = TRUE;
                    break;
                }
            }

            if ( $has_television_category ) {
                $count_posts++;
            }

            if ( $count_posts > $post_limit ) {
                $current_url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $redirect_url = add_query_arg( 'redirect', urlencode( $current_url ), home_url( '/login/' ) );
                wp_redirect( $redirect_url );
                exit;
            } else {
                setcookie( 'count_posts_all_categories', $count_posts, time() + 24 * HOUR_IN_SECONDS, '/' );
            }

        }
    }
}
add_action( 'template_redirect', 'redirect_visitors_to_login' );

/**
 * Redirect subscribers who saw more than 5 news from the television category to the subscription page
 */
function redirect_subscribers_to_subscription() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();

        $desired_category = 'television';

        $categories = get_the_category();
        $has_television_category = FALSE;

        foreach ( $categories as $category ) {
            if ($category->slug === $desired_category) {
                $has_television_category = TRUE;
                break;
            }
        }

        if ( $has_television_category && is_single() ) {
            $count_posts = get_transient( 'count_television_posts_'.$user_id );

            if ( $count_posts === FALSE ) {
                $count_posts = 0;
            }

            $count_posts++;
            $post_limit = 5;

            if ( $count_posts > $post_limit ) {
                $subscription_url = 'suscribete';
                wp_redirect( $subscription_url );
                exit;
            } else {
                #Limitation lasts 24 hours
                set_transient( 'count_television_posts_' . $user_id, $count_posts, 24 * HOUR_IN_SECONDS );
            }
        }
    }
}
// add_action( 'template_redirect', 'redirect_subscribers_to_subscription' );