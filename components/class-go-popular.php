<?php

class GO_Popular
{
	private $config = NULL;

	/**
	 * constructor, of course
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		add_action( 'wp_ajax_go_popular_trending_posts', array( $this, 'trending_posts_ajax' ) );
		add_action( 'wp_ajax_nopriv_go_popular_trending_posts', array( $this, 'trending_posts_ajax' ) );
	}//end __construct

	/**
	 * Hooked to the init action
	 */
	public function init()
	{
		if ( function_exists( 'go_ui' ) )
		{
			go_ui();
		}//end if

		$script_config = apply_filters( 'go_config', array( 'version' => 1 ), 'go-script-version' );
		$js_min = ( defined( 'GO_DEV' ) && GO_DEV ) ? 'lib' : 'min';

		wp_register_script(
			'go-popular-trending-posts',
			plugins_url( "/js/{$js_min}/go-popular-trending-posts.js", __FILE__ ),
			array(
				'jquery',
				'handlebars',
			),
			$script_config['version'],
			TRUE
		);

		wp_register_style(
			'go-popular-trending-posts',
			plugins_url( '/css/go-popular-trending-posts.css', __FILE__ ),
			array(),
			$script_config['version']
		);
	}//end init

	/**
	 * Hooks into the widgets_init action to initialize plugin widgets
	 */
	public function widgets_init()
	{
		require_once __DIR__ . '/class-go-popular-widget.php';
		register_widget( 'GO_Popular_Widget' );

		require_once __DIR__ . '/class-go-popular-terms-widget.php';
		register_widget( 'GO_Popular_Terms_Widget' );

		require_once __DIR__ . '/class-go-popular-trending-posts-widget.php';
		register_widget( 'GO_Popular_Trending_Posts_Widget' );
	}//end widgets_init

	/**
	 * returns our current configuration, or a value in the configuration.
	 *
	 * @param string $key (optional) key to a configuration value
	 * @return mixed Returns the config array, or a config value if
	 *  $key is not NULL
	 */
	public function config( $key = NULL )
	{
		if ( empty( $this->config ) )
		{
			$this->config = apply_filters(
				'go_config',
				array(),
				'go-popular'
			);
		}//END if

		if ( ! empty( $key ) )
		{
			return isset( $this->config[ $key ] ) ? $this->config[ $key ] : NULL ;
		}

		return $this->config;
	}//END config

