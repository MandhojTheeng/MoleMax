<?php

add_action( 'wp_enqueue_scripts', 'salient_child_enqueue_styles', 100);

function salient_child_enqueue_styles() {

		$nectar_theme_version = nectar_get_theme_version();

		wp_enqueue_style( 'salient-child-style', get_stylesheet_directory_uri() . '/style.css', [], filemtime( get_stylesheet_directory() . '/style.css' ));

    if ( is_rtl() ) {
   		wp_enqueue_style(  'salient-rtl',  get_template_directory_uri(). '/rtl.css', array(), '1.1', 'screen' );
    }
}

function add_additional_add_to_cart_button_before_description() {
    global $product;

    // Get the product description and short description
    $description = $product->get_description();
    $short_description = $product->get_short_description();

    // Check if the product description or short description length is greater than 100 characters
    if (strlen($short_description) > 4000) {
        // Check if the product is purchasable and in stock
        if ($product->is_purchasable() && $product->is_in_stock()) {
            woocommerce_template_single_add_to_cart();
        }
    }
}
// add_action('woocommerce_single_product_summary', 'add_additional_add_to_cart_button_before_description', 19);


function mmx_remove_wp_version() {
    return '';
}
add_filter('the_generator', 'mmx_remove_wp_version');


function mmx_remove_wp_ver_css_js( $src ) {
    if ( strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) )
        $src = remove_query_arg( 'ver', $src );
    return $src;
    }
add_filter( 'style_loader_src', 'mmx_remove_wp_ver_css_js', 9999 );
add_filter( 'script_loader_src', 'mmx_remove_wp_ver_css_js', 9999 );

function get_user_geo_country(){
    $geo      = new WC_Geolocation(); // Get WC_Geolocation instance object
    $user_ip  = $geo->get_ip_address(); // Get user IP
//    error_log(print_r('ip addy = ' . $user_ip,true));
    $user_geo = $geo->geolocate_ip( $user_ip ); // Get geolocated user data.
// error_log(print_r($user_geo,true));
    $country  = $user_geo['country']; // Get the country code
// error_log('country');error_log($country);
    return WC()->countries->countries[ $country ]; // return the country name

}

add_filter( 'script_loader_tag', 'add_data_cfasync_to_admin_bar', 10, 3 );
function add_data_cfasync_to_admin_bar( $tag, $handle, $src ) {
    // Check if this is the 'admin-bar' script handle
    if ( 'admin-bar' === $handle ) {
        // Add the data-cfasync="false" attribute to the <script> tag
        $tag = str_replace( 'src', 'data-cfasync="false" src', $tag );
    }
    return $tag;
}

add_action('wp_head','mmx_lucky_orange_script');
function mmx_lucky_orange_script() { ?>
	<script async defer src=https://tools.luckyorange.com/core/lo.js?site-id=d3efb373></script>
<?php
}

/*
 * START TEAM SEO work for Trello issue #93 Blog Section Improvements – Staging Site Ready for Review(MMX)
 */


/*
 * SINGLE POST DETAIL PAGE: TABLE OF CONTENTS & SHARE UI
*/
add_filter('the_content', function($content) {
    if (!is_single()) return $content;

    $share_ui = '
    <div class="mmx-share-col">
        <div class="mmx-share-link-wrapper" onclick="mmx_fast_share(this)" aria-label="Share">
            <svg class="mmx-share-icon" viewBox="0 0 24 24" fill="none" stroke="#283c8f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                <polyline points="16 6 12 2 8 6"></polyline>
                <line x1="12" y1="2" x2="12" y2="15"></line>
            </svg>
            <span class="mmx-share-text">SHARE THIS ARTICLE</span>
            <span class="mmx-copy-toast">Copied!</span>
        </div>
    </div>';

    if (strpos($content, 'ez-toc-container') !== false) {
        $content = str_replace('<div id="ez-toc-container"', '<div class="mmx-sidebar-sticky-wrapper"><div id="ez-toc-container"', $content);
        $content = str_replace('</nav></div>', '</nav></div>' . $share_ui . '</div>', $content);
    }

    return $content;
}, 100);

/*
 * SHARE LOGIC
*/
add_action('wp_footer', function() {
    ?>
    <script>
    function mmx_fast_share(el) {
        const toast = el.querySelector('.mmx-copy-toast');
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({ title: document.title, url: url })
                .then(() => mmx_show_toast(toast))
                .catch(() => mmx_do_copy(url, toast));
        } else {
            mmx_do_copy(url, toast);
        }
    }

    function mmx_do_copy(text, toast) {
        const input = document.createElement("input");
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand("copy");
        document.body.removeChild(input);
        mmx_show_toast(toast);
    }

    function mmx_show_toast(toast) {
        if (!toast) return;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2000);
    }
    </script>
    <?php
});

