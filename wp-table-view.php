<?php
/*
  Plugin Name: WP Table View
  Plugin URI: http://infobeans.com
  Description: Create a table view for post,page,custom post types, with pageination and search functionality.
  Version: 1.0
  Author: Shivraj Singh Rawat
  Author URI: https://profiles.wordpress.org/shivrajib
  License: GPLv2 or later
  Text Domain: wptv
 */

//include required files
require_once 'wptv-html-helper.php';
require_once 'wptv-shortcode.php';

/* actions */
add_action('init', 'wptv_init');
add_action('admin_enqueue_scripts', 'wptv_wp_admin_style');
add_action('wp_enqueue_scripts', 'register_front_end_scripts');
add_action('add_meta_boxes', 'wptv_register_meta_box');
add_action('save_post', 'cd_meta_box_save');
/*** Ajax Calls ***/
add_action('wp_ajax_wptv_get_post_fields', 'wptvGetPostFields');
add_action('wp_ajax_wptv_get_post_meta_fields', 'wptvGetPostMetaFields');
/* filters */

function wptv_wp_admin_style() {
    wp_enqueue_script('wptv_admin_js', plugins_url('js/wptv-script.js', __FILE__));
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_style('jquery-ui', plugins_url('css/jquery-ui.css', __FILE__));
    wp_enqueue_style('wptv_admin_style', plugins_url('css/wptv-style.css', __FILE__));
}

function register_front_end_scripts() {
    wp_enqueue_script('data-table', plugins_url('js/jquery.dataTables.min.js', __FILE__), array('jquery'));
    wp_enqueue_style('data-table',plugins_url('css/jquery.dataTables.min.css', __FILE__));
}

function wptv_init() {
    $labels = array(
        'name' => _x('Table Views', 'post type general name', 'wptv'),
        'singular_name' => _x('Table View', 'post type singular name', 'wptv'),
        'menu_name' => _x('Table Views', 'admin menu', 'wptv'),
        'name_admin_bar' => _x('Table View', 'add new on admin bar', 'wptv'),
        'add_new' => _x('Add New Table View', 'book', 'wptv'),
        'add_new_item' => __('Add New Table View', 'wptv'),
        'new_item' => __('New Table View', 'wptv'),
        'edit_item' => __('Edit Table View', 'wptv'),
        'view_item' => __('View Table View', 'wptv'),
        'all_items' => __('All Table Views', 'wptv'),
        'search_items' => __('Search Table Views', 'wptv')
    );

    $args = array(
        'labels' => $labels,
        'description' => __('Description.', 'wptv'),
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'wptvs'),
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title')
    );

    register_post_type('wptv', $args);
}

//Register Meta Box
function wptv_register_meta_box() {
    add_meta_box('wptv-short-code-meta', esc_html__('Add below short-code in content editor or template file to display table view', ''), 'wptv_short_code_meta_box', 'wptv', 'advanced', 'high');
    add_meta_box('wptv-post-type', esc_html__('Select Post Type', ''), 'wptv_post_type_meta_box_callback', 'wptv', 'advanced', 'high');
    add_meta_box('wptv-post-details', esc_html__('Select Post Columns', ''), 'wptv_post_field_meta_box_callback', 'wptv', 'advanced', 'high');
    add_meta_box('wptv-post-meta_details', esc_html__('Select Post Meta Columns', ''), 'wptv_post_meta_meta_box_callback', 'wptv', 'advanced', 'high');
    add_meta_box('wptv-general-settings', esc_html__('General Settings', ''), 'wptv_general_settings_meta_box_callback', 'wptv', 'advanced', 'high');
}

function wptv_short_code_meta_box($meta_id) {
    echo "[wptv id='" . $meta_id->ID . "' type='" . get_post_meta($meta_id->ID, 'wptv_post_type', true) . "']";
}

//general settings
function wptv_general_settings_meta_box_callback($meta_id) {
    $wptv_general_settings = get_post_meta($meta_id->ID, 'wptv_general_settings', true);
    ?>
    <div>
        <div class="wptv-field">
            <label>Show Default Search</label>
            <input type="checkbox" name="wptv_general_settings[wptv_show_search]" value="1" <?php echo!empty($wptv_general_settings['wptv_show_search']) ? 'checked' : ''; ?>>
            <div class="wptv_help_text">(show default text search option)</div>
        </div>
        <div class="wptv-field">
            <label>Show Pagination</label>
            <input type="checkbox" name="wptv_general_settings[wptv_show_pagination]" value="1" <?php echo!empty($wptv_general_settings['wptv_show_pagination']) ? 'checked' : ''; ?>>        
        </div>
        <div class="wptv-field">
            <label>Show Records Info</label>
            <input type="checkbox" name="wptv_general_settings[wptv_show_info]" value="1" <?php echo!empty($wptv_general_settings['wptv_show_info']) ? 'checked' : ''; ?>>        
        </div>
        <div class="wptv-field">
            <label>Number of Records</label>
            <input type="text" name="wptv_general_settings[wptv_no_of_reco]" value="<?php echo empty($wptv_general_settings['wptv_no_of_reco']) ? '10' : $wptv_general_settings['wptv_no_of_reco']; ?>">
            <div class="wptv_help_text">(show number of records per page)</div>
        </div>
    </div>
    <?php
}

