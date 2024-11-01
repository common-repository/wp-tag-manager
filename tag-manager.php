<?php
/*
Plugin Name: WP Tag Manager
Plugin URI: http://jameslao.com/2007/11/03/wp-tag-manager-011/
Description: An advanced tag manager that seamlessly integrates into WordPress.
Version: 0.1.1
Author: James Lao
Author URI: http://jameslao.com/
*/

function jtm_page() {
	switch( $_GET['action'] ) {
		case 'edit':
			jtm_edit_page();
			break;
		case 'delete':
			wp_delete_term($_GET['tag_id'], 'post_tag');
			echo '<div id="message" class="updated fade"><p>' . __('Tag deleted') . '.</p></div>';
			jtm_manage_page();
			break;
		default:
			jtm_manage_page();
			break;
	}
}

// Default page. Shows list of tags.
function jtm_manage_page() {
	if( $_POST['edit_tag'] ) {
		$args['name'] = $_POST['tag_name'];
		$args['slug'] = $_POST['tag_slug'];
		$return = wp_update_term($_POST['tag_id'], 'post_tag', $args);
		
		if( $_POST['tag_merge']) {
			// Gather post IDs of tags.
			$post_ids = get_objects_in_term($_POST['tag_merge'], 'post_tag');
			
			// Delete tags to be merged.
			foreach( $_POST['tag_merge'] as $tag_to_merge ) {
				wp_delete_term($tag_to_merge, 'post_tag');
			}
			
			// Associate posts with tag.
			foreach( $post_ids as $post_id ) {
				wp_set_object_terms($post_id, $_POST['tag_slug'], 'post_tag', TRUE);
			}
		}
		
		if( is_wp_error($return) ) {
			echo '<div id="message" class="updated fade"><p>' . $return->get_error_message() . '</p></div>';
		} else {
			echo '<div id="message" class="updated fade"><p>' . __('Tag updated') . '.</p></div>';
		}
	}
	
	$tags = array_chunk(get_tags('orderby=name'), 15);
	$jtm_root = get_bloginfo('wpurl').'/wp-admin/edit.php?page=jl-tag-manager';
	if( !is_numeric($_GET['tag_page']) ) $_GET['tag_page'] = 1;
	$_GET['tag_page']--;
	
	?>

<div class="wrap">
	<h2><?php _e('Tags'); ?></h2>
	
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" style="text-align: center;"><?php _e('ID'); ?></th>
				<th scope="col"><?php _e('Name'); ?></th>
				<th scope="col" style="text-align: center;"><?php _e('Posts'); ?></th>
				<th scope="col" colspan="2" style="text-align: center;"><?php _e('Action'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$alt = 0;
				foreach($tags[$_GET['tag_page']] as $tag) :
			?>
			<tr<?php if( $alt++%2==0 ) echo ' class="alternate"'; ?>>
				<th scope="row" style="text-align: center;"><?php echo $tag->term_id; ?></td>
				<td><?php echo $tag->name; ?></td>
				<td style="text-align: center;"><?php echo $tag->count; ?></td>
				<td><a class="edit" href="<?php echo $jtm_root.'&action=edit&tag_id='.$tag->term_id; ?>"><?php _e('Edit'); ?></a></td>
				<td><a class="delete" href="<?php echo $jtm_root.'&action=delete&tag_id='.$tag->term_id; ?>" onclick="return confirm('Are you sure you want to delete tag &quot;<?php echo $tag->name; ?>&quot;?');"><?php _e('Delete'); ?></a></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="navigation">
	<?php
		if( is_array($tags[$_GET['tag_page']-1]) ) {
			echo "<div class='alignleft'><a href='" . $jtm_root . "&tag_page=" . ($_GET['tag_page']) . "'>&laquo; " . __('Previous Page') . "</a></div>";
		}
		if( is_array($tags[$_GET['tag_page']+1]) ) {
			echo "<div class='alignright'><a href='" . $jtm_root . "&tag_page=" . ($_GET['tag_page']+2) . "'>" . __('Next Page') . " &raquo;</a></div>";
		}
	?>
	</div>
</div>

	<?php
}

function jtm_edit_page() {
	$jtm_root = get_bloginfo('wpurl').'/wp-admin/edit.php?page=jl-tag-manager';
	$tag = get_tag($_GET['tag_id'], OBJECT, 'edit');
	$tags = get_tags('orderby=name');
	?>
	
	<div class="wrap">
		<h2><?php echo _e('Editing Tag') . " &quot;" . $tag->name . "&quot;"; ?></h2>
		
		<form action="<?php echo $jtm_root; ?>" method="post">
			<input type="hidden" name="tag_id" value="<?php echo $tag->term_id; ?>" />
			<table class="editform" width="100%" cellspacing="2" cellpadding="5">
				<tr>
					<th width="33%" scope="row" style="vertical-align: top;"><label for="tag_name"><?php _e('Tag name'); ?>:</label></th>
					<td><input type="text" name="tag_name" id="tag_name" value="<?php echo $tag->name; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="33%" scope="row" style="vertical-align: top;"><label for="tag_slug"><?php _e('Tag slug'); ?>:</label></th>
					<td><input type="text" name="tag_slug" id="tag_slug" value="<?php echo $tag->slug; ?>" size="40" /></td>
				</tr>
				<tr>
					<th width="33%" scope="row" style="vertical-align: top;"><label for="tag_merge"><?php _e('Merge'); ?>:</label></th>
					<td>
						<select name="tag_merge[]" id="tag_merge" multiple="multiple" size="8" style="width: 200px;">
							<?php
								foreach($tags as $opttag) {
									if( $opttag->term_id != $tag->term_id )
										echo "<option value='$opttag->term_id'>$opttag->name</option>\n";
								}
							?>
						</select><br />
						<?php echo __('Delete the selected tags and associate their posts with the tag') . ' &quot;' . $tag->name . '&quot;.'; ?>
					</td>
				</tr>
			</table>
			<p class="submit"><input type="submit" name="edit_tag" value="<?php _e('Edit Tag'); ?> &raquo;" /></p>
		</form>
	</div>
	
	<?php
}

// Sink the pages.
function jtm_sink_pages() {
	add_management_page('Manage Tags', 'Tags', 8, 'jl-tag-manager', 'jtm_page');
}

// Hook into WordPress.
add_action('admin_menu', 'jtm_sink_pages');

?>