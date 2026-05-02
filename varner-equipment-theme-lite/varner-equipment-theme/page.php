<?php get_header(); ?>

    <section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-7xl mx-auto px-4">
            <?php
            if ( have_posts() ) :
                while ( have_posts() ) : the_post();
            ?>
                <div class="mb-12">
                    <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-6"><?php the_title(); ?></h1>
                    <div class="w-24 h-2 bg-red-600 mb-10"></div>
                </div>
                
                <div class="prose prose-lg prose-slate max-w-none font-bold text-slate-600">
                    <?php the_content(); ?>
                </div>

            <?php
                endwhile;
            endif;
            ?>
        </div>
    </section>

<?php get_footer(); ?>
