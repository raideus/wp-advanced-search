<?php
/*
Template Name: Advanced Search Demo

A custom page template to demonstrate the functionality of WP Advanced Search.
To use, simply create a new page and select "Advanced Search Demo" under
Page Attributes > Template.
*/
?>

<?php get_header(); ?>

	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

		<article>
			<h1><?php the_title(); ?></h1>

			<?php 
			
				$wpas = array();

				$wpas['wp_query'] = array('post_type' => 'post',
				                                'posts_per_page' => 5,
				                                'tax_query' => array('relation' => 'OR'));

				$wpas['fields'][] = array('type' => 'search',
				                                'title' => 'Search',
				                                'std' => '');


				$wpas['fields'][] = array('type' => 'taxonomy',
					                                'title' => 'Category',
					                                'taxonomy' => 'category',
					                                'format' => 'select',
					                                'operator' => 'AND');

				$wpas['fields'][] = array('type' => 'taxonomy',
					                                'title' => 'Tags',
					                                'taxonomy' => 'post_tag',
					                                'format' => 'checkbox',
					                                'operator' => 'IN');

				$wpas['fields'][] = array('type' => 'author',
				                                'title' => 'Author',
				                                'format' => 'multi-select',
				                                'authors' => array(1));

				$wpas['fields'][] = array('type' => 'date',
				                                'title' => 'Year',
				                                'date_type' => 'year',
				                                'values' => array(),
				                                'format' => 'select');

				$wpas['fields'][] = array('type' => 'date',
				                                'title' => 'Month',
				                                'date_type' => 'month',
				                                'values' => array(),
				                                'format' => 'select');

				$wpas['fields'][] = array('type' => 'date',
				                                'title' => 'Day',
				                                'date_type' => 'day',
				                                'values' => array(),
				                                'format' => 'select');

				$wpas['fields'][] = array('type' => 'submit',
				                                'value' => 'Search');


				$event_filter = new WP_Advanced_Search($wpas);

				$event_filter->the_form();

				$temp_query = $wp_query;
				$wp_query = $event_filter->query();

				if ( have_posts() ): 

					while ( have_posts() ): the_post();
					?>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p><strong>Author:</strong> <?php the_author();?> &nbsp;&nbsp; <strong>Date:</strong> <?php the_date();?></p>
						<?php the_excerpt(); ?>
						<p><a href="<?php the_permalink(); ?>">Read more...</a></p>
					<?php
					endwhile; 

				$event_filter->pagination();
				$wp_query = $temp_query;

				else :

					echo 'Sorry, no posts matched your criteria.';

				endif;

			?>

		</article>

	<?php endwhile; endif; ?>

<?php get_footer(); ?>