/*
 * DISPLAY 3 RELATED POSTS WITH IMAGES
*/
add_filter('the_content', function($content) {
    if (!is_single()) return $content;

    global $post;
    $categories = get_the_category($post->ID);
    $category_ids = array();
    if ($categories) {
        foreach($categories as $cat) $category_ids[] = $cat->term_id;
    }

    $args = array(
        'category__in'   => $category_ids,
        'post__not_in'   => array($post->ID),
        'posts_per_page' => 3,
        'orderby'        => 'rand',
        'post_status'    => 'publish'
    );

    $related_query = new WP_Query($args);

    if ($related_query->post_count < 3) {
        $needed = 3 - $related_query->post_count;
        $exclude = array_merge(array($post->ID), wp_list_pluck($related_query->posts, 'ID'));

        $fallback_args = array(
            'posts_per_page' => $needed,
            'post__not_in'   => $exclude,
            'orderby'        => 'date'
        );
        $fallback_query = new WP_Query($fallback_args);
    }

    if ($related_query->have_posts() || (isset($fallback_query) && $fallback_query->have_posts())) {
        $related_html = '<div class="mmx-related-section">';
        $related_html .= '<div class="mmx-fake-heading">Related Articles</div>';
        $related_html .= '<div class="mmx-post-grid">';

        $output_card = function($q_post) {
            $cats = get_the_category($q_post->ID);
            $cat_name = !empty($cats) ? $cats->name : 'Blog';
            $thumb = get_the_post_thumbnail_url($q_post->ID, 'medium_large') ?: 'https://via.placeholder.com/600x400';

            return '
            <article class="mmx-post-card">
                <a href="' . get_permalink($q_post->ID) . '">
                    <div class="mmx-card-image" style="background-image: url(' . esc_url($thumb) . ');"></div>
                    <span class="mmx-post-tag">' . esc_html(strtoupper($cat_name)) . '</span>
                    <div class="mmx-post-card-title">' . get_the_title($q_post->ID) . '</div>
                    <span class="mmx-post-date">' . get_the_date('M j, Y', $q_post->ID) . '</span>
                </a>
            </article>';
        };

        foreach ($related_query->posts as $p) $related_html .= $output_card($p);
        if (isset($fallback_query)) {
            foreach ($fallback_query->posts as $p) $related_html .= $output_card($p);
        }

        $related_html .= '</div></div>';
        wp_reset_postdata();
        return $content . $related_html;
    }

    return $content;
}, 1000);

/*
 * Refined Injection: Category List
*/
function molemax_blog_grid_injection_v4($content) {
    if ( !is_home() && !is_page('blog') && !is_archive() ) {
        return $content;
    }

    $categories = get_categories(array('hide_empty' => 1));
    if (empty($categories)) return $content;

    $cat_html = '<div class="molemax-categories-injected">';
    $cat_html .= '<ul class="molemax-cat-list">';
    $cat_html .= '<li><a href="'.home_url('/blog').'">ALL</a></li>';

    foreach ($categories as $category) {
        if($category->name !== 'Uncategorized') {
            $cat_html .= '<li><a href="'.esc_url(get_category_link($category->term_id)).'">' . strtoupper(esc_html($category->name)) . '</a></li>';
        }
    }
    $cat_html .= '</ul></div>';

    $blog_grid_start = '<div class="post-area';

    if (strpos($content, $blog_grid_start) !== false) {
        $content = str_replace($blog_grid_start, $cat_html . $blog_grid_start, $content);
    } else {
        $content .= $cat_html;
    }

    return $content;
}
add_filter('the_content', 'molemax_blog_grid_injection_v4', 999);


/*
 * Move the "Summarize" section above "Related Articles"
*/
function relocate_summarize_section() {
    ?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                var summarizeSection = $('.bottom-meta');
                var relatedSection = $('.mmx-related-section');

                if (summarizeSection.length > 0 && relatedSection.length > 0) {
                    summarizeSection.insertBefore(relatedSection);
                }
            });
        })(jQuery);
    </script>
    <?php
}
add_action('wp_footer', 'relocate_summarize_section');


