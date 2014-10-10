Gigaom Popular Stuff
==========

Determines popular terms/posts by taxonomy.  This adds two new widgets.


GO Popular
----------
Shows popluar posts


GO Popular Terms
----------
Shows popular terms.  This would allows creation of a limited tag cloud with a limited set of popular tags.


Service plugin
--------------
This plugin can also be used directly within a theme or another plugin like so:
```php
$terms = go_popular()->get_popular_terms( $taxonomy, $term_args );
```
