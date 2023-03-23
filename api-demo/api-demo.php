<?php
/*
 Plugin Name: api demo
 Plugin URI: https://example.com/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 1
 Author URI: https://wordpress.org/
 Text Domain: api-demo
 */

// require_once '/wp-content/plugins/api-demo/vendor/firebase/php-jwt/src/JWT.php';
// use Tmeister\Firebase\JWT\JWT;

 add_action('rest_api_init', function () {
    register_rest_route('wp/v2', '/register', array(
        'methods' => 'POST',
        'callback' => 'register_user',
    ));

    register_rest_route('wp/v2', '/user', array(
        'methods' => 'GET',
        'callback' => 'project_user',
    ));

    register_rest_route('wp/v2', '/project/store', array(
        'methods' => 'POST',
        'callback' => 'create_project',
    ));

    register_rest_route('wp/v2', '/project/update/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_project',
    ));

    register_rest_route('wp/v2', '/project/delete/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'delete_project',
    ));
});

function project_user(){
    global $wpdb;
    $user = wp_get_current_user();
    $result = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}users WHERE id = '$user->ID'" );
    $projects = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}projects WHERE user_id = '$user->ID'");

    if (is_wp_error($result)) {
        return new WP_Error('user_error', $result->get_error_message(), array('status' => 500));
    }

    if (is_wp_error($projects)) {
        return new WP_Error('project_error', $result->get_error_message(), array('status' => 500));
    }

    $response = array(
        'status' => true,
        'data' => [
            'id' => $result->ID,
            'display_name' => $result->display_name,
            'email' => $result->user_email,
            'projects' => $projects,
        ],

    );
    
    return $response;
}

function create_project(WP_REST_Request $request){
    global $wpdb;
    
    if(empty($request->get_header('authorization'))){
        return new WP_Error('token_empty', 'Token is required', array('status' => 400));
    }
    $user = wp_get_current_user();
    $params = $request->get_params();
    $name = $params['name'];
    $description = $params['description'];
    $url = $params['url'];
    $created_at = date("Y-m-d h:m:i");
    $updated_at = date("Y-m-d h:m:i");

    $data = array(
        $user->ID,
        $name,
        $description,
        $url,
        $created_at,
        $updated_at
    );
  
    $create_project = $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}projects ( user_id ,name, description, url, created_at, updated_at ) VALUES ( %d, %s, %s, %s, %s, %s )", $data));

    if (is_wp_error($create_project)) {
        return new WP_Error('create_project_error', $create_project->get_error_message(), array('status' => 500));
    }

    $response = array(
        'status' => true,
        'msg' => 'Create project successfully',

    );

    
    return new WP_REST_Response($response, 200);
}

function update_project(WP_REST_Request $request){
    global $wpdb;
    
    if(empty($request->get_header('authorization'))){
        return new WP_Error('token_empty', 'Token is required', array('status' => 400));
    }

    $user = wp_get_current_user();
    $params = $request->get_params();
    $name = $params['name'];
    $id = $params['id'];
    $description = $params['description'];
    $url = $params['url'];
    $updated_at = date("Y-m-d h:m:i");
  
    $update_project = $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}projects SET name = %s, description = %s, url = %s, updated_at = %s WHERE id = %d AND user_id = %d",
         $name, $description, $url, $updated_at, $id, $user->ID
    ));

    if (is_wp_error($update_project)) {
        return new WP_Error('updated_project_error', $update_project->get_error_message(), array('status' => 500));
    }
    if($update_project){
        $response = array(
            'status' => true,
            'msg' => 'Update project successfully',
        );
    }else{
        $response = array(
            'status' => false,
            'msg' => 'Update project failed',
    
        );
    }
    
    return new WP_REST_Response($response, 200);
}

function delete_project(WP_REST_Request $request){
    global $wpdb;
    
    if(empty($request->get_header('authorization'))){
        return new WP_Error('token_empty', 'Token is required', array('status' => 400));
    }

    $user = wp_get_current_user();
    $id = $request->get_param('id');
  
    $delete_project = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}projects WHERE id = %d AND user_id = %d",
                $id, $user->ID
            )
    );

    if (is_wp_error($delete_project)) {
        return new WP_Error('delete_project_error', $delete_project->get_error_message(), array('status' => 500));
    }

    if($delete_project){
        $response = array(
            'status' => true,
            'msg' => 'Delete project successfully',
        );
    }else{
        $response = array(
            'status' => false,
            'msg' => 'Delete project failed',
    
        );
    }
    
    return new WP_REST_Response($response, 200);
}

function register_user(WP_REST_Request $request)
{
    $username = $request->get_param('username');
    $email = $request->get_param('email');
    $password = $request->get_param('password');

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_Error('register_error', $user_id->get_error_message(), array('status' => 400));
    }
    $token = array('user_id' => $user_id->ID,);
    $response = array(
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email,
    );

    return new WP_REST_Response($response, 200);
}