/*
 * Inject Post Tags (Keywords) and Move Summarize Section
*/
function relocate_and_inject_keywords_logic() {
    if ( !is_single() ) return;

    $tags = get_the_tags();
    $tag_html = '';

    if ($tags) {
        $tag_html .= '<div class="post-keywords-wrapper">';
        $tag_html .= '<span class="keyword-label">Keywords:</span>';
        foreach ($tags as $tag) {
            $tag_html .= '<a href="' . get_tag_link($tag->term_id) . '" class="post-keyword-pill">' . $tag->name . '</a>';
        }
        $tag_html .= '</div>';
    }

    ?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                var summarizeSection = $('.bottom-meta');
                var relatedSection = $('.mmx-related-section');
                var keywordHtml = <?php echo json_encode($tag_html); ?>;

                if (summarizeSection.length > 0 && relatedSection.length > 0) {
                    summarizeSection.insertBefore(relatedSection);

                    if (keywordHtml) {
                        $(keywordHtml).insertBefore(summarizeSection);
                    }
                }
            });
        })(jQuery);
    </script>
    <?php
}
add_action('wp_footer', 'relocate_and_inject_keywords_logic');

/*
 * END TEAM SEO work for Trello issue #93 Blog Section Improvements – Staging Site Ready for Review(MMX)
 */


/*
 * START PRODUCT DESIGN CHANGES
 */

/**
 * Add custom cart+ icon to the product meta section
 */
add_action( 'woocommerce_after_shop_loop_item_title', 'custom_minimal_cart_plus_icon', 15 );

function custom_minimal_cart_plus_icon() {
    global $product;

    if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
        return;
    }

    $link = $product->add_to_cart_url();
    $id   = $product->get_id();

    echo '<div class="custom-cart-wrapper">';
    echo sprintf(
        '<a href="%s" data-product_id="%s" class="custom-ajax-cart add_to_cart_button ajax_add_to_cart" aria-label="Add to cart">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 6H22L20 15H7L5 3H2" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="20" r="1.5" fill="black"/>
                <circle cx="18" cy="20" r="1.5" fill="black"/>
                <path d="M11 10.5H17M14 7.5V13.5" stroke="black" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </a>',
        esc_url( $link ),
        esc_attr( $id )
    );
    echo '</div>';
}




/*
 * DYNAMIC STAR RATING DISPLAY
*/
add_action( 'woocommerce_after_shop_loop_item_title', 'strictly_solve_star_rating', 50 );

function strictly_solve_star_rating() {
    global $product;

    if ( ! $product ) return;

    $average_rating = $product->get_average_rating();
    $review_count   = $product->get_review_count();

    if ( $review_count > 0 && $average_rating > 3 ) {

        $percentage = ( $average_rating / 5 ) * 100;

        echo '
        <div class="star-fix-outer-wrapper">
            <div class="star-fix-container" title="Average Rating: '.$average_rating.'">
                <span class="star-fix-base">★★★★★</span>
                <span class="star-fix-fill" style="width:'.$percentage.'%;">★★★★★</span>
            </div>
        </div>';
    }
}




/**
 * Hide the entire rating and review link if the average rating is 3 or less.
 */
add_filter('woocommerce_product_get_rating_html', 'hide_rating_and_reviews_under_three', 10, 3);

function hide_rating_and_reviews_under_three($html, $rating, $count) {
    global $product;

    if (!$product) {
        return $html;
    }

    $average = $product->get_average_rating();

    if ($average <= 3) {
        add_filter('woocommerce_product_description_tabs', 'remove_reviews_tab_if_low_rating');

        return '';
    }

    return $html;
}




/**
 * Specifically targets and hides the 'customer reviews' link
 * next to the stars if average rating is <= 3
 */
add_action('wp_head', 'hide_review_link_via_css_conditionally');

function hide_review_link_via_css_conditionally() {
    if (is_product()) {
        global $product;
        if ($product && $product->get_average_rating() <= 3) {
            echo '<style>.woocommerce-product-rating { display: none !important; }</style>';
        }
    }
}





