<div class="go-popular taxonomy-<?php echo $taxonomy->name; ?>">
	<h2><?php echo esc_html( $title ); ?></h2>
	<?php
		$rank = 1;
		foreach ( $terms as $term )
		{
			?>
			<div class="popular-term rank-<?php echo absint( $rank ); ?>">
				<header>
					<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo wp_filter_nohtml_kses( $term->name ); ?></a>
				</header>
				<?php
				foreach ( $term->popular_posts as $type => $posts )
				{
					if ( ! $posts )
					{
						continue;
					}//end if

					?>
					<section class="<?php echo esc_attr( $type ); ?>">
						<header><?php echo esc_html( $type ); ?></header>
						<?php
						foreach ( $posts as $post )
						{
							?>
							<article>
								<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" title="Permalink to <?php echo esc_attr( strip_tags( get_the_title( $post->ID ) ) ); ?>" rel="bookmark" itemprop="url"><?php echo get_the_title( $post->ID ); ?></a>
							</article>
							<?php
						}//end foreach
						?>
					</section>
					<?php
					$rank++;
				}//end foreach
				?>
			</div>
			<?php
		}//end foreach
	?>
</div>
