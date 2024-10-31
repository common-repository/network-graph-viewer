<?php
/*
Plugin Name: Sigmajs Viewer
Plugin URI: http://cdh.ucla.edu
Description: Provide a custom post type Graph to upload and display Sigma.js content
Version: 1.0
Author: Yu Xie@CDH
License: GPLv2
*/


add_action('init', 'sv_create_graph');
add_action('init', 'sv_create_taxonomy_course');
add_action('init', 'sv_create_taxonomy_project');
add_action('init', 'sv_create_taxonomy_author');
add_action('add_meta_boxes', 'sv_graph_meta' );
add_action('save_post', 'sv_save_uploaded_files_and_metadata');
add_filter('manage_edit-graph_columns', 'sv_graph_edit_columns');
add_action('manage_posts_custom_column',  'sv_graph_custom_columns');
add_action('post_edit_form_tag', 'sv_update_edit_form');
add_action('wp_enqueue_scripts','sv_javascript_init');
add_filter('upload_mimes','sv_add_custom_mime_types');


/* Create custom post type */
function sv_create_graph() {
    register_post_type( 'graph',
        array(
            'labels' => array(
                'name' => 'Graph',
                'singular_name' => 'Graph',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Graph',
                'edit' => 'Edit',
                'edit_item' => 'Edit Graph',
                'new_item' => 'New Graph',
                'view' => 'View',
                'view_item' => 'View Graph',
                'search_items' => 'Search Graph',
                'not_found' => 'No Graph',
                'not_found_in_trash' => 'No Graph',
                'parent' => 'Parent Graph'
            ),
 
            'public' => true,
            'menu_position' => 15,
            'supports' => array( 'title', 'editor'),
            'show_ui' => true,
            'rewrite' => true,
            'taxonomies' => array( '' ),
            'menu_icon' => plugins_url( 'images/gephi.png', __FILE__ ),
            'has_archive' => 'graphs'
        )
    );
}

/* Register tag Author */
function sv_create_taxonomy_author() {
    register_taxonomy(
        'AuthorName',
        'graph',
        array(
            'labels' => array(
                'name' => 'Author',
                'all_items' => 'All Authors',
                'edit_item' => 'Edit Author',
                'view_item' => 'View Author',
                'update_item' => 'Update Author',
                'add_new_item' => 'Add New Author',
                'new_item_name' => 'New Author',
                'separate_items_with_commas' => 'Please follow this pattern "Thomas Abbt"',
                'choose_from_most_used' => null
            ),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        )
    );
}

