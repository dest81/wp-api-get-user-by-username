<?php
/**
 * Plugin Name: WP-API Get User
 * Plugin URI: https://github.com/dest81/wp-api-get-user-by-username
 * Description: that allows get user by username related by WP-API
 * Version: 0.1.1
 * Author URI:
 * License: MIT
 */

class WP_API_Get_User {
    protected $server;

    /**
     *
     * Prepare a User entity from a WP_User instance.
     *
     * @param WP_User $user
     * @param string $context One of 'view', 'edit', 'embed'
     * @return array
     */
    protected function prepare_user( $user, $context = 'view' ) {
        $user_fields = array(
            'ID'          => $user->ID,
            'username'    => $user->user_login,
            'name'        => $user->display_name,
            'first_name'  => $user->first_name,
            'last_name'   => $user->last_name,
            'nickname'    => $user->nickname,
            'slug'        => $user->user_nicename,
            'URL'         => $user->user_url,
            'avatar'      => json_get_avatar_url( $user->user_email ),
            'description' => $user->description,
        );

        $user_fields['registered'] = date( 'c', strtotime( $user->user_registered ) );

        if ( $context === 'view' || $context === 'edit' ) {
            $user_fields['roles']        = $user->roles;
            $user_fields['capabilities'] = $user->allcaps;
            $user_fields['email']        = false;
        }

        if ( $context === 'edit' ) {
            // The user's specific caps should only be needed if you're editing
            // the user, as allcaps should handle most uses
            $user_fields['email']              = $user->user_email;
            $user_fields['extra_capabilities'] = $user->caps;
        }

        $user_fields['meta'] = array(
            'links' => array(
                'self' => json_url( '/users/' . $user->ID ),
                'archives' => json_url( '/users/' . $user->ID . '/posts' ),
            ),
        );

        return apply_filters( 'json_prepare_user', $user_fields, $user, $context );
    }

    /**
     * Class constructor
     */
    public function __construct() {
        add_action('wp_json_server_before_serve', array($this, 'init'));
    }

    /**
     * Plugin bootstrap
     *
     * @param WP_JSON_ResponseHandler $server Server object.
     */
    public function init($server) {
        $this->server = $server;
        add_filter('json_endpoints', array($this, 'register_routes'));
    }

    /**
     * Registers routes for the endpoints
     *
     * @param array $routes Existing routes
     * @return array Modified routes
     */
    public function register_routes($routes) {
        $routes['/users/user/(?P<login>[0-9a-zA-Z_-]+)'] = array(
            array(array($this, 'get_user_by_login'), WP_JSON_Server::READABLE)
        );
        $routes['/users/email/(?P<email>.+)'] = array(
            array(array($this, 'get_user_by_email'), WP_JSON_Server::READABLE)
        );

        return $routes;
    }
     /**
     * Retrieve a user by login.
     *
     * @param str $login User Login
     * @param string $context
     * @return response
     */
    public function get_user_by_login( $login, $context = 'view' ) {
        $login = (string) $login;
        $current_user_id = get_current_user_id();

        if ( $current_user_id !== $id && ! current_user_can( 'list_users' ) ) {
            return new WP_Error( 'json_user_cannot_list', __( 'Sorry, you are not allowed to view this user.' ), array( 'status' => 403 ) );
        }

        $user = get_user_by( 'login', $login );

        if ( empty( $user->ID ) ) {
            //return new WP_Error( 'json_user_invalid_id', __( 'Invalid username.' ), array( 'status' => 400 ) );

            return [];
        }

        return $this->prepare_user( $user, $context );
    }
    /**
     * Retrieve a user by email.
     *
     * @param str $email User Email
     * @param string $context
     * @return response
     */
    public function get_user_by_email( $email, $context = 'view' ) {
        $email = (string) $email;
        $current_user_id = get_current_user_id();

        if ( $current_user_id !== $id && ! current_user_can( 'list_users' ) ) {
            return new WP_Error( 'json_user_cannot_list', __( 'Sorry, you are not allowed to view this user.' ), array( 'status' => 403 ) );
        }

        $user = get_user_by( 'email', $email );

        if ( empty( $user->ID ) ) {
            //return new WP_Error( 'json_user_invalid_id', __( 'Invalid username.' ), array( 'status' => 400 ) );

            return [];
        }

        return $this->prepare_user( $user, $context );
    }

}

$multipost = new WP_API_Get_User();