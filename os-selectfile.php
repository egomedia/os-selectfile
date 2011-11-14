<?php
/*
Plugin Name: OS Select File
Description: Allows admin to select a single file from all those attached to post.
Version: 0.3
Author: Oli Salisbury
*/

//hooks
if (WP_ADMIN) {
	add_action('admin_menu', 'os_selectfile_init');
	add_action('save_post',  'os_selectfile_save');
	add_action('admin_head', 'os_selectfile_init_js');
}

//save
function os_selectfile_save($post_id) {
	//Check permissions
	if ('page' == $_POST['post_type']) {
		if (!current_user_can('edit_page', $post_id)) { return $post_id; }
	} else {
		if (!current_user_can('edit_post', $post_id)) { return $post_id; }
	}
	//verify nonce
	if (!wp_verify_nonce($_POST['os_selectfile_nonce'], basename(__FILE__))) {
			return $post_id;
	}
	//check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
	}
	//GREEN LIGHT
	//localise posted values
	$os_selectfile_file = $_POST['os_selectfile_file'];
	//add the value to database if it is not there
	add_post_meta($post_id, 'os_selectfile_file', $os_selectfile_file, true)
	or
	//update it if the value is already there
	update_post_meta($post_id, 'os_selectfile_file', $os_selectfile_file);
	//if empty value then delete the meta
	if (!$os_selectfile_file) { delete_post_meta($post_id, 'os_selectfile_file'); }
}

//Inserts HTML for meta box, including all existing attachments
function os_selectfile_meta_box_html() { 
	global $post;
  echo '<input type="hidden" name="os_selectfile_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />'; // Use nonce for verification
	echo '<p id="os-selectfile-ajax">';
	echo '<label for="os_selectfile_file"></label>';
	echo '<select name="os_selectfile_file" id="os_selectfile_file">';
	echo '<option value="">-</option>';
	$val = get_post_meta($post->ID, 'os_selectfile_file', true);
	$q = get_posts('post_type=attachment&post_mime_type=application&posts_per_page=-1&orderby=title&order=ASC&post_parent='.$post->ID);
	foreach ($q as $obj) {
		echo '<option value="'.$obj->ID.'"';
		echo $obj->ID == $val ? 'selected="selected"' : '';
		echo '>';
		echo $obj->post_title;
		//echo ' ('.str_replace("application/", "", $obj->post_mime_type).')';
		echo '</option>';
	}
	echo '</select>';
	echo '</p>';
	echo '<p>';
	echo '<a href="media-upload.php?post_id='.$post->ID.'&TB_iframe=1" class="button button-highlighted thickbox">Upload File</a>';
	echo ' <a href="#" id="os-selectfile-ajaxclick" class="button">Refresh</a>';
	echo ' <span id="os-selectfile-loading" style="display:none;"><img src="images/loading.gif" alt="Loading..." style="vertical-align:middle" /></span>';
	echo '</p>';
}

//Creates meta box on all Posts and Pages
function os_selectfile_add_meta_box() {
	//for general
	add_meta_box('os_selectfile_meta_box', 'Select File', 'os_selectfile_meta_box_html', '', 'side');
}

//javascript in header
function os_selectfile_init_js() {
	echo '
	<script type="text/javascript" charset="utf-8">
	jQuery(document).ready(function(){
		//ajax update
		jQuery("#os-selectfile-ajaxclick").click(function() {
			jQuery("#os-selectfile-ajax").slideUp("fast", function() { jQuery("#os-selectfile-loading").show(); }).load("'.$_SERVER['REQUEST_URI'].' #os-selectfile-ajax", function() { 
				jQuery(this).slideDown("fast");
				jQuery("#os-selectfile-loading").hide();
			});
			return false;
		});
	});
	</script>';
}

//javascript in footer
function os_selectfile_init_footer() {
}

//initialise plugin
function os_selectfile_init() {
	wp_enqueue_script('jquery');
	os_selectfile_add_meta_box();
}
?>