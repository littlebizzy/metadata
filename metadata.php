<?php
/*
Plugin Name: Metadata
Plugin URI: https://www.littlebizzy.com/plugins/metadata
Description: For custom titles and descriptions
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
			$site_name          = get_bloginfo( 'name' );

			wp_nonce_field( 'metadata_save_meta_box', 'metadata_nonce' );

			// Meta Title Input Field with Site Name Overlay
			echo '<label for="metadata_custom_title">Meta Title</label>';
			echo '<div style="position: relative; display: flex; align-items: center; max-width: 100%;">';
			echo '<input type="text" id="metadata_custom_title" name="metadata_custom_title" value="' . esc_attr( $custom_title ) . '" class="widefat" style="padding-right: 180px;" />';
			echo '<span id="site_name_overlay" style="position: absolute; right: 10px; color: #999; pointer-events: none; user-select: none;"> - ' . esc_html( $site_name ) . '</span>';
			echo '</div>';
			echo '<p id="metadata_title_counter" class="description">Optimal range: 45-60 characters (including " - ' . esc_html( $site_name ) . '").</p>';

			// Meta Description Field
			echo '<label for="metadata_custom_description">Meta Description</label>';
			echo '<textarea id="metadata_custom_description" name="metadata_custom_description" class="widefat" rows="4">' . esc_textarea( $custom_description ) . '</textarea>';
			echo '<p id="metadata_description_counter" class="description">Optimal range: 145-160 characters.</p>';

			// Inline JavaScript for counter updates
			echo '<script>
				document.addEventListener("DOMContentLoaded", function() {
					const titleInput = document.getElementById("metadata_custom_title");
					const descriptionInput = document.getElementById("metadata_custom_description");
					const titleCounter = document.getElementById("metadata_title_counter");
					const descriptionCounter = document.getElementById("metadata_description_counter");
					const siteName = " - ' . esc_js( $site_name ) . '";
					const siteNameLength = siteName.length;

					// Function to update character counter and colors
					function updateCounter(baseValue, counter, optimalMin, optimalMax, appendLength = 0, descriptionLogic = false) {
						const totalLength = baseValue.length + appendLength;
						let message = `${totalLength} characters`;
						let color = "";

						if (descriptionLogic) {
							// Meta description logic
							if (totalLength < 80) {
								color = "red"; // Too short
							} else if (totalLength >= 80 && totalLength < 145) {
								color = "orange"; // Below optimal
							} else if (totalLength >= 145 && totalLength <= 160) {
								color = "green"; // Optimal range
							} else if (totalLength > 160) {
								color = "orange"; // Above optimal
							}
						} else {
							// Meta title logic
							if (baseValue.length < 5) {
								color = "red"; // Too short (excluding site name)
							} else if (baseValue.length >= 5 && baseValue.length < 20) {
								color = "orange"; // Below optimal (excluding site name)
							} else if (totalLength >= 45 && totalLength <= 60) {
								color = "green"; // Optimal range (including site name)
							} else if (totalLength > 60) {
								color = "red"; // Too long (including site name)
							}
						}

						counter.style.color = color;
						counter.textContent = `${message} (Optimal: ${optimalMin}-${optimalMax} characters)`;
					}

					// Function to dynamically update title counter
					function updateTitle() {
						const baseValue = titleInput.value.trim();
						updateCounter(baseValue, titleCounter, 45, 60, siteNameLength);
					}

					// Function to dynamically update description counter
					function updateDescription() {
						const baseValue = descriptionInput.value.trim();
						updateCounter(baseValue, descriptionCounter, 145, 160, 0, true);
					}

					// Initial updates
					updateTitle();
					updateDescription();

					// Event listeners
					titleInput.addEventListener("input", updateTitle);
					descriptionInput.addEventListener("input", updateDescription);
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
		$custom_title = sanitize_text_field( $_POST['metadata_custom_title'] );
		update_post_meta( $post_id, '_metadata_custom_title', $custom_title );
	}

	if ( isset( $_POST['metadata_custom_description'] ) ) {
		update_post_meta( $post_id, '_metadata_custom_description', sanitize_textarea_field( $_POST['metadata_custom_description'] ) );
	}
});

// modify the title output and append the site name
add_filter( 'pre_get_document_title', function( $title ) {
	if ( is_singular() ) {
		global $post;

		$custom_title = get_post_meta( $post->ID, '_metadata_custom_title', true );
		$site_name    = get_bloginfo( 'name' );

		if ( $custom_title ) {
			return $custom_title . ' - ' . $site_name;
		}
	}

	return $title . ' - ' . get_bloginfo( 'name' );
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
