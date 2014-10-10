<?php

class GO_Popular_Terms_Widget extends WP_Widget
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$widget_ops = array(
			'classname'   => 'widget-go-popular-terms',
			'description' => __( 'List of popular terms' ),
		);

		parent::__construct( 'go-popular-terms', __( 'GO Popular Terms' ), $widget_ops );
	} // END __construct

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance )
	{
		$title     = isset( $instance['title'] ) ? trim( $instance['title'] ) : '';
		$taxonomy  = isset( $instance['taxonomy'] ) ? $instance['taxonomy'] : array();
		$num       = isset( $instance['num'] ) ? trim( $instance['num'] ) : 10;
		$term_from = isset( $instance['term_from'] ) ? trim( $instance['term_from'] ) : '-3 days';
		$term_to   = isset( $instance['term_to'] ) ? trim( $instance['term_to'] ) : 'today';

		include __DIR__ . '/templates/form-terms.php';
	}//end form

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $unused_old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $unused_old_instance )
	{
		global $wp_taxonomies;

		$instance = array();

		$instance['title']     = preg_replace( '/[^a-zA-Z0-9_\- ]/', '', $new_instance['title'] );
		$instance['num']       = preg_replace( '/[^0-9]/', '', $new_instance['num'] );
		$instance['term_from'] = preg_replace( '/[^a-zA-Z0-9\- :]/', '', $new_instance['term_from'] );
		$instance['term_to']   = preg_replace( '/[^a-zA-Z0-9\- :]/', '', $new_instance['term_to'] );
		$taxonomies            = array_keys( $wp_taxonomies );

		foreach ( $taxonomies as $taxonomy )
		{
			if ( isset( $new_instance[ 'taxonomy-' . $taxonomy ] ) )
			{
				$instance['taxonomy'][] = $taxonomy;
			} // END if
		} // END foreach

		return $instance;
	}//end update

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance )
	{
		$title     = isset( $instance['title'] ) ? $instance['title'] : $taxonomy->label;
		$num       = isset( $instance['num'] ) ? trim( $instance['num'] ) : 10;
		$taxonomy  = ( isset( $instance['taxonomy'] ) && is_array( $instance['taxonomy'] ) ) ? $instance['taxonomy'] : array( 'post_tag' );
		$term_from = isset( $instance['term_from'] ) ? trim( $instance['term_from'] ) : '-3 days';
		$term_to   = isset( $instance['term_to'] ) ? trim( $instance['term_to'] ) : 'today';

		$term_args = array(
			'count' => $num,
			'from'  => $term_from,
			'to'    => $term_to,
		);

		// support injecting the taxonomy name into the title
		if ( FALSE !== strpos( $title, '_TAXONOMY_' ) )
		{
			$queried = get_queried_object();
			$title = str_replace( '_TAXONOMY_', $queried->name, $title );

			$term_args['tax_query'][] = array(
				'taxonomy' => $queried->taxonomy,
				'field' => 'term_id',
				'terms' => array(
					$queried->term_id,
				),
			);
		}//end if

		$terms = go_popular()->get_popular_terms( $taxonomy, $term_args );

		echo $args['before_widget'];
		include __DIR__ . '/templates/widget-terms.php';
		echo $args['after_widget'];
	}//end widget
}// END GO_Popular_Terms_Widget
