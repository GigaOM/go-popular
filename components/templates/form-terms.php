<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
	<input class="widefat" type="text"
		id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
		title="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
		value="<?php echo esc_attr( $title ); ?>" />
	Note: Using <code>_TAXONOMY_</code> in the title injects the queried_object's name.
</p>
<p>
	Show
	<select id="<?php echo $this->get_field_id( 'num' ); ?>" name="<?php echo $this->get_field_name( 'num' ); ?>">
		<?php
		for ( $i = 1; $i <= 50; $i++ )
		{
			?>
			<option value="<?php echo $i; ?>" <?php selected( $i, $num ); ?>><?php echo $i; ?></option>
			<?php
		} // END for
		?>
	</select>
	terms
</p>
<p>
	from these taxonomies
	<?php
	foreach ( get_taxonomies( array(), 'objects' ) as $slug => $tax )
	{
		if ( ! $tax->labels->name )
		{
			continue;
		}//end if
		?>
		<br />
		<input type="checkbox"
			name="<?php echo $this->get_field_name( 'taxonomy-' . $slug ); ?>"
			value="<?php echo esc_attr( $slug ); ?>"
			id="<?php echo $this->get_field_id( 'taxonomy-' . $slug ); ?>"
			<?php echo checked( in_array( $slug, $taxonomy ) ); ?>>
		<label for="<?php echo $this->get_field_id( 'taxonomy-' . $slug ); ?>"><?php echo $tax->label; ?></label>
		<?php
	} // END foreach
	?>
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
		value="<?php echo esc_attr( $term_to ); ?>"
		style="width: 40%;" />.
</p>
