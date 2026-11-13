<?php
/**
 * Plugin Name: Post Series
 * Plugin URI:  https://github.com/Azanumer/wp-post-series
 * Description: Group posts into a series with an automatic navigation box, prev/next links and a series shortcode.
 * Version:     1.0.0
 * Author:      Azan Umer
 * Author URI:  https://azanumer.cv
 * License:     MIT
 * Text Domain: wp-post-series
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPSERIES_VERSION', '1.0.0' );
define( 'WPSERIES_TAXONOMY', 'post_series' );

/**
 * Register the "Series" taxonomy for posts.
 */
function wpseries_register_taxonomy() {
    register_taxonomy(
        WPSERIES_TAXONOMY,
        'post',
        array(
            'labels'            => array(
                'name'          => __( 'Series', 'wp-post-series' ),
                'singular_name' => __( 'Series', 'wp-post-series' ),
                'add_new_item'  => __( 'Add New Series', 'wp-post-series' ),
                'edit_item'     => __( 'Edit Series', 'wp-post-series' ),
                'all_items'     => __( 'All Series', 'wp-post-series' ),
            ),
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'hierarchical'      => false,
            'rewrite'           => array( 'slug' => 'series' ),
        )
    );
}
add_action( 'init', 'wpseries_register_taxonomy' );

/**
 * Default settings.
 *
 * @return array
 */
function wpseries_get_settings() {
    $defaults = array(
        'position'   => 'after', // before | after | both
        'show_count' => 1,
        'title'      => __( 'Part of the series:', 'wp-post-series' ),
    );

    return wp_parse_args( get_option( 'wpseries_settings', array() ), $defaults );
}

/**
 * Get the ordered posts of a series term (oldest first).
 *
 * @param int $term_id Series term ID.
 * @return WP_Post[]
 */
function wpseries_get_series_posts( $term_id ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => WPSERIES_TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => (int) $term_id,
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    );

    return $query->posts;
}

/**
 * Render the series navigation box for a post.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return string HTML (empty when the post belongs to no series).
 */
function wpseries_render_box( $post_id = 0 ) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();
    if ( ! $post_id ) {
        return '';
    }

    $terms = get_the_terms( $post_id, WPSERIES_TAXONOMY );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    $settings = wpseries_get_settings();
    $out      = '';

    foreach ( $terms as $term ) {
        $posts = wpseries_get_series_posts( $term->term_id );
        if ( count( $posts ) < 2 ) {
            continue; // A series of one post needs no navigation.
        }

        $current_index = -1;
        foreach ( $posts as $i => $p ) {
            if ( (int) $p->ID === (int) $post_id ) {
                $current_index = $i;
                break;
            }
        }

        $out .= '<aside class="wpseries-box">';
        $out .= '<h3 class="wpseries-title">' . esc_html( $settings['title'] ) . ' ';
        $out .= '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
        if ( $settings['show_count'] ) {
            /* translators: %d: number of posts in the series. */
            $out .= ' <span class="wpseries-count">(' . sprintf( esc_html__( '%d parts', 'wp-post-series' ), count( $posts ) ) . ')</span>';
        }
        $out .= '</h3><ol class="wpseries-list">';

        foreach ( $posts as $i => $p ) {
            $out .= '<li class="wpseries-item' . ( $i === $current_index ? ' wpseries-current' : '' ) . '">';
            if ( $i === $current_index ) {
                $out .= '<strong>' . esc_html( get_the_title( $p ) ) . '</strong>';
            } else {
                $out .= '<a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>';
            }
            $out .= '</li>';
        }

        $out .= '</ol>';

        // Prev / next links inside the series.
        $prev = $current_index > 0 ? $posts[ $current_index - 1 ] : null;
        $next = ( $current_index >= 0 && $current_index < count( $posts ) - 1 ) ? $posts[ $current_index + 1 ] : null;
        if ( $prev || $next ) {
            $out .= '<nav class="wpseries-nav">';
            if ( $prev ) {
                $out .= '<a class="wpseries-prev" href="' . esc_url( get_permalink( $prev ) ) . '">&larr; ' . esc_html( get_the_title( $prev ) ) . '</a>';
            }
            if ( $next ) {
                $out .= '<a class="wpseries-next" href="' . esc_url( get_permalink( $next ) ) . '">' . esc_html( get_the_title( $next ) ) . '</a> &rarr;';
            }
            $out .= '</nav>';
        }

        $out .= '</aside>';
    }

    return $out;
}

/**
 * Auto-append (or prepend) the series box to single post content.
 *
 * @param string $content Post content.
 * @return string
 */