add_action('wp_head', 'salient_fix_review_form_css');
function salient_fix_review_form_css() {
    echo '<style type="text/css">
        #review_form_wrapper {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        #review_form_wrapper.show-now {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
            margin-top: 30px !important;
        }
    </style>';
}
add_action('wp_footer', 'salient_fix_review_form_js', 999);
function salient_fix_review_form_js() {
    ?>
    <script type="text/javascript">
        (function() {
            // Function to handle the click
            const handleReviewClick = function(e) {
                const btn = e.target.closest('.nectar-product-reviews-trigger');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();

                    const wrapper = document.getElementById('review_form_wrapper');
                    if (wrapper) {
                        wrapper.classList.toggle('show-now');

                        if (wrapper.classList.contains('show-now')) {
                            wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            };

            document.addEventListener('click', handleReviewClick, true);
        })();
    </script>
    <?php
}




/**
 * Break WooCommerce product titles into two lines if they have 5 or more words.
 */
add_filter( 'the_title', 'custom_multiline_product_titles', 10, 2 );
function custom_multiline_product_titles( $title, $id ) {
    if ( !is_admin() && get_post_type($id) === 'product' && in_the_loop() ) {
        $words = explode( ' ', $title );

        if ( count( $words ) >= 5 ) {
            $chunks = array_chunk( $words, 4 );
            $formatted_chunks = array();

            foreach ( $chunks as $chunk ) {
                $formatted_chunks[] = implode( ' ', $chunk );
            }
            return implode( '<br>', $formatted_chunks );
        }
    }
    return $title;
}




/**
 * ADMIN: CUSTOM PRODUCT WARRANTY FIELDS
**/
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_text_input([
        'id'    => '_warranty',
        'label' => 'Warranty (years)',
        'description' => 'Enter the number of years for the product warranty.',
        'desc_tip'    => true
    ]);
});

add_action('woocommerce_process_product_meta', function($post_id) {
    if (isset($_POST['_warranty'])) {
        update_post_meta($post_id, '_warranty', sanitize_text_field($_POST['_warranty']));
    }
});




/**
 * UNIFIED PRODUCT LAYOUT SYSTEM
 * This code reorganizes the product page into a clean, professional flow:
 * 1. Shows Star Ratings at the very top.
 * 2. Displays a custom "In Stock" and "Warranty" status bar.
 * 3. Shows the Short Description.
 * 4. Displays the Price, Variation Dropdowns, and Add to Cart button for ALL products.
 */

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

add_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 5);

add_action('woocommerce_single_product_summary', function() {
    global $product;
    if (!$product) return;

    $stock_text = $product->is_in_stock() ? 'In Stock' : 'Out of Stock';
    $warranty   = get_post_meta($product->get_id(), '_warranty', true);
    $dot_color  = $product->is_in_stock() ? '#2ecc71' : '#e74c3c';

    echo '<div class="mmx-status-bar" style="display:flex; gap:15px; font-size:14px; margin-bottom:15px; font-weight:500;">';
    echo '<span style="color:'.$dot_color.';">● ' . esc_html($stock_text) . '</span>';

    if (!empty($warranty)) {
        echo '<span style="color:#666; border-left:1px solid #ddd; padding-left:15px;">' . esc_html($warranty) . ' Year Warranty</span>';
    }
    echo '</div>';
}, 11);

add_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 15);

function mmx_unified_add_to_cart() {
    global $product;
    if ( ! $product ) return;

    if ( $product->is_purchasable() && $product->is_in_stock() ) {
        woocommerce_template_single_add_to_cart();
    }
}
add_action('woocommerce_single_product_summary', 'mmx_unified_add_to_cart', 19);




// ==============================================
// Custom WooCommerce Product Tabs with WYSIWYG
// ==============================================
add_action('add_meta_boxes', 'add_custom_product_tabs_metabox');
function add_custom_product_tabs_metabox() {
    add_meta_box(
        'custom_product_tabs',
        __('Custom Product Tabs', 'your-textdomain'),
        'render_custom_product_tabs_metabox',
        'product',
        'normal',
        'high'
    );
}

function render_custom_product_tabs_metabox($post) {
    wp_nonce_field('save_custom_product_tabs', 'custom_product_tabs_nonce');

    $tabs = [
        'overview' => __('Overview', 'your-textdomain'),
        'technical' => __('Technical & Specialization', 'your-textdomain'),
        'shipping' => __('Shipping & Warranty', 'your-textdomain')
    ];

    foreach ($tabs as $key => $label) {
        $content = get_post_meta($post->ID, "_tab_{$key}", true);
        echo '<p><strong>' . esc_html($label) . '</strong></p>';
        wp_editor(
            $content,
            "tab_{$key}_editor",
            [
                'textarea_name' => "tab_{$key}",
                'textarea_rows' => 8,
                'media_buttons' => true,
                'teeny' => false,
                'tinymce' => true,
            ]
        );
        echo '<hr>';
    }
}

