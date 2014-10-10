<?php

class GO_Popular
{
	/**
	 * constructor, of course
	 */
	public function __construct()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}//end __construct

	/**
	 * Hooks into the widgets_init action to initialize plugin widgets
	 */
	public function widgets_init()
	{
		require_once __DIR__ . '/class-go-popular-widget.php';
		register_widget( 'GO_Popular_Widget' );

		require_once __DIR__ . '/class-go-popular-terms-widget.php';
		register_widget( 'GO_Popular_Terms_Widget' );
	}//end widgets_init

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