	/**
	 * Hooked to the trending_posts_ajax action
	 */
	public function trending_posts_ajax()
	{
		header( 'Content-type: application/json' );

		if ( $massaged_data = wp_cache_get( 'go-popular-trending-posts' ) )
		{
			wp_send_json_success( $massaged_data );
			die;
		}//end if

		$args = array(
			'apikey' => $this->config( 'chartbeat_api_key' ),
			'host' => $this->config( 'chartbeat_host' ),
		);

		$url = 'http://api.chartbeat.com/live/toppages/v3/';
		$url = add_query_arg( $args, $url );

		// fetch content from chartbeat
		$response = wp_remote_get( $url );

		// if the wp_remote_get failed, return a json error
		if ( is_wp_error( $response ) )
		{
			wp_send_json_error();
			die;
		}//end if

		// parse the data
		$data = json_decode( $response['body'] );

		$massaged_data = array();

		// start the first post at rank 1
		$rank = 1;

		foreach ( $data->pages as $item )
		{
			if ( 'gigaom.com/' === $item->path )
			{
				continue;
			}//end if

			// formula for a trend line
			// m = ( a - b ) / ( c - d )
			// where:
			// a = n times ( all x-values multiplied by their corresponding y-values )
			// b = the sum of all x-values times the sum of all y-values
			// c = n times the sum of all squared x-values
			// d = the squared sum of all x-values

			$x = 1;
			$x_y_multiply = 0;
			$sum_x = 0;
			$sum_y = 0;
			$sum_squared_x = 0;
			foreach ( $item->stats->visit->hist as $hist )
			{
				$x_y_multiply += $x * $hist;

				$sum_x += $x;
				$sum_y += $hist;

				$sum_squared_x += ( $x * $x );

				$x++;
			}//end foreach

			$num = count( $item->stats->visit->hist );
			$calc_a = $x_y_multiply * $num;
			$calc_b = $sum_x * $sum_y;
			$calc_c = $num * $sum_squared_x;
			$calc_d = $sum_x * $sum_x;

			$trend = ( $calc_a - $calc_b ) / ( $calc_c - $calc_d );
			if ( $trend < 0 )
			{
				$trend_direction = 'down';
			}//end if
			elseif ( $trend >= 0 && $trend <= 0.5 )
			{
				$trend_direction = 'dash';
			}//end elseif
			elseif ( $trend > 0.5 )
			{
				$trend_direction = 'up';
			}//end elseif

			// get the path of the post
			$path = str_replace( 'gigaom.com/', '/', $item->path );

			// build the post
			$post_data = array(
				'url' => 'https://gigaom.com/' . $path,
				'title' => preg_replace( '/ \| Gigaom$/', '', $item->title ),
				'rank' => $rank,
				'trend' => $trend,
				'trend_direction' => $trend_direction,
				'thumbnail' => esc_url( get_template_directory_uri() . '/img/logo-iphone.gigaom.png' ),
			);

			// attempt to fetch the post
			$post = get_page_by_path( $path, OBJECT, 'post' );

			// if we can find a post by path and it has a thumbnail, use that instead
			if ( $post && ! empty( $post->ID ) )
			{
				if ( has_post_thumbnail( $post->ID ) )
				{
					$thumbnail = get_the_post_thumbnail( $post->ID, 'small-square-thumbnail' );

					preg_match( '!src="([^"]+)"!', $thumbnail, $matches );

					$thumbnail = $matches[1];

					// let's make sure old images are pointing at the correct location
					$thumbnail = preg_replace( '!src="(https?)://pro\.!', 'src="$1://research.', $thumbnail );

					// point at the correct uploads directory
					$thumbnail = preg_replace( '!src="(https?)://research\.gigaom\.com/files/!', 'src="$1://research.gigaom.com/wp-content/uploads/', $thumbnail );

					$post_data['thumbnail'] = $thumbnail;
				}//end if
			}//end if

			$massaged_data[] = $post_data;
			$rank++;
		}//end foreach

		// cache the data
		wp_cache_set( 'go-popular-trending-posts', $massaged_data );

		wp_send_json_success( $massaged_data );
		die;
	}//end trending_posts_ajax

	/**
	 * Get the popular term
	 */
	public function get_popular_terms( $taxonomy, $args = array() )
	{
		$taxonomy = ( ! is_array( $taxonomy ) ) ? array( $taxonomy ) : $taxonomy;

		$default = array(
			'count' => 3,
			'from' => '-3 days',
			'to' => null,
		);

		$args = wp_parse_args( $args, $default );

		$count = $args['count'];

		$args = array_merge( $args, array(
			'post_status'     => 'publish',
			'posts_per_page'  => 1500,
			'orderby'         => 'date',
			'order'           => 'DESC',
			'date_query'      => array(
				'after'   => $args['from'],
			),
		) );

		if ( ! empty( $args['to'] ) )
		{
			$args['date_query']['before'] = $args['to'];
			$args['date_query']['inclusive'] = TRUE;
		}//end if

		$cache_key = md5( serialize( array( $args, $taxonomy ) ) );

		if ( $popular_terms = wp_cache_get( $cache_key, 'go-popular' ) )
		{
			return $popular_terms;
		}//end if

		unset( $args['count'], $args['from'], $args['to'] );

		$popular_terms = array();

		$the_query = new WP_Query( $args );
		while ( $the_query->have_posts() )
		{
			$the_query->next_post();

			$terms = wp_get_object_terms( $the_query->post->ID, $taxonomy );

			if ( is_array( $terms ) )
			{
				$comment_count = $the_query->post->comment_count ? $the_query->post->comment_count : get_post_meta( $the_query->post->ID, 'go_xpost_comment_count', true );
				foreach ( $terms as $term )
				{
					if ( ! isset( $popular_terms[ $term->slug ] ) )
					{
						// give each term one point just for showing up
						$term->popularity = 1;
						$popular_terms[ $term->slug ] = $term;
					}// end if
					else
					{
						// give 0.2 points for each additional article
						$popular_terms[ $term->slug ]->popularity += 0.2;
					}// end else

					$popular_terms[ $term->slug ]->popularity += $comment_count;
				}// end foreach
			}// end if
		}// end while

		uasort( $popular_terms, array( $this, 'popularity_sort' ) );
		$popular_terms = array_slice( $popular_terms, 0, $count );

		// put this in cache for 24 hours
		wp_cache_set( $cache_key, $popular_terms, 'go-popular', 86400 );

		return $popular_terms;
	}// end get_popular_terms

