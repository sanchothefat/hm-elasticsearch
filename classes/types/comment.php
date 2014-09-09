<?php

namespace HMES\Types;

class Comment extends Base {

	var $name             = 'comment';
	var $index_hooks      = array( 'wp_insert_comment', 'edit_comment' );
	var $delete_hooks     = array( 'deleted_comment' );
	var $mappable_hooks   = array(
		'added_comment_meta'   => 'update_comment_meta_callback',
		'updated_comment_meta' => 'update_comment_meta_callback',
		'deleted_comment_meta' => 'update_comment_meta_callback'
	);

	/**
	 * Called when comment meta is added/deleted/updated
	 *
	 * @param $meta_id
	 * @param $user_id
	 */
	function update_comment_meta_callback( $meta_id, $user_id ) {

		$this->index_callback( $user_id );
	}

	/**
	 * Queue the indexing of an item - called when a comment is modified or added to the database
	 *
	 * @param $item
	 * @param array $args
	 */
	function index_callback( $item, $args = array()  ) {

		$comment = (array) get_comment( $item );

		if ( ! $comment ) {
			return;
		}

		$this->queue_action( 'index_item', $item );
	}

	/**
	 * Queue the deletion of an item - called when a comment is deleted from the database
	 *
	 * @param $user_id
	 * @param array $args
	 */
	function delete_callback( $user_id, $args = array()  ) {

		$this->queue_action( 'delete_item', $user_id );
	}

	/**
	 * Parse an item for indexing - accepts comment ID or comment object
	 *
	 * @param $item
	 * @param array $args
	 * @return array|bool
	 */
	function parse_item_for_index( $item, $args = array() ) {

		//get a valid user object as array (populate if only id is supplied)
		if ( is_numeric( $item ) ) {
			$item = (array) get_comment( $item );
			//make sure ID is a parameter, we want a common parameter for the object id across types
			$item['ID'] = $item['comment_ID'];
		} else {
			$item = (array) $item;
			$item['ID'] = $item['comment_ID'];
		}

		if ( empty( $item['ID'] ) ) {
			return false;
		}

		$item['meta'] = get_metadata( 'comment', (int) $item['ID'], '', true );

		foreach ( $item['meta'] as $meta_key => $meta_array ) {
			$item['meta'][$meta_key] = reset( $meta_array );
		}

		return $item;
	}

	/**
	 * Get paginated comments for use by index_all base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return array
	 */
	function get_items( $page, $per_page ) {

		$comments = get_comments( array(
			'offset' => ( $page > 0 ) ? $per_page * ( $page -1 ) : 0,
			'number' => $per_page
		) );

		//make sure ID is a parameter, we want a common parameter for the object id across types
		foreach ( $comments as $key => $comment ) {

			$comments[$key] = (array) $comment;
			$comments[$key]['ID'] = $comments[$key]['comment_ID'];
		}

		return $comments;
	}

}