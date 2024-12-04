<?php
/*
Plugin Name: Metadata
Plugin URI: https://www.littlebizzy.com/plugins/metadata
Description: Allows editing the title tag and meta description for posts and pages.
Version: 1.0.0
Author: LittleBizzy
Author URI: https://www.littlebizzy.com
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
GitHub Plugin URI: littlebizzy/metadata
Primary Branch: master
*/

// prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// disable wordpress.org updates for this plugin
add_filter( 'gu_override_dot_org', function( $overrides ) {
	$overrides[] = 'metadata/metadata.php';
	return $overrides;
}, 999 );

// add metadata fields to the post editor
add_action( 'add_meta_boxes', function() {
	add_meta_box(
		'metadata-meta-box',
		'Metadata',
		function( $post ) {
			$custom_title       = get_post_meta( $post->ID, '_metadata_custom_title', true );
			$custom_description = get_post_meta( $post->ID, '_metadata_custom_description', true );

			wp_nonce_field( 'metadata_save_meta_box', 'metadata_nonce' );

			echo '<label for="metadata_custom_title">Custom Title</label>';
			echo '<input type="text" id="metadata_custom_title" name="metadata_custom_title" value="' . esc_attr( $custom_title ) . '" class="widefat" />';
			echo '<p id="metadata_title_counter" class="description">Optimal range: 50-60 characters.</p>';

			echo '<label for="metadata_custom_description">Meta Description</label>';
			echo '<textarea id="metadata_custom_description" name="metadata_custom_description" class="widefat" rows="4">' . esc_textarea( $custom_description ) . '</textarea>';
			echo '<p class="description">Overrides the default meta description.</p>';

			// Add inline JavaScript for the counter
			echo '<script>
				document.addEventListener("DOMContentLoaded", function() {
					const titleInput = document.getElementById("metadata_custom_title");
					const counter = document.getElementById("metadata_title_counter");
					
					function updateCounter() {
						const length = titleInput.value.length;
						let message = `${length} characters`;
						let color = "";
						
						if (length <= 50) {
							color = "green"; // Ideal length
						} else if (length > 50 && length <= 60) {
							color = "yellow"; // Approaching limit
						} else {
							color = "red"; // Exceeds limit
						}
						
						counter.style.color = color;
						counter.textContent = `${message} (Optimal: 50-60)`;
					}

					// Update on load and on input
					updateCounter();
					titleInput.addEventListener("input", updateCounter);
				});
			</script>';
		},
		[ 'post', 'page' ],
		'normal',
		'high'
	);
});

// save the metadata fields
add_action( 'save_post', function( $post_id ) {
	if ( ! isset( $_POST['metadata_nonce'] ) || ! wp_verify_nonce( $_POST['metadata_nonce'], 'metadata_save_meta_box' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['metadata_custom_title'] ) ) {
		update_post_meta( $post_id, '_metadata_custom_title', sanitize_text_field( $_POST['metadata_custom_title'] ) );
	}

	if ( isset( $_POST['metadata_custom_description'] ) ) {
		update_post_meta( $post_id, '_metadata_custom_description', sanitize_textarea_field( $_POST['metadata_custom_description'] ) );
	}
});

// modify the title output
add_filter( 'pre_get_document_title', function( $title ) {
	if ( is_singular() ) {
		global $post;

		$custom_title = get_post_meta( $post->ID, '_metadata_custom_title', true );

		if ( $custom_title ) {
			return $custom_title;
		}
	}

	return $title;
});

// inject meta description directly after the title
add_action( 'wp_head', function() {
	if ( is_singular() ) {
		global $post;

		$custom_description = get_post_meta( $post->ID, '_metadata_custom_description', true );

		// Output description directly after the <title> tag
		if ( $custom_description ) {
			echo '<meta name="description" content="' . esc_attr( $custom_description ) . '">' . "\n";
		}
	}
}, 1 ); // priority 1 ensures it's output right after the <title>

// Ref: ChatGPT
