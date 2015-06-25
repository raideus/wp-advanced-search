<?php
/*
AJAX Results Template Example

This is an example of a template part which can be used to customize how search
results appear when using AJAX.
*/
?>

<?php if ( have_posts() ): ?>
   <?php while ( have_posts() ): the_post(); ?>

        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p><strong>Author:</strong> <?php the_author();?> &nbsp;&nbsp; <strong>Date:</strong> <?php the_date();?></p>
        <?php the_excerpt(); ?>
        <p><a href="<?php the_permalink(); ?>">Read more...</a></p>

    <?php endwhile; ?>

<?php else : ?>

    <p>Sorry, no posts matched your criteria.</p>

<?php endif; ?>

<?php wp_reset_query(); ?>