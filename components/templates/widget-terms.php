<div class="go-popular-terms ">
	<h2><?php echo esc_html( $title ); ?></h2>
	<ol class="terms <?php echo esc_attr( implode( '-taxonomy ', $taxonomy ) ); ?>">
		<?php
		$rank = 1;
		foreach ( $terms as $term )
		{
			// the lack of linebreaks is to avoid spacing around a CSS injected comma
			?>
			<li class="popular-term rank-<?php echo esc_attr( $rank ); ?>"><a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo wp_filter_nohtml_kses( $term->name ); ?></a></li>
			<?php
		}//end foreach
		?>
	</ol>
</div>
