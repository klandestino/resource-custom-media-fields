<?php
/*
Plugin Name: Resource Custom Media Fields
Plugin URI: https://github.com/klandestino/resource-custom-media-fields
Description: Create attachments custom fields. Made with a lot of inspiration from Guillaume Voisin http://wp.tutsplus.com/author/guillaumevoisin
Version: 0.1
Author: Tom Bergman
License: GPL2
*/

class Resource_Custom_Media_Fields {

	private $media_fields = array();

	function __construct( $fields ) {
		$this->media_fields = $fields;

		add_filter( 'attachment_fields_to_edit', array( $this, 'rcmf_edit_fields' ), 11, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'rcmf_save_fields' ), 11, 2 );

		add_action( 'resourcespace_import_complete', array( $this, 'rcmf_update_photographer' ), 11, 2 );
	}

	/**
	 * Show custom fields in attachment view
	 */
	public function rcmf_edit_fields( $form_fields, $post = null ) {
		// If our fields array is not empty
		if ( ! empty( $this->media_fields ) ) {
			//var_dump('expression');

			// We browse our set of options
			foreach ( $this->media_fields as $field => $values ) {
				// If the field matches the current attachment mime type
				// and is not one of the exclusions
				if ( preg_match( "/" . $values['application'] . "/", $post->post_mime_type ) && ! in_array( $post->post_mime_type, $values['exclusions'] ) ) {
					// We get the already saved field meta value
					$meta = get_post_meta( $post->ID, '_' . $field, true );

					// Define the input type to 'text' by default
					$values['input'] = 'text';

					// And set it to the field before building it
					$values['value'] = $meta;

					// We add our field into the $form_fields array
					$form_fields[$field] = $values;
				}
			}
		}

		// We return the completed $form_fields array
		return $form_fields;
	}

	/**
	 * Save custom fields
	 */
	function rcmf_save_fields( $post, $attachment ) {
		// If our fields array is not empty
		if ( ! empty( $this->media_fields ) ) {
			// Browser those fields
			foreach ( $this->media_fields as $field => $values ) {
				// If this field has been submitted (is present in the $attachment variable)

				if ( isset( $attachment[$field] ) ) {
					// If submitted field is empty
					// We add errors to the post object with the "error_text" parameter we set in the options
					if ( strlen( trim( $attachment[$field] ) ) == 0 )
						$post['errors'][$field]['errors'][] = __( $values['error_text'] );
					// Otherwise we update the custom field
					else
						update_post_meta( $post['ID'], '_' . $field, $attachment[$field] );
				}
				// Otherwise, we delete it if it already existed
				else {
					delete_post_meta( $post['ID'], $field );
				}
			}
		}

		return $post;
	}

	/**
	 * Set photographer post meta. Use action hook in Resourcespace-explorer
	 */
	function rcmf_update_photographer( $attachment_id, $data ) {
		if ( '' !== $data->field10 ) {
			update_post_meta( $attachment_id, '_image_photographer', $data->field10 );
		}
	}

}

// Our custom fields to add. If more fields needs to be added, maybe move this to an own file.
$attachment_options = array(
	'image_photographer' => array(
		'label'       => __( 'Photographer', 'resource-custom-media-fields' ),
		'input'       => 'text',
		'application' => 'image',
		'exclusions'  => array(),
		'required'    => false
	)
);

$rcmf = new Resource_Custom_Media_Fields( $attachment_options );
