<?php
/**
 * Elementor Widget for Ebooks
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class SkillScore_Elementor_Ebook_Widget extends Widget_Base {

    /**
     * Get widget name.
     */
    public function get_name() {
        return 'skillscore_ebook';
    }

    /**
     * Get widget title.
     */
    public function get_title() {
        return __('SkillScore Ebooks', 'skillscore-ebook');
    }

    /**
     * Get widget icon.
     */
    public function get_icon() {
        return 'eicon-posts-grid';
    }

    /**
     * Get widget categories.
     */
    public function get_categories() {
        return ['general'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        // Content section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'skillscore-ebook'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'display_type',
            [
                'label' => __('Display Type', 'skillscore-ebook'),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('Grid (Multiple Ebooks)', 'skillscore-ebook'),
                    'single' => __('Single Ebook', 'skillscore-ebook'),
                ],
            ]
        );

        $this->add_control(
            'ebook_id',
            [
                'label' => __('Select Ebook', 'skillscore-ebook'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_ebooks_list(),
                'condition' => [
                    'display_type' => 'single',
                ],
            ]
        );

        $this->add_control(
            'limit',
            [
                'label' => __('Number of Ebooks', 'skillscore-ebook'),
                'type' => Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 100,
                'condition' => [
                    'display_type' => 'grid',
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'skillscore-ebook'),
                'type' => Controls_Manager::SELECT,
                'default' => 3,
                'options' => [
                    '1' => __('1 Column', 'skillscore-ebook'),
                    '2' => __('2 Columns', 'skillscore-ebook'),
                    '3' => __('3 Columns', 'skillscore-ebook'),
                    '4' => __('4 Columns', 'skillscore-ebook'),
                ],
                'condition' => [
                    'display_type' => 'grid',
                ],
            ]
        );

        $this->add_control(
            'category',
            [
                'label' => __('Category', 'skillscore-ebook'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_categories_list(),
                'default' => '',
                'condition' => [
                    'display_type' => 'grid',
                ],
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => __('Order By', 'skillscore-ebook'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => __('Date', 'skillscore-ebook'),
                    'title' => __('Title', 'skillscore-ebook'),
                    'rand' => __('Random', 'skillscore-ebook'),
                ],
                'condition' => [
                    'display_type' => 'grid',
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => __('Order', 'skillscore-ebook'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => __('Descending', 'skillscore-ebook'),
                    'ASC' => __('Ascending', 'skillscore-ebook'),
                ],
                'condition' => [
                    'display_type' => 'grid',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        if ($settings['display_type'] === 'single' && !empty($settings['ebook_id'])) {
            // Render single ebook
            echo do_shortcode('[skillscore_ebook id="' . intval($settings['ebook_id']) . '"]');
        } else {
            // Render ebook grid
            $atts = array(
                'limit' => $settings['limit'] ?? 12,
                'columns' => $settings['columns'] ?? 3,
                'category' => $settings['category'] ?? '',
                'orderby' => $settings['orderby'] ?? 'date',
                'order' => $settings['order'] ?? 'DESC',
            );

            $shortcode_atts = array();
            foreach ($atts as $key => $value) {
                if (!empty($value)) {
                    $shortcode_atts[] = $key . '="' . esc_attr($value) . '"';
                }
            }

            echo do_shortcode('[skillscore_ebooks ' . implode(' ', $shortcode_atts) . ']');
        }
    }

    /**
     * Get list of ebooks for dropdown.
     */
    private function get_ebooks_list() {
        $ebooks = get_posts(array(
            'post_type' => 'ebook',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ));

        $options = array('' => __('Select Ebook', 'skillscore-ebook'));

        foreach ($ebooks as $ebook) {
            $options[$ebook->ID] = $ebook->post_title;
        }

        return $options;
    }

    /**
     * Get list of categories for dropdown.
     */
    private function get_categories_list() {
        $categories = get_terms(array(
            'taxonomy' => 'ebook_category',
            'hide_empty' => false,
        ));

        $options = array('' => __('All Categories', 'skillscore-ebook'));

        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $options[$category->slug] = $category->name;
            }
        }

        return $options;
    }
}
