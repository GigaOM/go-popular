<?php

class GO_Popular_Widget extends WP_Widget
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$widget_ops = array(
			'classname'   => 'widget-go-popular',
			'description' => 'Popular stuff by taxonomy',
		);

		parent::__construct( 'go-popular', 'GO Popular', $widget_ops );
	}//end __construct

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance )
	{
		$title        = isset( $instance['title'] ) ? trim( $instance['title'] ) : '';
		$taxonomy     = isset( $instance['taxonomy'] ) ? trim( $instance['taxonomy'] ) : '';
		$num          = isset( $instance['num'] ) ? trim( $instance['num'] ) : 3;
		$num_news     = isset( $instance['num_news'] ) ? trim( $instance['num_news'] ) : 3;
		$num_research = isset( $instance['num_research'] ) ? trim( $instance['num_research'] ) : 1;
		$term_from    = isset( $instance['term_from'] ) ? trim( $instance['term_from'] ) : '-3 days';
		$term_to      = isset( $instance['term_to'] ) ? trim( $instance['term_to'] ) : 'today';
		$article_from = isset( $instance['article_from'] ) ? trim( $instance['article_from'] ) : '-7 days';
		$article_to   = isset( $instance['article_to'] ) ? trim( $instance['article_to'] ) : 'today';

		include __DIR__ . '/templates/form.php';
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

		$instance['title']        = preg_replace( '/[^a-zA-Z0-9_\- ]/', '', $new_instance['title'] );
		$instance['num']          = preg_replace( '/[^0-9]/', '', $new_instance['num'] );
		$instance['num_news']     = preg_replace( '/[^0-9]/', '', $new_instance['num_news'] );
		$instance['num_research'] = preg_replace( '/[^0-9]/', '', $new_instance['num_research'] );
		$instance['term_from']    = preg_replace( '/[^a-zA-Z0-9\- :]/', '', $new_instance['term_from'] );
		$instance['term_to']      = preg_replace( '/[^a-zA-Z0-9\- :]/', '', $new_instance['term_to'] );
		$instance['article_from'] = preg_replace( '/[^a-zA-Z0-9\- :]/', '', $new_instance['article_from'] );
		$instance['article_to']   = preg_replace( '/[^a-zA-Z0-9\- :]/', '', $new_instance['article_to'] );

		if ( $new_instance['taxonomy'] && isset( $wp_taxonomies[ $new_instance['taxonomy'] ] ) )
		{
			$instance['taxonomy'] = $new_instance['taxonomy'];
		}//end if

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
		global $wp_taxonomies;

		if ( ! isset( $wp_taxonomies[ $instance['taxonomy'] ] ) )
		{
			return;
		}//end if

		$taxonomy = $wp_taxonomies[ $instance['taxonomy'] ];

		$title = isset( $instance['title'] ) ? $instance['title'] : $taxonomy->label;
		$num          = isset( $instance['num'] ) ? trim( $instance['num'] ) : 3;
		$num_news     = isset( $instance['num_news'] ) ? trim( $instance['num_news'] ) : 3;
		$num_research = isset( $instance['num_research'] ) ? trim( $instance['num_research'] ) : 1;
		$term_from    = isset( $instance['term_from'] ) ? trim( $instance['term_from'] ) : '-3 days';
		$term_to      = isset( $instance['term_to'] ) ? trim( $instance['term_to'] ) : 'today';
		$article_from = isset( $instance['article_from'] ) ? trim( $instance['article_from'] ) : '-7 days';
		$article_to   = isset( $instance['article_to'] ) ? trim( $instance['article_to'] ) : 'today';

		$term_args = array(
			'count' => $num,
			'from' => $term_from,
			'to' => $term_to,
		);

		$terms = go_popular()->get_popular_terms( $taxonomy->name, $term_args );

		foreach ( $terms as $index => $term )
		{
			$args = array(
				'count' => $num_news,
				'from' => $article_from,
				'to' => $article_to,
			);

			$terms[ $index ]->popular_posts         = array();
			$terms[ $index ]->popular_posts['news'] = go_popular()->get_popular_posts_by_term( 'news', $taxonomy->name, $term->slug, $args );

			$args['count'] = $num_research;
			$terms[ $index ]->popular_posts['research'] = go_popular()->get_popular_posts_by_term( 'research', $taxonomy->name, $term->slug, $args );
		}// end foreach

		echo $args['before_widget'];
		include __DIR__ . '/templates/widget.php';
		echo $args['after_widget'];
	}//end widget
}//end class
