<?php

class GO_Popular_Trending_Posts_Widget extends WP_Widget
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$widget_ops = array(
			'classname'   => 'widget-go-popular-trending hide',
			'description' => 'Trending posts',
		);

		parent::__construct( 'go-popular-trending-posts', 'GO Trending Posts', $widget_ops );
	}//end __construct

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
		wp_localize_script(
			'go-popular-trending-posts',
			'go_popular_trending_posts',
			array(
				'endpoint' => home_url( 'go-popular-trending-posts/' . mktime( date( 'H' ), date( 'i' ), 0 ) . '/' ),
				'chartbeat_api_key' => go_popular()->config( 'chartbeat_api_key' ),
			)
		);
		wp_enqueue_script( 'go-popular-trending-posts' );
		wp_enqueue_style( 'go-popular-trending-posts' );

		echo $args['before_widget'];
		include __DIR__ . '/templates/trending-posts.php';
		echo $args['after_widget'];
	}//end widget
}//end class
