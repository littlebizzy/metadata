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
            // fetch existing meta values
            $metadata_title       = get_post_meta( $post->ID, '_metadata_title', true );
            $metadata_description = get_post_meta( $post->ID, '_metadata_description', true );
            $metadata_site_name   = get_bloginfo( 'name' );
            $post_title           = get_the_title( $post );
            $metadata_excerpt     = wp_strip_all_tags( wp_trim_words( $post->post_content, 160 ) ); // first 160 chars of content

            // determine defaults
            $default_metadata_title       = $metadata_title ?: $post_title;
            $default_metadata_description = $metadata_description ?: $metadata_excerpt;

            // nonce for security
            wp_nonce_field( 'metadata_save_meta_box', 'metadata_nonce' );

            // wrapper for metadata fields
            echo '<div class="metadata-box">';

            // meta title field
            echo '<div class="metadata-title-field" style="margin-bottom: 12px;">';
            echo '<label for="metadata_custom_title">' . esc_html__( 'Meta Title', 'metadata' ) . '</label>';
            echo '<div style="position: relative; max-width: 100%; margin-bottom: 4px;">'; // ensure vertical stacking
            echo '<input type="text" id="metadata_custom_title" name="metadata_custom_title" value="' . esc_attr( $metadata_title ) . '" class="widefat" style="padding-right: 180px;" placeholder="' . esc_attr( $default_metadata_title ) . '" />';
            echo '<span id="site_name_overlay" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #999; pointer-events: none; user-select: none;"> - ' . esc_html( $metadata_site_name ) . '</span>';
            echo '</div>';
            echo '<p id="metadata_title_counter" class="description" style="margin-top: 0;">' . esc_html__( 'Optimal range: 45-60 characters (including " - ', 'metadata' ) . esc_html( $metadata_site_name ) . '").</p>';
            echo '</div>';

            // meta description field
            echo '<div class="metadata-description-field" style="margin-bottom: 0px;">';
            echo '<label for="metadata_custom_description">' . esc_html__( 'Meta Description', 'metadata' ) . '</label>';
            echo '<div style="position: relative; max-width: 100%; margin-bottom: 4px;">'; // align with title field structure
            echo '<textarea id="metadata_custom_description" name="metadata_custom_description" class="widefat" rows="4" style="resize: vertical; padding-right: 0;" placeholder="' . esc_attr( $default_metadata_description ) . '">' . esc_textarea( $metadata_description ) . '</textarea>';
            echo '</div>';
            echo '<p id="metadata_description_counter" class="description" style="margin-top: 0;">' . esc_html__( 'Optimal range: 145-160 characters.', 'metadata' ) . '</p>';
            echo '</div>';

            echo '</div>'; // end wrapper

            // inline JavaScript for counter updates
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    const titleInput = document.getElementById("metadata_custom_title");
                    const descriptionInput = document.getElementById("metadata_custom_description");
                    const titleCounter = document.getElementById("metadata_title_counter");
                    const descriptionCounter = document.getElementById("metadata_description_counter");
                    const siteName = " - ' . esc_js( $metadata_site_name ) . '";
                    const siteNameLength = siteName.length;

                    function getColorBasedOnLength(length, thresholds) {
                        if (length < thresholds.min) return "red";
                        if (length >= thresholds.warning && length < thresholds.optimal.min) return "orange";
                        if (length >= thresholds.optimal.min && length <= thresholds.optimal.max) return "green";
                        return "red";
                    }

                    function updateCounter(inputValue, counterElement, thresholds, appendLength = 0) {
                        const totalLength = inputValue.length + appendLength;
                        const color = getColorBasedOnLength(totalLength, thresholds);
                        counterElement.style.color = color;
                        counterElement.textContent = `${totalLength} characters (Optimal: ${thresholds.optimal.min}-${thresholds.optimal.max})`;
                    }

                    function updateTitleCounter() {
                        if (!titleInput || !titleCounter) return;
                        const thresholds = { min: 5, warning: 20, optimal: { min: 45, max: 60 } };
                        updateCounter(titleInput.value.trim(), titleCounter, thresholds, siteNameLength);
                    }

                    function updateDescriptionCounter() {
                        if (!descriptionInput || !descriptionCounter) return;
                        const thresholds = { min: 80, warning: 145, optimal: { min: 145, max: 160 } };
                        updateCounter(descriptionInput.value.trim(), descriptionCounter, thresholds);
                    }

                    updateTitleCounter();
                    updateDescriptionCounter();

                    if (titleInput) {
                        titleInput.addEventListener("input", updateTitleCounter);
                    }

                    if (descriptionInput) {
                        descriptionInput.addEventListener("input", updateDescriptionCounter);
                    }
                });
            </script>';
        },
        get_post_types( [ 'public' => true ] ), // dynamically include all public post types
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
        $metadata_title = sanitize_text_field( $_POST['metadata_custom_title'] );
        update_post_meta( $post_id, '_metadata_title', $metadata_title );
    }

    if ( isset( $_POST['metadata_custom_description'] ) ) {
        $metadata_description = sanitize_textarea_field( $_POST['metadata_custom_description'] );
        update_post_meta( $post_id, '_metadata_description', $metadata_description );
    }
});

// modify the title output and append the site name
add_filter( 'pre_get_document_title', function( $title ) {
    if ( ! is_singular() ) {
        return $title;
    }

    $post = get_queried_object();

    if ( ! ( $post instanceof WP_Post ) ) {
        return $title;
    }

    $metadata_title = get_post_meta( $post->ID, '_metadata_title', true );
    $default_title  = $metadata_title ?: get_the_title( $post );
    $metadata_site_name = get_bloginfo( 'name' );

    return $default_title . ' - ' . $metadata_site_name;
});

// inject meta description directly after the title
add_action( 'wp_head', function() {
    if ( ! is_singular() ) {
        return;
    }

    $post = get_queried_object();

    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    $metadata_description = get_post_meta( $post->ID, '_metadata_description', true );
    $default_description  = $metadata_description ?: wp_strip_all_tags( wp_trim_words( $post->post_content, 160, '' ) );

    echo '<meta name="description" content="' . esc_attr( $default_description ) . '">' . "\n";
}, 1 ); // priority 1 ensures it's output right after the <title>

// Ref: ChatGPT