add_action('save_post', 'save_custom_product_tabs');
function save_custom_product_tabs($post_id) {
    if (!isset($_POST['custom_product_tabs_nonce']) || !wp_verify_nonce($_POST['custom_product_tabs_nonce'], 'save_custom_product_tabs')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $keys = ['overview', 'technical', 'shipping'];
    foreach ($keys as $key) {
        if (isset($_POST["tab_{$key}"])) {
            update_post_meta($post_id, "_tab_{$key}", wp_kses_post($_POST["tab_{$key}"]));
        }
    }
}

add_filter('woocommerce_product_tabs', 'replace_woocommerce_tabs', 98);
function replace_woocommerce_tabs($tabs) {
    global $product;

    $tabs = [];

    $custom_tabs = [
        'overview' => __('Overview', 'your-textdomain'),
        'technical' => __('Technical & Specialization', 'your-textdomain'),
        'shipping' => __('Shipping & Warranty', 'your-textdomain')
    ];

    foreach ($custom_tabs as $key => $title) {
        $content = get_post_meta($product->get_id(), "_tab_{$key}", true);
        if ($content) {
            $tabs["{$key}_tab"] = [
                'title'    => $title,
                'priority' => ($key === 'overview') ? 5 : (($key === 'technical') ? 10 : 15),
                'callback' => function() use ($key, $product) {
                    echo wpautop(get_post_meta($product->get_id(), "_tab_{$key}", true));
                }
            ];
        }
    }

    return $tabs;
}

function kill_all_duplicate_tabs() {
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_output_product_data_tabs', 60 );
}
add_action( 'wp', 'kill_all_duplicate_tabs' );




/**
 * PRODUCT FAQ: Backend Meta Box Logic
 */
add_action( 'add_meta_boxes', 'mmx_add_product_faq_meta_box' );
function mmx_add_product_faq_meta_box() {
    add_meta_box('mmx_product_faqs', 'Product Frequently Asked Questions', 'mmx_render_faq_meta_box', 'product', 'normal', 'high');
}

function mmx_render_faq_meta_box( $post ) {
    $defaults = array(
        array('q' => 'What you’re performing routine skin checks or evaluating complex lesions?', 'a' => 'Our systems provide high-resolution imaging to assist in both routine checks and detailed lesion analysis.'),
        array('q' => 'What are the key indicators to look for during a skin examination?', 'a' => 'Key indicators include asymmetry, border irregularity, color variation, diameter, and evolving features.'),
        array('q' => 'How do you differentiate between benign and malignant lesions?', 'a' => 'Using MoleMax\'s polarized light and high magnification, clinicians can visualize subsurface structures.')
    );

    $faqs = get_post_meta( $post->ID, '_product_faqs', true );
    if ( empty( $faqs ) ) { $faqs = $defaults; }

    wp_nonce_field( 'mmx_faq_save', 'mmx_faq_nonce' );
    ?>
    <table class="widefat fixed striped" id="faq-table">
        <thead>
            <tr>
                <th style="width: 30%;">Question</th>
                <th>Answer</th>
                <th style="width: 50px;"></th>
            </tr>
        </thead>
        <tbody id="faq-tbody">
            <?php foreach ( $faqs as $index => $faq ) : ?>
                <tr>
                    <td><input type="text" name="faq_q[]" value="<?php echo esc_attr( $faq['q'] ); ?>" style="width:100%;" /></td>
                    <td><textarea name="faq_a[]" rows="2" style="width:100%;"><?php echo esc_textarea( $faq['a'] ); ?></textarea></td>
                    <td><a href="#" class="remove-faq-row button" style="color:#a00; border-color:#a00;">×</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" id="add-faq-row" class="button button-primary" style="margin-top: 10px;">+ Add New FAQ</button>

    <script>
        jQuery(document).ready(function($){
            $('#add-faq-row').on('click', function(e){
                e.preventDefault();
                $('#faq-tbody').append('<tr><td><input type="text" name="faq_q[]" style="width:100%;" /></td><td><textarea name="faq_a[]" rows="2" style="width:100%;"></textarea></td><td><a href="#" class="remove-faq-row button" style="color:#a00; border-color:#a00;">×</a></td></tr>');
            });
            $('#faq-tbody').on('click', '.remove-faq-row', function(e){
                e.preventDefault();
                $(this).closest('tr').remove();
            });
        });
    </script>
    <?php
}

add_action( 'save_post_product', 'mmx_save_product_faq_data' );
function mmx_save_product_faq_data( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['mmx_faq_nonce'] ) || ! wp_verify_nonce( $_POST['mmx_faq_nonce'], 'mmx_faq_save' ) ) return;
    if ( ! current_user_can( 'edit_product', $post_id ) ) return;

    $faq_data = array();
    if ( isset( $_POST['faq_q'] ) && is_array($_POST['faq_q']) ) {
        foreach ( $_POST['faq_q'] as $i => $question ) {
            if ( empty( $question ) ) continue;
            $faq_data[] = array(
                'q' => sanitize_text_field( $question ),
                'a' => isset($_POST['faq_a'][$i]) ? wp_kses_post( $_POST['faq_a'][$i] ) : ''
            );
        }
    }
    update_post_meta( $post_id, '_product_faqs', $faq_data );
}

