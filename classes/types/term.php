<?php

//todo: taxonomies can be modified and deleted (what to do in this case)

namespace HMES\Types;

class Term extends Base {

	var $name             = 'term';
	var $index_hooks      = array();
	var $delete_hooks     = array();
	var $mappable_hooks   = array(
		'create_term'    => 'update_term_callback',
		'edited_terms'   => 'update_term_callback',
		'delete_term'    => 'delete_term_callback'
	);

	/**
	 * Called when a term is added/deleted/updated
	 *
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 */
	function update_term_callback( $term_id, $tt_id, $taxonomy ) {

		$this->index_callback( $tt_id );
	}

	/**
	 * Called when a term is deleted
	 *
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 */
	function delete_term_callback( $term_id, $tt_id, $taxonomy ) {

		$this->delete_callback( $tt_id );
	}

	/**
	 * Queue the indexing of an item - called when a term is modified or added to the database
	 *
	 * @param $tt_id
	 * @param array $args
	 */
	function index_callback( $tt_id, $args = array() ) {

		$this->queue_action( 'index_item', $tt_id, $args );
	}

	/**
	 * Queue the deletion of an item - called when a post is deleted from the database
	 *
	 * @param $tt_id
	 * @param array $args
	 */
	function delete_callback( $tt_id, $args = array() ) {

		$this->queue_action( 'delete_item', $tt_id, $args );
	}

	/**
	 * Parse an item for indexing, accepts term_taxonomy_id ID or post object
	 *
	 * @param $item
	 * @param array $args
	 * @return array|bool
	 */
	function parse_item_for_index( $item, $args = array() ) {

		//get a valid user object as array (populate if only id is supplied)
		//terms are stored on tt_id because terms are stored independent of taxonomies in the database
		if ( is_numeric( $item ) ) {
			$tt_id      = $item;
			$item       = (array) $this->get_term_from_tt_id( $item );
			$item['ID'] = $tt_id;
		} else {
			$item       = (array) $item;
			$item['ID'] = $item['term_taxonomy_id'];
		}

		if ( empty( $item['ID'] ) ) {
			return false;
		}

		$item['taxonomy_data'] = get_taxonomy( $item['taxonomy'] );

		if ( ! $item['taxonomy_data'] ) {
			return false;
		}

		return $item;
	}

	/**
	 * Get a term from a term_taxonomy_id
	 *
	 * @param $tt_id
	 * @return bool|object
	 */
	function get_term_from_tt_id( $tt_id ) {

		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $tt_id ) );

		$result = reset( $results );

		if ( ! $result ) {
			return false;
		}

		$term = get_term( $result->term_id, $result->taxonomy );

		if ( is_wp_error( $term ) ) {
			return false;
		}

		return $term;
	}

	/**
	 * Get paginated term_taxonomy_ids for use by index_all base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return mixed
	 */
	function get_items( $page, $per_page ) {

		global $wpdb;

		$tt_ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy ORDER BY term_taxonomy_id ASC LIMIT %d, %d", ( $page > 0 ) ? $per_page * ( $page -1 ) : 0, ( $page > 0 ) ? $page * $per_page : $per_page ) );

		return $tt_ids;
	}

}