/* Register category Course */
function sv_create_taxonomy_course() {
    register_taxonomy(
        'Course',
        'graph',
        array(
            'labels' => array(
                'name' => 'Course',
                'add_or_remove_items' => null,
                'all_items' => 'All Courses',
                'edit_item' => 'Edit Course',
                'view_item' => 'View Course',
                'update_item' => 'Update Course',
                'add_new_item' => 'Add New Course',
                'new_item_name' => 'New Course',
                'choose_from_most_used' => null
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        )
    );
}

/* Register category project */
function sv_create_taxonomy_project() {
    register_taxonomy(
        'Project',
        'graph',
        array(
            'labels' => array(
                'name' => 'Project',
                'add_or_remove_items' => null,
                'all_items' => 'All Projects',
                'edit_item' => 'Edit Project',
                'view_item' => 'View Project',
                'update_item' => 'Update Project',
                'add_new_item' => 'Add New Project',
                'new_item_name' => 'New Project',
                'choose_from_most_used' => null
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        )
    );
}

/* Create all meta boxes */
function sv_graph_meta() {
    add_meta_box('sv_graph_meta_description', 'Description', 'sv_description_callback', 'graph', 'normal', 'low');
    add_meta_box('sv_graph_meta_contributor', 'Contributor', 'sv_contributor_callback', 'graph', 'normal', 'low');
    add_meta_box('sv_custom_attachment','Files Upload','sv_upload_file_callback','graph','normal','low');
}

/* Callback function for meta box discription */
function sv_description_callback() {
  global $post;
  $custom = get_post_custom($post->ID);
  $description = $custom["description"][0];
  ?>
  <input type = "text" style="width:100%" name="description" value="<?php if (isset ( $custom['description'] )) echo esc_attr($description); ?>">
  <?php
}

/* Callback function for meta box contributor */
function sv_contributor_callback() {
  global $post;
  $custom = get_post_custom($post->ID);
  $contributor = $custom["contributor"][0];
  ?>
  <input type = "text" style="width:100%" name="contributor" value="<?php if (isset ( $custom['contributor'] )) echo esc_attr($contributor); ?>">
  <?php
}

/* Callback function for meta box files upload */
function sv_upload_file_callback() { 
    wp_nonce_field(plugin_basename(__FILE__), 'sv_custom_attachment_nonce');
    ?> 
    <p>
    Upload nodes.csv here   <input type="file" id="sv_nodes_attachment" name="sv_nodes_attachment" value="" size="25" accept=".csv"/>
    </p>
    <p>
    Upload edges.csv here   <input type="file" id="sv_edges_attachment" name="sv_edges_attachment" value="" size="25" accept=".csv"/>
    </p>
    <p>
    Upload data.json here   <input type="file" id="sv_data_attachment" name="sv_data_attachment" value="" size="25" accept=".json" />
    </p>
    <p>
    Upload config.json here <input type="file" id="sv_config_attachment" name="sv_config_attachment" value="" size="25" accept=".json"/>  
    </p>    
    <?php
} 

/* Edit colunms of graph post type */
function sv_graph_edit_columns($columns){
  $columns["analysis"] = "Analysis";
  $columns["description"] = "Description";
  $columns["contributor"] = "Contributor";
  $columns["nodes"]   = "nodes.csv";
  $columns["edges"]   = "edges.csv";
  return $columns;
}

/* Edit values shown on colunms of graph post type */
function sv_graph_custom_columns($column){
  global $post;
  $custom = get_post_custom();
  switch ($column) {
  case "analysis":
    the_excerpt();
    break;
  case "description":    
    echo esc_html($custom["description"][0]);
    break;   
  case "contributor":    
    echo esc_html($custom["contributor"][0]);
    break;
  case "nodes":
    $nodes = get_post_meta(get_the_ID(), 'sv_nodes_attachment', true);
    
    if (isset($nodes['url'])){
        echo '<a href="'.esc_url($nodes['url']).'">Uploaded</a>';
    }else{
        echo "No file";
    }
    break;
  case "edges":
    $edges = get_post_meta(get_the_ID(), 'sv_edges_attachment', true);
    
    if (isset($edges['url'])){
        echo '<a href="'.esc_url($edges['url']).'">Uploaded</a>';
    }else{
        echo "No file";
    }
    break;
  }
}


/* Validate and save the uploaded files */
function sv_save_uploaded_files_and_metadata($id) {
     /* --- security verification --- */
    if(!wp_verify_nonce($_POST['sv_custom_attachment_nonce'], plugin_basename(__FILE__))) {
      return $id;
    } 
       
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return $id;
    } 
       
    if('page' == $_POST['post_type']) {
      if(!current_user_can('edit_page', $id)) {
        return $id;
      } 
    } else {
        if(!current_user_can('edit_page', $id)) {
            return $id;
        } 
    } 
    /* - end security verification - */
    
    sv_save_uploaded_file('sv_data_attachment',$id,'application/json');     
    sv_save_uploaded_file('sv_config_attachment',$id,'application/json');  
    sv_save_uploaded_file('sv_nodes_attachment',$id,'text/csv');     
    sv_save_uploaded_file('sv_edges_attachment',$id,'text/csv');     

    $contributor = sanitize_text_field($_POST["contributor"]);
    $description = sanitize_text_field($_POST["description"]);
    update_post_meta($id, "contributor", $contributor);
    update_post_meta($id, "description", $description);
} 

/* Save the uploaded files */
function sv_save_uploaded_file($filename,$id,$type){
    // Make sure the file array isn't empty
    if(!empty($_FILES[$filename]['name'])) {
         
        // Setup the array of supported file types. In this case, it's just PDF.
        $supported_types = array($type);
         
        // Get the file type of the upload
        $arr_file_type = wp_check_filetype(basename($_FILES[$filename]['name']));
        $uploaded_type = $arr_file_type['type'];
         
        // Check if the type is supported. If not, throw an error.
        if(in_array($uploaded_type, $supported_types)) {
 
            // Use the WordPress API to upload the file
            $upload = wp_upload_bits($_FILES[$filename]['name'], null, file_get_contents($_FILES[$filename]['tmp_name']));
     
            if(isset($upload['error']) && $upload['error'] != 0) {
                sv_admin_notice('Error!','There was an error uploading your file. The error is: ' . $upload['error']);
            } else {
                $upload = sanitize_text_field($upload);
                add_post_meta($id, $filename, $upload);
                update_post_meta($id, $filename, $upload);     
            }
        } else {
            sv_admin_notice('Error!','The file type that you\'ve uploaded is not a JSON.');
        } 
         
    } 
}

/* The following function is used to help form support multimedia file upload */
function sv_update_edit_form() {
    echo ' enctype="multipart/form-data"';
} 

/* Output error message in admin panel*/
function sv_admin_notice($header,$content) {
    add_action('admin_notices', 'sv_admin_notice' );
    ?>
    <div class="error">
        <p><?php _e( $header, $content); ?></p>
    </div>
    <?php
}

/* Add JSON as accepted mime types */
function sv_add_custom_mime_types($mimes){
    return array_merge($mimes,array (
      'json' => 'application/json',
      'csv'  => 'text/csv',
    ));
}

/* import sigmajs library */
function sv_javascript_init() {
    wp_enqueue_script( 'sigma.min.js', plugins_url( '/js/sigma.min.js', __FILE__ ));
    wp_enqueue_script( 'sigma.parsers.json.min.js', plugins_url( '/js/sigma.parsers.json.min.js', __FILE__ ));
}
?>
