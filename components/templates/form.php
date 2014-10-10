<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
	<input class="widefat" type="text"
		id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
		title="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
		value="<?php echo esc_attr( $title ); ?>" />
</p>
<p>
	Show
	<select id="<?php echo esc_attr( $this->get_field_id( 'num' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'num' ) ); ?>">
		<?php
		for ( $i = 1; $i <= 10; $i++ )
		{
			?>
			<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $num ); ?>><?php echo absint( $i ); ?></option>
			<?php
		}//end for
		?>
	</select>
	<select id="<?php echo esc_attr( $this->get_field_id( 'taxonomy' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'taxonomy' ) ); ?>" style="width: 125px;">
		<option value="">Select a taxonomy...</option>
		<?php
		foreach ( get_taxonomies( array(), 'objects' ) as $slug => $tax )
		{
			if ( ! $tax->labels->name )
			{
				continue;
			}//end if
			?>
			<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $taxonomy ); ?>><?php echo esc_html( $tax->label ); ?></option>
			<?php
		}//end foreach
		?>
	</select>
</p>
<p>
	that were popular between (inclusive)
	<input type="text"
		id="<?php echo esc_attr( $this->get_field_id( 'term_from' ) ); ?>"
		title="<?php echo esc_attr( $this->get_field_id( 'term_from' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'term_from' ) ); ?>"
		value="<?php echo esc_attr( $term_from ); ?>"
		style="width: 40%;" />
	and
	<input type="text"
		id="<?php echo esc_attr( $this->get_field_id( 'term_to' ) ); ?>"
		title="<?php echo esc_attr( $this->get_field_id( 'term_to' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'term_to' ) ); ?>"
		value="<?php echo esc_attr( esc_attr( $term_to ) ); ?>"
		style="width: 40%;" />.
</p>
<br>
<p>
	Show
	<select id="<?php echo esc_attr( $this->get_field_id( 'num_news' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'num_news' ) ); ?>">
		<?php
		for ( $i = 1; $i <= 10; $i++ )
		{
			?>
			<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $num_news ); ?>><?php echo absint( $i ); ?></option>
			<?php
		}//end for
		?>
	</select>
	news articles and
</p>
<p>
	Show
	<select id="<?php echo esc_attr( $this->get_field_id( 'num_research' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'num_research' ) ); ?>">
		<?php
		for ( $i = 1; $i <= 10; $i++ )
		{
			?>
			<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $num_research ); ?>><?php echo absint( $i ); ?></option>
			<?php
		}//end for
		?>
	</select>
	research articles
</p>
<p>
	that were popular between (inclusive)
	<input type="text"
		id="<?php echo esc_attr( $this->get_field_id( 'article_from' ) ); ?>"
		title="<?php echo esc_attr( $this->get_field_id( 'article_from' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'article_from' ) ); ?>"
		value="<?php echo esc_attr( $article_from ); ?>"
		style="width: 40%;" />
	and
	<input type="text"
		id="<?php echo esc_attr( $this->get_field_id( 'article_to' ) ); ?>"
		title="<?php echo esc_attr( $this->get_field_id( 'article_to' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'article_to' ) ); ?>"
		value="<?php echo esc_attr( $article_to ); ?>"
		style="width: 40%;" />.
</p>