	/**
	 * Get terms that have a higher popularity in a short time period vs a longer time period
	 *
	 * @param array taxonomy array of taxonomies to pull terms from
	 * @param array args_period_short the args to pass for popularity, should be a short time period
	 * @param array args_period_long (optional) the args to pass for popularity, should be a longer time period.
	 *                               If omitted, it will use args_period_short with a time period that is 5 days longer
	 */
	public function get_emergent_terms( $taxonomy, $args_period_short, $args_period_long = NULL )
	{
		$cache_key = md5( serialize( array( $taxonomy, $args_period_short, $args_period_long ) ) );

		if ( $emergent_terms = wp_cache_get( $cache_key, 'go-popular-emergent' ) )
		{
			return $emergent_terms;
		}//end if

		$emergent_terms = array();

		if ( NULL === $args_period_long )
		{
			$args_period_long = $args_period_short;
			$args_period_long['from'] = date( 'Y-m-d', strtotime( '-5 days', strtotime( $args_period_short['from' ] ) ) );
		}//end if

		// determine a scaling factor based on the ratio of the time period durations
		$duration_short = $this->get_duration( $args_period_short['from'], $args_period_short['to'] );
		$duration_long = $this->get_duration( $args_period_long['from'], $args_period_long['to'] );
		$scaling_factor = $duration_short / $duration_long;

		// we need to look at all the terms in the period to see if a term is emergent
		$count = $args_period_short['count'] ?: -1;
		$args_period_short['count'] = $args_period_long['count'] = -1;

		// get the terms for the time periods
		$terms_short = $this->get_popular_terms( $taxonomy, $args_period_short );
		$terms_long = $this->get_popular_terms( $taxonomy, $args_period_long );

		foreach ( $terms_short as $slug => &$term_short )
		{
			// emergent score is $term_short['popularity'] - ( $term_long['popularity'] * $scaling_factor )
			$term_short->emergent_score = round( $term_short->popularity - ( $terms_long[ $slug ]->popularity * $scaling_factor ), 2 );

			// anything <= 0 is steady or falling, not emergent
			if ( $term_short->emergent_score > 0 )
			{
				$emergent_terms[] = $term_short;
			}// end if
		}// end foreach

		uasort( $emergent_terms, array( $this, 'emergent_score_sort' ) );
		if ( $count > 0 )
		{
			$emergent_terms = array_slice( $emergent_terms, 0, $count );
		}// end if
		else
		{
			$emergent_terms = $emergent_terms;
		}// end else

		// put this in cache for 24 hours
		wp_cache_set( $cache_key, $emergent_terms, 'go-popular-emergent', 86400 );

		return $emergent_terms;
	}//end get_emergent_terms

	/**
	 * Utility function to easily calculate durations between two times
	 *
	 * @param string $from strtotime compatible time indicating the start of the duration
	 * @param string $from strtotime compatible time indicating the end of the duration
	 * @return int number of seconds between times
	 */
	private function get_duration( $from, $to )
	{
		$from = strtotime( $from );
		$to = strtotime( $to );
		return $to - $from;
	}//end get_duration

	/**
	 * Sort function for popularity.  Higher popularity terms float to the top.
	 */
	public function popularity_sort( $a, $b )
	{
		if ( $a->popularity == $b->popularity )
		{
			return 0;
		}// end if

		return $a->popularity < $b->popularity ? 1 : -1;
	}// end popularity_sort

	/**
	 * Sort function for emergent score.  Higher emergent scored terms float to the top.
	 */
	public function emergent_score_sort( $a, $b )
	{
		if ( $a->emergent_score == $b->emergent_score )
		{
			return 0;
		}// end if

		return $a->emergent_score < $b->emergent_score ? 1 : -1;
	}// end emergent_score_sort

