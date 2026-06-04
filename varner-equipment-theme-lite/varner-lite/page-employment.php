<?php
/* Template Name: Employment */
if ( ! session_id() ) { session_start(); }

$tagline  = function_exists('varner_get_theme_setting') ? varner_get_theme_setting('employment_tagline', 'Join The Crew') : 'Join The Crew';
$headline = function_exists('varner_get_theme_setting') ? varner_get_theme_setting('employment_headline', 'Careers at Varner') : 'Careers at Varner';
$intro    = function_exists('varner_get_theme_setting') ? varner_get_theme_setting('employment_intro', 'We are always looking for hardworking, reliable individuals to join our team in Delta, Colorado. If you have a passion for heavy equipment and a dedication to customer service, we want to hear from you.') : 'We are always looking for hardworking, reliable individuals to join our team in Delta, Colorado. If you have a passion for heavy equipment and a dedication to customer service, we want to hear from you.';
$jobs     = function_exists('varner_get_theme_setting') ? varner_get_theme_setting('employment_jobs') : array();

$submitted = isset( $_GET['application'] ) && $_GET['application'] === 'sent';

get_header();
?>

    <section class="pt-32 pb-24 bg-slate-50 min-h-[60vh]">
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-16 text-center max-w-3xl mx-auto">
                <div class="text-red-600 font-black text-[10px] uppercase tracking-[0.4em] mb-4"><?php echo esc_html( $tagline ); ?></div>
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-6"><?php echo esc_html( $headline ); ?></h1>
                <div class="w-24 h-2 bg-red-600 mx-auto mb-8"></div>
                <div class="text-lg font-bold text-slate-600 leading-relaxed"><?php echo wp_kses_post( $intro ); ?></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Current Openings -->
                <div class="space-y-6">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tighter uppercase mb-8">Current Openings</h3>

                    <?php if ( empty( $jobs ) ) : ?>
                    <div class="bg-white p-8 rounded-3xl shadow-lg border-2 border-dashed border-slate-200 text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-black text-slate-900 uppercase tracking-tighter mb-2">No Open Positions</h4>
                        <p class="text-sm font-bold text-slate-500 leading-relaxed max-w-md mx-auto mb-6">
                            We are not currently hiring, but we are always looking for hardworking, reliable individuals to join our crew. Please feel free to submit a general application below or check back later!
                        </p>
                        <a href="#apply" class="inline-block bg-red-600 text-white px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-slate-900 transition-colors shadow-md">Submit General Application</a>
                    </div>
                    <?php else : ?>
                        <?php foreach ( $jobs as $job ) :
                            $title       = esc_html( $job['job_title'] );
                            $type        = esc_html( $job['job_type'] ?: 'Full-Time' );
                            $location    = esc_html( $job['job_location'] ?: 'Delta, CO' );
                            $description = esc_html( $job['job_description'] );
                            $show_badge  = ! empty( $job['job_show_badge'] );
                            $badge_text  = esc_html( $job['job_badge_text'] ?: 'Urgently Hiring' );
                        ?>
                        <div class="bg-white p-8 rounded-3xl shadow-lg border-2 border-slate-100 hover:border-red-600 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                              <div>
                                    <h4 class="text-xl font-black text-slate-900 uppercase tracking-tighter"><?php echo $title; ?></h4>
                                    <div class="text-xs font-bold text-slate-500 mt-1"><?php echo $type; ?> &bull; <?php echo $location; ?></div>
                                </div>
                                <?php if ( $show_badge ) : ?>
                                <span class="bg-red-100 text-red-600 text-[9px] font-black uppercase px-3 py-1.5 rounded-lg"><?php echo $badge_text; ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm font-bold text-slate-600 mb-6 leading-relaxed"><?php echo nl2br( esc_html( $description ) ); ?></p>
                            <a href="#apply" class="inline-block bg-slate-900 text-white px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-600 transition-colors">Apply Now</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Application Form -->
                <div id="apply" class="bg-slate-950 p-8 md:p-10 rounded-[3rem] text-white">
                    <h3 class="text-3xl font-black tracking-tighter uppercase mb-2">Submit Application</h3>
                    <p class="text-sm text-slate-400 font-bold mb-8">Fill out the form below or email your resume to contact@varnerequipment.com</p>

                    <?php if ( $submitted ) : ?>
                    <div class="bg-green-600 text-white rounded-2xl px-6 py-4 mb-6 font-bold text-sm">
                        Your application has been submitted. We will be in touch soon!
                    </div>
                    <?php endif; ?>

                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <?php wp_nonce_field( 'varner_employment_submit', 'varner_employment_nonce' ); ?>
                        <input type="hidden" name="action" value="varner_employment_submit">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">First Name</label>
                                <input type="text" name="first_name" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600 text-white" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Last Name</label>
                                <input type="text" name="last_name" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600 text-white" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Email Address</label>
                                <input type="email" name="email" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600 text-white" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Phone Number</label>
                                <input type="tel" name="phone" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600 text-white" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Position Applied For</label>
                            <select name="position" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600 text-white">
                                <?php foreach ( $jobs as $job ) : ?>
                                <option value="<?php echo esc_attr( $job['job_title'] ); ?>"><?php echo esc_html( $job['job_title'] ); ?></option>
                                <?php endforeach; ?>
                                <option value="General Inquiry / Other">General Inquiry / Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Brief Cover Letter / Experience</label>
                            <textarea name="cover_letter" rows="4" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600 text-white resize-none" required></textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Upload Resume</label>
                            <input type="file" name="resume" accept=".pdf,.doc,.docx"
                                class="w-full text-sm text-slate-400
                                    file:mr-4 file:py-2 file:px-5
                                    file:rounded-lg file:border-0
                                    file:text-[10px] file:font-black file:uppercase file:tracking-widest
                                    file:bg-red-600 file:text-white
                                    hover:file:bg-red-700 file:cursor-pointer cursor-pointer">
                            <p class="text-[10px] text-slate-500 mt-1.5">PDF, DOC, or DOCX &mdash; Max 5 MB</p>
                        </div>

                        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                            <div class="bg-slate-800 px-5 py-4 rounded-xl border border-slate-700 shrink-0">
                                <?php
                                    $num1 = rand(1, 10);
                                    $num2 = rand(1, 10);
                                    $_SESSION['varner_employment_captcha'] = $num1 + $num2;
                                ?>
                                <span class="text-2xl font-black text-white tracking-widest"><?php echo $num1; ?> + <?php echo $num2; ?> = ?</span>
                            </div>
                            <div class="flex-1 w-full">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">What is the sum? <span class="text-red-500">*</span></label>
                                <input type="number" name="captcha_answer" required placeholder="Type answer here..." class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-600 text-white">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-red-600 text-white py-4 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg hover:bg-white hover:text-red-600 transition-all mt-4 border border-transparent hover:border-red-600">
                            Submit Application
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