//Add field
function wptv_post_type_meta_box_callback($meta_id) {
    echo wptvGetPostTypeOptions(get_post_meta($meta_id->ID, 'wptv_post_type', true));
}

//Add field
function wptv_post_field_meta_box_callback($meta_id) {
    echo wptvGetPostFieldOptions($meta_id);
}

//Add field
function wptv_post_meta_meta_box_callback($meta_id) {
    echo wptvGetPostMetaOptions($meta_id);
}

function wptvGetPostFieldOptions($meta_id) {
    $post_type = get_post_meta($meta_id->ID, 'wptv_post_type', true);
    $options = get_post_meta($meta_id->ID, 'wptv_post_field', true);
    //echo '<div id="wptv_post_fields">'.wptvGetSelectedPostOptions($post_type,$options).'</div>';
    $fields = array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments');
    $htmlHelper = new WPTV_Html_Helper();
    echo '<div id="wptv_post_fields">' . $htmlHelper->generateOptionFieldsHtml($htmlHelper->post_fields, 'post_field', 'wptv_post_field', $post_type, $options) . '</div>';
}

function wptvGetPostMetaOptions($meta_id) {
    $post_type = get_post_meta($meta_id->ID, 'wptv_post_type', true);
    $options = get_post_meta($meta_id->ID, 'wptv_post_meta_field', true);
    $htmlHelper = new WPTV_Html_Helper();
    echo '<div id="wptv_post_meta_fields">' . $htmlHelper->generateOptionFieldsHtml(wptvGetMetaKeys($post_type), 'post_meta_field', 'wptv_post_meta_field', $post_type, $options) . '</div>';
}

function wptvGetPostTypeOptions($selected_post_type) {
    $retunHtml = '';
    $args = array('public' => true);
    $output = 'objects'; // names or objects, note names is the default
    $post_types = get_post_types($args, $output);
    $retunHtml .= '<select name="wptv_post_type" id="wptv_post_type">';
    $retunHtml .= '<option>please select post type</option>';
    foreach ($post_types as $post_type) {
        $selected = ($post_type->name == $selected_post_type) ? 'selected="selected"' : '';
        $retunHtml .= '<option value="' . $post_type->name . '" ' . $selected . '>' . $post_type->label . '</option>';
    }
    $retunHtml .= '</select>';
    return $retunHtml;
}



function wptvGetPostFields() {
    $htmlHelper = new WPTV_Html_Helper();
    echo $htmlHelper->generateOptionFieldsHtml($htmlHelper->post_fields, 'post_field', 'wptv_post_field', $_POST['type']);
    exit();
}

function wptvGetPostMetaFields() {
    $htmlHelper = new WPTV_Html_Helper();
    echo $htmlHelper->generateOptionFieldsHtml(wptvGetMetaKeys($_POST['type']), 'post_meta', 'wptv_post_meta_field', $_POST['type']);
    exit();
}



function cd_meta_box_save($post_id) {

    $post_type = get_post_type($post_id);

    if ($post_type == 'wptv') {

        // Bail if we're doing an auto save
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        // if our current user can't edit this post, bail
        if (!current_user_can('edit_post'))
            return;

        if (isset($_POST['wptv_general_settings'])) {
            $general_settings = array_map( 'sanitize_text_field', $_POST['wptv_general_settings']);
            update_post_meta($post_id, 'wptv_general_settings', $general_settings);
        }

        if (isset($_POST['wptv_post_field'])) {
            //PREPARE ARRAY BEFORE SAVING
            $wptv_post_fields = array();
            foreach ($_POST['wptv_post_field'] as $post_field) {
                if (!empty($post_field['field'])) {
                    $wptv_post_fields[] = array_map( 'wp_kses_post', $post_field);
                }
            }            
            update_post_meta($post_id, 'wptv_post_field', $wptv_post_fields);
        }

        if (isset($_POST['wptv_post_meta_field'])) {
            //PREPARE ARRAY BEFORE SAVING
            $wptv_meta_fields = array();
            foreach ($_POST['wptv_post_meta_field'] as $meta_field) {
                if (!empty($meta_field['field'])) {
                    $wptv_meta_fields[] = array_map( 'wp_kses_post', $meta_field);
                }
            }
            update_post_meta($post_id, 'wptv_post_meta_field', $wptv_meta_fields);
        }

        if (isset($_POST['wptv_post_type'])) {
            update_post_meta($post_id, 'wptv_post_type', sanitize_text_field($_POST['wptv_post_type']));
        }
    }
}

function wptvGetMetaKeys($post_type) {
    if (empty($post_type))
        return;
    global $wpdb;
    $query = "
        SELECT DISTINCT($wpdb->postmeta.meta_key) 
        FROM $wpdb->posts 
        LEFT JOIN $wpdb->postmeta 
        ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
        WHERE $wpdb->posts.post_type = '%s' 
        AND $wpdb->postmeta.meta_key != '' 
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' 
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'
    ";
    $meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));
    return $meta_keys;
}