function wpseries_append_to_content( $content ) {
    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $settings = wpseries_get_settings();
    $box      = wpseries_render_box();
    if ( '' === $box ) {
        return $content;
    }

    if ( 'before' === $settings['position'] ) {
        return $box . $content;
    }
    if ( 'both' === $settings['position'] ) {
        return $box . $content . $box;
    }
    return $content . $box;
}
add_filter( 'the_content', 'wpseries_append_to_content' );

/**
 * [post_series] shortcode — renders the box for the current post.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function wpseries_shortcode( $atts ) {
    $atts = shortcode_atts(
        array( 'id' => 0 ),
        $atts,
        'post_series'
    );

    return wpseries_render_box( (int) $atts['id'] );
}
add_shortcode( 'post_series', 'wpseries_shortcode' );

/**
 * [series_nav] shortcode — prev/next links only.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function wpseries_nav_shortcode( $atts ) {
    $atts    = shortcode_atts( array( 'id' => 0 ), $atts, 'series_nav' );
    $post_id = $atts['id'] ? (int) $atts['id'] : get_the_ID();
    $box     = wpseries_render_box( $post_id );

    // Reuse the rendered box but keep only its <nav> part.
    if ( preg_match( '/<nav class="wpseries-nav">.*?<\/nav>/s', $box, $m ) ) {
        return $m[0];
    }
    return '';
}
add_shortcode( 'series_nav', 'wpseries_nav_shortcode' );

/**
 * Minimal inline styling so the box looks decent on any theme.
 */
function wpseries_inline_styles() {
    if ( ! is_singular( 'post' ) ) {
        return;
    }
    echo '<style>.wpseries-box{border:1px solid #ddd;border-left:4px solid #2271b1;padding:1em 1.25em;margin:1.5em 0;background:#f9f9f9}.wpseries-title{margin:0 0 .5em;font-size:1.1em}.wpseries-list{margin:0 0 .5em 1.25em}.wpseries-current{font-weight:700}.wpseries-nav{display:flex;justify-content:space-between;gap:1em;margin-top:.75em}</style>';
}
add_action( 'wp_head', 'wpseries_inline_styles' );

/**
 * Settings page under Settings → Post Series.
 */
function wpseries_register_settings() {
    register_setting( 'wpseries_settings_group', 'wpseries_settings', 'wpseries_sanitize_settings' );

    add_settings_section( 'wpseries_main', __( 'Display', 'wp-post-series' ), '__return_false', 'wpseries' );

    add_settings_field(
        'wpseries_position',
        __( 'Box position', 'wp-post-series' ),
        'wpseries_field_position',
        'wpseries',
        'wpseries_main'
    );
    add_settings_field(
        'wpseries_show_count',
        __( 'Show part count', 'wp-post-series' ),
        'wpseries_field_show_count',
        'wpseries',
        'wpseries_main'
    );
}
add_action( 'admin_init', 'wpseries_register_settings' );

/**
 * Sanitize settings input.
 *
 * @param array $input Raw input.
 * @return array
 */
function wpseries_sanitize_settings( $input ) {
    $out = array();
    $out['position']   = in_array( $input['position'], array( 'before', 'after', 'both' ), true ) ? $input['position'] : 'after';
    $out['show_count'] = ! empty( $input['show_count'] ) ? 1 : 0;
    $out['title']      = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';
    return $out;
}

function wpseries_field_position() {
    $s = wpseries_get_settings();
    foreach ( array( 'before' => 'Before content', 'after' => 'After content', 'both' => 'Both' ) as $value => $label ) {
        printf(
            '<label><input type="radio" name="wpseries_settings[position]" value="%s" %s> %s</label><br>',
            esc_attr( $value ),
            checked( $s['position'], $value, false ),
            esc_html( $label )
        );
    }
}

function wpseries_field_show_count() {
    $s = wpseries_get_settings();
    printf(
        '<label><input type="checkbox" name="wpseries_settings[show_count]" value="1" %s> %s</label>',
        checked( $s['show_count'], 1, false ),
        esc_html__( 'Show "(N parts)" next to the series name', 'wp-post-series' )
    );
}

function wpseries_add_menu() {
    add_options_page(
        __( 'Post Series', 'wp-post-series' ),
        __( 'Post Series', 'wp-post-series' ),
        'manage_options',
        'wpseries',
        'wpseries_settings_page'
    );
}
add_action( 'admin_menu', 'wpseries_add_menu' );

function wpseries_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Post Series', 'wp-post-series' ); ?></h1>
        <p><?php esc_html_e( 'Assign posts to a series from the post editor (Series box in the sidebar). The navigation box appears automatically on single posts.', 'wp-post-series' ); ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wpseries_settings_group' );
            do_settings_sections( 'wpseries' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