add_action( 'woocommerce_after_single_product_summary', 'mmx_fast_faq_frontend', 15 );
function mmx_fast_faq_frontend() {
    global $post;
    $faqs = get_post_meta( $post->ID, '_product_faqs', true );

    if ( empty( $faqs ) ) return;

    ?>
    <section class="mmx-faq-section">
        <div class="container">
            <h2 class="faq-main-title">Frequently Asked Questions (FAQs)</h2>
            <div class="mmx-accordion">
                <?php foreach ( $faqs as $index => $faq ) : ?>
                    <div class="accordion-item">
                        <input type="checkbox" id="faq-<?php echo $index; ?>" class="accordion-toggle">
                        <label for="faq-<?php echo $index; ?>" class="accordion-header">
                            <span class="q-text"><?php echo ($index + 1) . '. ' . esc_html( $faq['q'] ); ?></span>
                            <span class="chevron"></span>
                        </label>
                        <div class="accordion-panel">
                            <div class="panel-inner">
                                <?php echo wpautop( wp_kses_post( $faq['a'] ) ); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

add_action( 'woocommerce_after_single_product_summary', 'comments_template', 20 );




/**
 * Advanced fix to prevent GPay/Stripe reload loops on quantity change
 */
add_action('wp_footer', 'restrict_quantity_input_script_v2');
function restrict_quantity_input_script_v2() {
    if ( is_product() ) {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const qtyInput = document.querySelector('input.qty');

                if (qtyInput) {
                    qtyInput.addEventListener('input', function(e) {
                        e.stopImmediatePropagation();

                        if (this.value !== '' && parseFloat(this.value) < 1) {
                            this.value = 1;
                        }
                    }, true);

                    qtyInput.addEventListener('keydown', function(e) {
                        const invalidChars = ['-', '+', 'e', 'E'];
                        if (invalidChars.includes(e.key)) {
                            e.preventDefault();
                        }
                    });

                    qtyInput.addEventListener('blur', function() {
                        if (this.value === '' || parseFloat(this.value) < 1) {
                            this.value = 1;
                        }

                        const event = new Event('change', { bubbles: true });
                        this.dispatchEvent(event);
                    });
                }
            });
        </script>
        <?php
    }
}




/*
 * Description: Prevents Express Payment button reloads and suppresses variation selection errors globally.
 */

add_filter('woocommerce_add_error', function($error) {
    if (strpos($error, 'Please choose product options') !== false) {
        return false;
    }
    return $error;
}, 10, 1);

add_action('wp_footer', 'molemax_payment_reload_prevention');
function molemax_payment_reload_prevention() {
    if ( ! is_product() ) return;
    ?>
    <script type="text/javascript">
    (function($) {
        'use strict';
        $(document).ready(function() {
            var target = document.querySelector('#wc-stripe-express-checkout-element');
            if (!target) return;

            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.attributeName === 'class') {
                        $(target).removeClass('processing').css('opacity', '1');
                        $('.blockUI').remove();
                    }
                });
            });

            observer.observe(target, { attributes: true, childList: true, subtree: true });

            $(document.body).on('update_checkout', function(e) {
                e.stopImmediatePropagation();
            });

            $('.variations_form').on('show_variation', function() {
                $('.woocommerce-variation-availability, .single_variation_wrap .woocommerce-variation-description').hide();
            });
        });
    })(jQuery);
    </script>
    <?php
}




/*------------------
 * BRINGING THE PAYPAL IN THE SAME LINE WITH THE STRIPE AND GPAY
 * -------------------
*/
add_action('wp_footer', 'align_payment_buttons_script');
function align_payment_buttons_script() {
    if ( is_product() ) {
        ?>
        <script type="text/javascript">
            (function($) {
                $(window).on('load', function() {
                    var $stripeBox = $('#wc-stripe-express-checkout-element');
                    var $paypalBox = $('.ppc-button-wrapper');

                    if ($stripeBox.length && $paypalBox.length) {
                        $paypalBox.appendTo($stripeBox);

                        $stripeBox.css({
                            'display': 'flex',
                            'flex-direction': 'row',
                            'align-items': 'center',
                            'gap': '10px',
                            'width': '100%',
                            'clear': 'none'
                        });

                        $('#wc-stripe-express-checkout-element-googlePay, #wc-stripe-express-checkout-element-link, .ppc-button-wrapper').css({
                            'flex': '1',
                            'min-width': '120px',
                            'max-width': '160px',
                            'margin': '0'
                        });
                    }
                });
            })(jQuery);
        </script>
        <?php
    }
}




