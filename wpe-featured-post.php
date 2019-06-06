<?php

/**
 * Plugin Name:       WPE Featured Post
 * Description:       Featured on WP Engine's blog
 * Version:           1.0.0
 * Author:            Benjamin Bond for WP Engine
 * Author URI:        http://netswagger.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

function wpe_fp_meta() {
    add_meta_box( 'wpe_fp_meta', __( 'Featured Posts', 'sm-textdomain' ), 'wpe_fp_meta_callback', 'post' );
}
function wpe_fp_meta_callback( $post ) {
    $featured = get_post_meta( $post->ID );
    ?>
 
	<p>
    <div class="sm-row-content">
        <label for="wpe-fp-check">
            <input type="checkbox" name="wpe-fp-check" id="wpe-fp-check" value="yes" <?php if ( isset ( $featured['wpe-fp-check'] ) ) checked( $featured['wpe-fp-check'][0], 'yes' ); ?> />Feature WP Engine's blog
        </label>
        
    </div>
</p>
 
    <?php
}
add_action( 'add_meta_boxes', 'wpe_fp_meta' );


/**
 * Saves the custom meta input
 */
function wpe_fp_save( $post_id ) {
 

    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'sm_nonce' ] ) && wp_verify_nonce( $_POST[ 'sm_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
 
    // Exits script depending on save status
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }
 
	 // Checks for input and saves
	if( isset( $_POST[ 'wpe-fp-check' ] ) ) {
	    update_post_meta( $post_id, 'wpe-fp-check', 'yes' );
	} else {
	    update_post_meta( $post_id, 'wpe-fp-check', '' );
	}
 
}
add_action( 'save_post', 'wpe_fp_save' );

function my_rest_prepare_post( $data, $post, $request ) {
  $_data = $data->data;
  $_data['wpe-fp-check'] = get_post_meta( $post->ID, 'wpe-fp-check', true );
  $data->data = $_data;
  return $data;
}
add_filter( 'rest_prepare_post', 'my_rest_prepare_post', 10, 3 );



add_action( 'rest_api_init', 'wp_rest_filter_add_filters' );
 
// adds custom meta filter to each post
function wp_rest_filter_add_filters() {
    foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
        add_filter( 'rest_' . $post_type->name . '_query', 'wp_rest_filter_add_filter_param', 10, 2 );
    }
}
// add filter paramiters
function wp_rest_filter_add_filter_param( $args, $request ) {
    if ( empty( $request['filter'] ) || ! is_array( $request['filter'] ) ) {
        return $args;
    }
    $filter = $request['filter'];
    if ( isset( $filter['posts_per_page'] ) && ( (int) $filter['posts_per_page'] >= 1 && (int) $filter['posts_per_page'] <= 100 ) ) {
        $args['posts_per_page'] = $filter['posts_per_page'];
    }
    global $wp;
    $vars = apply_filters( 'rest_query_vars', $wp->public_query_vars );
    function allow_meta_query( $valid_vars )
    {
        $valid_vars = array_merge( $valid_vars, array( 'meta_query', 'meta_key', 'meta_value', 'meta_compare' ) );
        return $valid_vars;
    }
    $vars = allow_meta_query( $vars );

    foreach ( $vars as $var ) {
        if ( isset( $filter[ $var ] ) ) {
            $args[ $var ] = $filter[ $var ];
        }
    }
    return $args;
}
