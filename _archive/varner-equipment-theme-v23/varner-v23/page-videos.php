<?php
/* Template Name: Videos */
get_header();
?>

    <section class="pt-32 pb-16 bg-slate-950 text-white">
        <div class="max-w-7xl mx-auto px-4 space-y-6">
            <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em]">Varner Equipment</div>
            <h1 class="text-4xl md:text-6xl font-black tracking-tighter uppercase"><?php echo esc_html( get_the_title() ?: 'Product Videos' ); ?></h1>
            <?php if (get_the_content()): ?>
                <div class="text-slate-300 max-w-2xl font-bold">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>
            <div class="pt-4">
                <a href="https://www.youtube.com/@VarnerEquipment" target="_blank" class="inline-block bg-red-600 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg hover:bg-red-700 transition-all">View Our YouTube Channel</a>
            </div>
        </div>
    </section>

    <section class="pt-16 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-7xl mx-auto px-4">

            
            <?php 
            $terms = get_terms(array(
                'taxonomy' => 'video_category',
                'hide_empty' => false, // Set to true if you want to hide empty categories
            ));

            if (!empty($terms) && !is_wp_error($terms)): ?>
                <div class="space-y-24">
                <?php foreach ($terms as $term): ?>
                    <div>
                        <div class="mb-8">
                            <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase mb-2"><?php echo esc_html($term->name); ?></h2>
                            <?php if ($term->description): ?>
                                <p class="text-slate-500 font-medium max-w-2xl"><?php echo esc_html($term->description); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php 
                        $video_query = new WP_Query(array(
                            'post_type' => 'video',
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'video_category',
                                    'field' => 'term_id',
                                    'terms' => $term->term_id,
                                ),
                            ),
                        ));

                        if ($video_query->have_posts()): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                <?php while ($video_query->have_posts()): $video_query->the_post(); 
                                    $youtube_link = get_field('youtube_link');
                                ?>
                                    <div class="bg-white rounded-[2rem] overflow-hidden shadow-xl border-4 border-slate-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 group">
                                        <div class="aspect-video w-full bg-slate-100 relative">
                                            <?php 
                                            if ($youtube_link) {
                                                echo $youtube_link;
                                            } else {
                                                echo '<div class="absolute inset-0 flex items-center justify-center text-slate-400 font-bold uppercase text-xs tracking-widest">Video Unavailable</div>';
                                            }
                                            ?>
                                        </div>
                                        <div class="p-6">
                                            <h3 class="text-lg font-bold text-slate-900 leading-snug group-hover:text-red-600 transition-colors"><?php the_title(); ?></h3>
                                        </div>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-slate-400 font-bold uppercase text-xs tracking-widest">No videos in this category yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white p-12 rounded-[2rem] shadow-xl border-4 border-slate-100 text-center">
                    <p class="text-slate-500 font-bold mb-4">No video categories found.</p>
                    <p class="text-slate-400 text-sm">Please add categories and videos in the WordPress dashboard.</p>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <style>
        .aspect-video iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>

<?php get_footer(); ?>