/**
 * PRODUCT TRUST BADGES SYSTEM
 */
add_action( 'admin_enqueue_scripts', 'trust_badges_admin_scripts' );
function trust_badges_admin_scripts( $hook ) {
    if ( 'post.php' != $hook && 'post-new.php' != $hook ) return;
    wp_enqueue_media();
}

add_action( 'add_meta_boxes', 'add_product_trust_meta_box' );
function add_product_trust_meta_box() {
    add_meta_box(
        'product_trust_badges',
        'Product Trust Badges',
        'render_product_trust_meta_box',
        'product',
        'normal',
        'high'
    );
}

function render_product_trust_meta_box( $post ) {
    $heading = get_post_meta( $post->ID, '_trust_heading', true );
    $logo_1  = get_post_meta( $post->ID, '_trust_logo_1', true );
    $logo_2  = get_post_meta( $post->ID, '_trust_logo_2', true );

    wp_nonce_field( 'save_trust_meta', 'trust_meta_nonce' );
    ?>
    <div class="trust-badge-admin-wrapper" style="padding: 10px 0;">
        <div style="margin-bottom: 20px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">Section Heading:</label>
            <input type="text" name="trust_heading" value="<?php echo esc_attr( $heading ); ?>" class="widefat" placeholder="e.g. Trusted by leading clinics and institutions">
        </div>

        <div style="display: flex; gap: 30px;">
            <div class="logo-upload-field" style="flex: 1;">
                <label style="font-weight:bold; display:block; margin-bottom:8px;">Clinic Logo 1</label>
                <div class="image-preview" id="preview_1" style="background:#f0f0f0; border:1px dashed #ccc; height:100px; display:flex; align-items:center; justify-content:center; margin-bottom:10px; overflow:hidden;">
                    <?php if($logo_1): ?>
                        <img src="<?php echo esc_url($logo_1); ?>" style="max-height:100%; max-width:100%;">
                    <?php else: ?>
                        <span style="color:#999;">No image selected</span>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="trust_logo_1" id="logo_url_1" value="<?php echo esc_attr( $logo_1 ); ?>">
                <button type="button" class="upload_image_button button" data-target="1">Select Image</button>
                <button type="button" class="remove_image_button button" data-target="1" style="<?php echo !$logo_1 ? 'display:none;' : ''; ?>">Remove</button>
            </div>

            <div class="logo-upload-field" style="flex: 1;">
                <label style="font-weight:bold; display:block; margin-bottom:8px;">Clinic Logo 2</label>
                <div class="image-preview" id="preview_2" style="background:#f0f0f0; border:1px dashed #ccc; height:100px; display:flex; align-items:center; justify-content:center; margin-bottom:10px; overflow:hidden;">
                    <?php if($logo_2): ?>
                        <img src="<?php echo esc_url($logo_2); ?>" style="max-height:100%; max-width:100%;">
                    <?php else: ?>
                        <span style="color:#999;">No image selected</span>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="trust_logo_2" id="logo_url_2" value="<?php echo esc_attr( $logo_2 ); ?>">
                <button type="button" class="upload_image_button button" data-target="2">Select Image</button>
                <button type="button" class="remove_image_button button" data-target="2" style="<?php echo !$logo_2 ? 'display:none;' : ''; ?>">Remove</button>
            </div>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('.upload_image_button').click(function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            var button = $(this);
            var custom_uploader = wp.media({
                title: 'Select Clinic Logo',
                button: { text: 'Use this Logo' },
                multiple: false
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#logo_url_' + target).val(attachment.url);
                $('#preview_' + target).html('<img src="'+attachment.url+'" style="max-height:100%; max-width:100%;">');
                button.next('.remove_image_button').show();
            }).open();
        });

        $('.remove_image_button').click(function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $('#logo_url_' + target).val('');
            $('#preview_' + target).html('<span style="color:#999;">No image selected</span>');
            $(this).hide();
        });
    });
    </script>
    <?php
}

