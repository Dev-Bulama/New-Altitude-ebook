<?php
/**
 * Sample Ebook Generator
 * Generates 5 dummy ebooks for testing
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Sample_Generator {

    /**
     * Generate sample ebooks
     */
    public static function generate_samples() {
        $samples = array(
            array(
                'title' => 'No Excuses, No Miracles',
                'content' => 'A powerful manifesto for radical responsibility. This transformative guide challenges you to take ownership of your life and create the future you desire. Duke Ofotare delivers hard-hitting truths about personal accountability, eliminating excuses, and taking massive action. Through compelling stories and practical frameworks, learn how to break free from victim mentality and step into your power as the creator of your destiny.',
                'author' => 'Duke Ofotare',
                'price' => 24.99,
                'publisher' => 'SkillScore Publishing',
                'isbn' => '978-0-00-000001-1',
                'pages' => 256,
                'language' => 'English',
                'category' => 'Self-Help',
                'excerpt' => 'Stop waiting for miracles. Stop making excuses. This is your wake-up call to take radical responsibility for your life and create the transformation you\'ve been waiting for.',
                'unlimited' => true,
                'enable_preview' => true,
                'enable_audio' => true,
            ),
            array(
                'title' => 'The Digital Marketing Blueprint',
                'content' => 'Master the art and science of digital marketing in the modern age. This comprehensive guide covers everything from SEO and content marketing to social media strategies and conversion optimization. Packed with real-world case studies, proven frameworks, and actionable tactics that deliver results. Whether you\'re a beginner or experienced marketer, this blueprint will transform your approach to online marketing and help you generate consistent, measurable results for your business.',
                'author' => 'Sarah Johnson',
                'price' => 39.99,
                'publisher' => 'Digital Mastery Press',
                'isbn' => '978-0-00-000002-8',
                'pages' => 342,
                'language' => 'English',
                'category' => 'Business',
                'excerpt' => 'Discover the proven strategies that top brands use to dominate online. Learn the exact framework for creating campaigns that convert browsers into buyers.',
                'unlimited' => true,
                'enable_preview' => true,
                'enable_audio' => true,
            ),
            array(
                'title' => 'Python for Data Science',
                'content' => 'Unlock the power of Python for data analysis, visualization, and machine learning. This hands-on guide takes you from Python basics to advanced data science techniques. Learn pandas, NumPy, matplotlib, scikit-learn, and TensorFlow through practical projects. Build predictive models, create stunning visualizations, and extract insights from complex datasets. Perfect for beginners and intermediate programmers looking to break into the data science field.',
                'author' => 'Dr. Michael Chen',
                'price' => 44.99,
                'publisher' => 'TechBook Publishing',
                'isbn' => '978-0-00-000003-5',
                'pages' => 418,
                'language' => 'English',
                'category' => 'Technology',
                'excerpt' => 'Transform raw data into actionable insights using Python. This practical guide includes 20+ real-world projects and downloadable datasets.',
                'unlimited' => true,
                'enable_preview' => true,
                'enable_audio' => false,
            ),
            array(
                'title' => 'The Minimalist Entrepreneur',
                'content' => 'Build a profitable business without the complexity. Learn how to start and scale a business with minimal resources, maximum impact, and authentic purpose. This revolutionary approach to entrepreneurship rejects hustle culture and embraces sustainable growth. Discover how to validate ideas quickly, build products customers love, and create a business that supports your ideal lifestyle. Packed with case studies of successful minimalist businesses and step-by-step frameworks you can implement today.',
                'author' => 'James Martinez',
                'price' => 29.99,
                'publisher' => 'Indie Business Press',
                'isbn' => '978-0-00-000004-2',
                'pages' => 298,
                'language' => 'English',
                'category' => 'Business',
                'excerpt' => 'You don\'t need venture capital or a massive team to build a successful business. Discover the minimalist approach to entrepreneurship that prioritizes profit, purpose, and freedom.',
                'unlimited' => true,
                'enable_preview' => true,
                'enable_audio' => true,
            ),
            array(
                'title' => 'Atomic Habits for Creative Minds',
                'content' => 'Transform your creative practice through the power of tiny habits. This groundbreaking guide applies proven habit-formation science specifically to artists, writers, designers, and creative professionals. Learn how to overcome creative blocks, build a consistent practice, and produce your best work without burnout. Discover the exact system used by world-class creatives to maintain motivation, manage projects, and bring their biggest ideas to life. Includes 30-day creative habit challenges and downloadable worksheets.',
                'author' => 'Emma Williams',
                'price' => 34.99,
                'publisher' => 'Creative Life Publishing',
                'isbn' => '978-0-00-000005-9',
                'pages' => 276,
                'language' => 'English',
                'category' => 'Self-Help',
                'excerpt' => 'Stop waiting for inspiration and start building a creative practice that produces consistent results. Small changes, remarkable creative transformation.',
                'unlimited' => true,
                'enable_preview' => true,
                'enable_audio' => true,
            ),
        );

        $created_posts = array();

        foreach ($samples as $sample) {
            // Check if post already exists using WP_Query (replaces deprecated get_page_by_title)
            $existing_query = new WP_Query(array(
                'post_type' => 'ebook',
                'title' => $sample['title'],
                'posts_per_page' => 1,
                'post_status' => 'any',
                'fields' => 'ids',
            ));

            if ($existing_query->have_posts()) {
                wp_reset_postdata();
                continue; // Skip if already exists
            }

            // Create the post
            $post_id = wp_insert_post(array(
                'post_title' => $sample['title'],
                'post_content' => $sample['content'],
                'post_excerpt' => $sample['excerpt'],
                'post_type' => 'ebook',
                'post_status' => 'publish',
            ));

            if (is_wp_error($post_id)) {
                continue;
            }

            // Add meta data
            update_post_meta($post_id, '_ebook_price', $sample['price']);
            update_post_meta($post_id, '_ebook_author', $sample['author']);
            update_post_meta($post_id, '_ebook_publisher', $sample['publisher']);
            update_post_meta($post_id, '_ebook_isbn', $sample['isbn']);
            update_post_meta($post_id, '_ebook_pages', $sample['pages']);
            update_post_meta($post_id, '_ebook_language', $sample['language']);
            update_post_meta($post_id, '_ebook_unlimited', $sample['unlimited']);
            update_post_meta($post_id, '_ebook_enable_preview', $sample['enable_preview']);
            update_post_meta($post_id, '_ebook_enable_audio', $sample['enable_audio']);

            // Set category
            $category_term = term_exists($sample['category'], 'ebook_category');
            if (!$category_term) {
                $category_term = wp_insert_term($sample['category'], 'ebook_category');
            }
            if (!is_wp_error($category_term)) {
                wp_set_post_terms($post_id, array($category_term['term_id']), 'ebook_category');
            }

            $created_posts[] = array(
                'id' => $post_id,
                'title' => $sample['title'],
            );
        }

        return $created_posts;
    }

    /**
     * Register admin page for sample generation
     */
    public static function register_admin_page() {
        add_submenu_page(
            'edit.php?post_type=ebook',
            'Generate Samples',
            'Generate Samples',
            'manage_options',
            'skillscore-generate-samples',
            array(__CLASS__, 'render_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        if (isset($_POST['generate_samples']) && check_admin_referer('skillscore_generate_samples')) {
            $created = self::generate_samples();

            if (count($created) > 0) {
                echo '<div class="notice notice-success"><p><strong>Success!</strong> Generated ' . count($created) . ' sample ebook(s):</p><ul>';
                foreach ($created as $post) {
                    echo '<li>' . esc_html($post['title']) . ' (ID: ' . $post['id'] . ')</li>';
                }
                echo '</ul></div>';
            } else {
                echo '<div class="notice notice-info"><p>No new samples created. They may already exist.</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1>Generate Sample Ebooks</h1>
            <p>This tool will create 5 professional sample ebooks for testing and demonstration purposes.</p>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Sample Ebooks to be Created:</h2>
                <ol>
                    <li><strong>No Excuses, No Miracles</strong> by Duke Ofotare - Self-Help ($24.99)</li>
                    <li><strong>The Digital Marketing Blueprint</strong> by Sarah Johnson - Business ($39.99)</li>
                    <li><strong>Python for Data Science</strong> by Dr. Michael Chen - Technology ($44.99)</li>
                    <li><strong>The Minimalist Entrepreneur</strong> by James Martinez - Business ($29.99)</li>
                    <li><strong>Atomic Habits for Creative Minds</strong> by Emma Williams - Self-Help ($34.99)</li>
                </ol>

                <p><strong>Note:</strong> These are dummy ebooks with placeholder content. You can edit them after creation to add real covers, files, and customize the details.</p>
            </div>

            <form method="post" style="margin-top: 30px;">
                <?php wp_nonce_field('skillscore_generate_samples'); ?>
                <button type="submit" name="generate_samples" class="button button-primary button-large">
                    Generate 5 Sample Ebooks
                </button>
            </form>

            <div class="card" style="max-width: 800px; margin-top: 30px;">
                <h3>What to do after generating samples:</h3>
                <ol>
                    <li>Go to <strong>Ebooks → All Ebooks</strong> to see the generated samples</li>
                    <li>Edit each ebook to add featured images (book covers)</li>
                    <li>Upload actual ebook files (PDF/EPUB) in the File Upload meta box</li>
                    <li>Test the shortcodes:
                        <ul>
                            <li><code>[skillscore_ebooks]</code> - Display all ebooks in a grid</li>
                            <li><code>[skillscore_ebook id="POST_ID"]</code> - Display single ebook</li>
                        </ul>
                    </li>
                    <li>Configure payment gateways in <strong>Ebooks → Settings</strong></li>
                </ol>
            </div>
        </div>
        <?php
    }
}