	public function get_popular_posts_by_term( $type, $taxonomy_slug, $term_slug, $args )
	{
		$args = wp_parse_args(
			$args,
			array(
				'count' => 3,
				'from' => '-7 days',
				'to' => null,
			)
		);

		$property = 'news' == $type ? 'gigaom' : 'research';

		$popular_posts = array();

		$query_args = array(
			'post_type'       => 'post',
			'post_status'     => 'publish',
			'posts_per_page'  => $args['count'],
			'orderby'         => 'meta_value',
			'meta_key'        => 'go_xpost_comment_count',
			'order'           => 'DESC',
			'tax_query'       => array(
				'relation'  => 'AND',
				0           => array(
					'taxonomy' => $taxonomy_slug,
					'field'    => 'slug',
					'terms'    => $term_slug,
				),
				1           => array(
					'taxonomy' => 'go-property',
					'field'    => 'slug',
					'terms'    => $property,
				),
			),
			'date_query'      => array(
				'after'   => $args['from'],
			),
		);

		if ( ! empty( $args['to'] ) )
		{
			$query_args['date_query']['before'] = $args['to'];
			$query_args['date_query']['inclusive'] = TRUE;
		}//end if

		$the_query = new WP_Query( $query_args );
		while ( $the_query->have_posts() )
		{
			$the_query->next_post();

			$popular_posts[] = $the_query->post;
		}// end while

		return $popular_posts;
	}// end get_popular_posts_by_term

	/**
	 * get a list of company and technology terms from postloops on the page
	 *
	 * @param $postloop_id (Optional) the ID of the postloop to query
	 */
	public function terms_from_postloops( $postloop_id = FALSE, $limit = 0 )
	{
		// these are the taxonomies we care about
		$taxonomies = array( 'company', 'technology' );

		// if we don't have a postloop id, merge all the postloop counts into a summary
		if ( FALSE === $postloop_id )
		{
			$tmp_emerging_terms = array();

			$all_terms = bcms_postloop()->terms;

			foreach ( $all_terms as $postloop_terms )
			{
				foreach ( $taxonomies as $taxonomy )
				{
					if ( ! isset( $postloop_terms[ $taxonomy ] ) )
					{
						continue;
					}// end if

					if ( ! isset( $tmp_emerging_terms[ $taxonomy ] ) )
					{
						$tmp_emerging_terms[ $taxonomy ] = array();
					}//end if

					foreach ( $postloop_terms[ $taxonomy ] as $term_id => $count )
					{
						if ( ! isset( $tmp_emerging_terms[ $taxonomy ][ $term_id ] ) )
						{
							$tmp_emerging_terms[ $taxonomy ][ $term_id ] = 0;
						}// end if
						$tmp_emerging_terms[ $taxonomy ][ $term_id ] += $count;
					}// end foreach
				}// end foreach
			}// end foreach
		}//end if
		else
		{
			$tmp_emerging_terms = bcms_postloop()->terms[ $postloop_id ];
		}//end else

		$emerging_terms = $tmp_emerging_terms['company'] + $tmp_emerging_terms['technology'];
		arsort( $emerging_terms );

		foreach ( $taxonomies as $taxonomy )
		{
			foreach ( $tmp_emerging_terms[ $taxonomy ] as $term_id => $count )
			{
				$emerging_terms[ $term_id ] = get_term( $term_id, $taxonomy );
				$emerging_terms[ $term_id ]->link = get_term_link( $term_id, $taxonomy );
				$emerging_terms[ $term_id ]->count = $count;
			}// end foreach
		}// end foreach

		$term_output = '';
		$current = 0;
		foreach ( $emerging_terms as $emerging_term )
		{
			$term_output .= '<a href="' . esc_url( $emerging_term->link ) . '">' . esc_html( $emerging_term->name ) . '</a>, ';
			$current++;

			if ( $limit && $current > $limit )
			{
				break;
			}//end if
		}// end foreach

		return substr( $term_output, 0, -2 );
	}//end terms_from_postloops
}//end class

function go_popular()
{
	global $go_popular;

	if ( ! $go_popular )
	{
		$go_popular = new GO_Popular;
	}//end if

	return $go_popular;
}//end go_popular
