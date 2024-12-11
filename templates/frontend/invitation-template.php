<?php
/*
Template Name: Subscriber's invitation template
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

SubscriptionHelper::subscriptionGetHeader( 'subscribers-header' ); ?>

<div class="section-login">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 sec-white">
                <?php if ( have_posts() ) : ?>
                    <?php while ( have_posts() ) : the_post(); ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <header class="entry-header">
                                <h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
                            </header>

                            <div class="entry-content">
                                <?php the_content(); ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p><?php _e( 'Lo siento, no se encontraron posts.', 'textdomain' ); ?></p>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php SubscriptionHelper::subscriptionGetFooter( 'subscribers-footer' );