add_action( 'save_post_product', 'save_product_trust_meta' );
function save_product_trust_meta( $post_id ) {
    if ( ! isset( $_POST['trust_meta_nonce'] ) || ! wp_verify_nonce( $_POST['trust_meta_nonce'], 'save_trust_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    update_post_meta( $post_id, '_trust_heading', sanitize_text_field( $_POST['trust_heading'] ) );
    update_post_meta( $post_id, '_trust_logo_1', esc_url_raw( $_POST['trust_logo_1'] ) );
    update_post_meta( $post_id, '_trust_logo_2', esc_url_raw( $_POST['trust_logo_2'] ) );
}

add_action( 'woocommerce_single_product_summary', 'display_native_trust_section', 60 );
function display_native_trust_section() {
    global $product;
    $id = $product->get_id();
    $h  = get_post_meta( $id, '_trust_heading', true );
    $l1 = get_post_meta( $id, '_trust_logo_1', true );
    $l2 = get_post_meta( $id, '_trust_logo_2', true );

    if ( ! $l1 && ! $l2 ) return;
    ?>
    <div class="product-trust-badges">
        <?php if ( $h ) : ?>
            <h4 class="trust-badges-title"><?php echo esc_html( $h ); ?></h4>
        <?php endif; ?>

        <div class="trust-logos-flex">
            <?php if ( $l1 ) : ?>
                <div class="trust-logo-item logo-1">
                    <img src="<?php echo esc_url( $l1 ); ?>" alt="Clinic Logo 1">
                </div>
            <?php endif; ?>

            <?php if ( $l2 ) : ?>
                <div class="trust-logo-item logo-2">
                    <img src="<?php echo esc_url( $l2 ); ?>" alt="Clinic Logo 2">
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}




/**
 * STATIC PRODUCT DETAILS SECTION
 */
add_action( 'woocommerce_single_product_summary', 'display_product_details_right_side', 35 );
function display_product_details_right_side() {
    ?>
    <div class="summary-flex-container">
        <div class="summary-left-side"></div>

        <div class="summary-right-side">
            <div class="product-details-sidebox">
                <ul class="details-list">
                    <li class="details-item"><span class="check-icon"></span><span class="item-text">Shipping within 1-2 business days</span></li>
                    <li class="details-item"><span class="check-icon"></span><span class="item-text">30-day returns</span></li>
                    <li class="details-item"><span class="check-icon"></span><span class="item-text">Free Shipping on Orders Over $500 (Domestic Metro Areas)</span></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}




/**
 * DISPLAY THE NATIVE WOOCOMMERCE PRICE AFTER THE PRODUCT TITLE
 *
 * Uses WooCommerce's standard price output to preserve compatibility
 * with country-based pricing, sales, taxes, currency and AJAX updates.
 */
remove_action(
    'woocommerce_single_product_summary',
    'woocommerce_template_single_price',
    10
);

add_action(
    'woocommerce_single_product_summary',
    'woocommerce_template_single_price',
    6
);




/**
 * UNIFIED WOOCOMMERCE GALLERY OVERRIDE V15
 *
 * Synchronizes the thumbnail container height with the main
 * product gallery viewport on desktop.
 */
add_action(
    'woocommerce_product_thumbnails',
    'strictly_conditional_thumb_layout_v15',
    40
);

function strictly_conditional_thumb_layout_v15(): void
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            'use strict';

            function syncGallery() {
                var $gallery = $('.woocommerce-product-gallery');
                var $thumbs = $gallery.find(
                    '.flex-control-nav.flex-control-thumbs'
                );
                var $viewport = $gallery.find('.flex-viewport');

                if (!$thumbs.length || !$viewport.length) {
                    return;
                }

                if ($(window).width() > 768) {
                    var mainHeight = $viewport.outerHeight();

                    $thumbs.css({
                        height: mainHeight + 'px',
                        maxHeight: mainHeight + 'px',
                        display: 'flex'
                    });

                    $thumbs.off('touchmove.mmxGallery');
                } else {
                    $thumbs.css({
                        height: 'auto',
                        maxHeight: 'none',
                        display: 'block'
                    });

                    $thumbs
                        .off('touchmove.mmxGallery')
                        .on('touchmove.mmxGallery', function (event) {
                            event.stopPropagation();
                        });
                }
            }

            var galleryResizeTimer;

            $(window).on('load.mmxGallery resize.mmxGallery', function () {
                clearTimeout(galleryResizeTimer);

                galleryResizeTimer = setTimeout(
                    syncGallery,
                    600
                );
            });

            $('.woocommerce-product-gallery').on(
                'wc-product-gallery-after-init.mmxGallery',
                function () {
                    syncGallery();
                }
            );

            syncGallery();
        });
    </script>
    <?php
}

/*
 * END PRODUCT DESIGN CHANGES
 */
