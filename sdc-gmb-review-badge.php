<?php
/**
 * Plugin Name: SDC GMB Review Badge
 * Plugin URI: https://stokedesign.co
 * Description: Display a Google Business Profile rating badge anywhere on your site via shortcode with configurable styling, caching, and accessible markup.
 * Version: 1.1.0
 * Author: Stoke Design Co
 * Author URI: https://stokedesign.co
 * Text Domain: sdc-gmb-review-badge
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load plugin text domain.
 */
function sdc_gmb_load_textdomain() {
    load_plugin_textdomain( 'sdc-gmb-review-badge', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'sdc_gmb_load_textdomain' );

/**
 * Retrieve default options.
 *
 * @return array
 */
function sdc_gmb_get_default_options() {
    return array(
        'api_key'       => '',
        'place_id'      => '',
        'stars'         => 5,
        'star_color'    => '#E9966F',
        'accent_color'  => '#1E2A3A',
        'cache_minutes' => 720,
    );
}

/**
 * Retrieve saved options merged with defaults.
 *
 * @return array
 */
function sdc_gmb_get_options() {
    $options = get_option( 'sdc_gmb_review_badge_options', array() );

    if ( ! is_array( $options ) || empty( $options ) ) {
        $legacy_keys = array(
            'sdc_gmb_rating_options',
            'stoke_gbp_rating_options',
        );

        foreach ( $legacy_keys as $legacy_key ) {
            $legacy = get_option( $legacy_key );

            if ( is_array( $legacy ) && ! empty( $legacy ) ) {
                $options = $legacy;
                break;
            }
        }
    }

    if ( ! is_array( $options ) ) {
        $options = array();
    }

    return wp_parse_args( $options, sdc_gmb_get_default_options() );
}

/**
 * Register settings and fields.
 */
function sdc_gmb_register_settings() {
    register_setting( 'sdc_gmb_review_badge_options_group', 'sdc_gmb_review_badge_options', 'sdc_gmb_sanitize_options' );

    add_settings_section(
        'sdc_gmb_review_badge_section',
        __( 'Google Business Profile', 'sdc-gmb-review-badge' ),
        '__return_false',
        'sdc_gmb_review_badge'
    );

    add_settings_field(
        'api_key',
        __( 'Google Places API Key', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_api_key_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'place_id',
        __( 'Place ID', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_place_id_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'stars',
        __( 'Number of Stars to Display', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_stars_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'star_color',
        __( 'Star Colour', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_star_color_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'accent_color',
        __( 'Text and Icon Colour', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_accent_color_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );

    add_settings_field(
        'cache_minutes',
        __( 'Cache Duration (minutes)', 'sdc-gmb-review-badge' ),
        'sdc_gmb_render_cache_field',
        'sdc_gmb_review_badge',
        'sdc_gmb_review_badge_section'
    );
}
add_action( 'admin_init', 'sdc_gmb_register_settings' );

/**
 * Sanitize options before saving.
 *
 * @param array $input Raw input values.
 * @return array
 */
function sdc_gmb_sanitize_options( $input ) {
    $options   = sdc_gmb_get_options();
    $sanitized = array();

    $sanitized['api_key']       = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : $options['api_key'];
    $sanitized['place_id']      = isset( $input['place_id'] ) ? sanitize_text_field( $input['place_id'] ) : $options['place_id'];
    $sanitized['stars']         = isset( $input['stars'] ) ? absint( $input['stars'] ) : $options['stars'];
    $sanitized['star_color']    = isset( $input['star_color'] ) ? sanitize_hex_color( $input['star_color'] ) : $options['star_color'];
    $sanitized['accent_color']  = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : $options['accent_color'];
    $sanitized['cache_minutes'] = isset( $input['cache_minutes'] ) ? absint( $input['cache_minutes'] ) : $options['cache_minutes'];

    if ( $sanitized['stars'] < 1 ) {
        $sanitized['stars'] = 1;
    } elseif ( $sanitized['stars'] > 10 ) {
        $sanitized['stars'] = 10;
    }

    if ( empty( $sanitized['star_color'] ) ) {
        $sanitized['star_color'] = sdc_gmb_get_default_options()['star_color'];
    }

    if ( empty( $sanitized['accent_color'] ) ) {
        $sanitized['accent_color'] = sdc_gmb_get_default_options()['accent_color'];
    }

    return $sanitized;
}

/**
 * Render API key field.
 */
function sdc_gmb_render_api_key_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[api_key]" id="sdc-gmb-api-key" class="regular-text" value="%s" />',
        esc_attr( $options['api_key'] )
    );

    echo '<p class="description">' . esc_html__( 'Enter the restricted Google Places API key for your Google Business Profile.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render place ID field.
 */
function sdc_gmb_render_place_id_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[place_id]" id="sdc-gmb-place-id" class="regular-text" value="%s" />',
        esc_attr( $options['place_id'] )
    );

    echo '<p class="description">' . esc_html__( 'Paste the Place ID for your Google Business Profile location.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render stars field.
 */
function sdc_gmb_render_stars_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="number" min="1" max="10" name="sdc_gmb_review_badge_options[stars]" id="sdc-gmb-stars" value="%d" />',
        absint( $options['stars'] )
    );

    echo '<p class="description">' . esc_html__( 'Choose how many star icons appear in the badge (between 1 and 10).', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render star colour field.
 */
function sdc_gmb_render_star_color_field() {
    $options = sdc_gmb_get_options();
    $color   = sanitize_hex_color( $options['star_color'] );

    if ( ! $color ) {
        $color = sdc_gmb_get_default_options()['star_color'];
    }

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[star_color]" id="sdc-gmb-star-color" class="sdc-gmb-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_gmb_get_default_options()['star_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Select the colour used for the filled portion of the stars.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render accent colour field.
 */
function sdc_gmb_render_accent_color_field() {
    $options = sdc_gmb_get_options();
    $color   = sanitize_hex_color( $options['accent_color'] );

    if ( ! $color ) {
        $color = sdc_gmb_get_default_options()['accent_color'];
    }

    printf(
        '<input type="text" name="sdc_gmb_review_badge_options[accent_color]" id="sdc-gmb-accent-color" class="sdc-gmb-color-field" value="%s" data-default-color="%s" />',
        esc_attr( $color ),
        esc_attr( sdc_gmb_get_default_options()['accent_color'] )
    );

    echo '<p class="description">' . esc_html__( 'Select the colour used for the text and Google icon.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Render cache field.
 */
function sdc_gmb_render_cache_field() {
    $options = sdc_gmb_get_options();

    printf(
        '<input type="number" min="0" name="sdc_gmb_review_badge_options[cache_minutes]" id="sdc-gmb-cache" value="%d" />',
        absint( $options['cache_minutes'] )
    );

    echo '<p class="description">' . esc_html__( 'Cache the Google API response for this many minutes. Set to 0 to disable caching temporarily.', 'sdc-gmb-review-badge' ) . '</p>';
}

/**
 * Add settings page.
 */
function sdc_gmb_add_settings_page() {
    add_options_page(
        esc_html__( 'SDC GMB Review Badge', 'sdc-gmb-review-badge' ),
        esc_html__( 'SDC GMB Review Badge', 'sdc-gmb-review-badge' ),
        'manage_options',
        'sdc-gmb-review-badge',
        'sdc_gmb_render_settings_page'
    );
}
add_action( 'admin_menu', 'sdc_gmb_add_settings_page' );

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current admin page hook.
 */
function sdc_gmb_admin_enqueue_assets( $hook ) {
    if ( 'settings_page_sdc-gmb-review-badge' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".sdc-gmb-color-field").wpColorPicker();});' );
}
add_action( 'admin_enqueue_scripts', 'sdc_gmb_admin_enqueue_assets' );

/**
 * Render settings page markup.
 */
function sdc_gmb_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_SPECIAL_CHARS );

    if ( $updated ) {
        add_settings_error( 'sdc_gmb_messages', 'sdc_gmb_message', esc_html__( 'Settings saved.', 'sdc-gmb-review-badge' ), 'updated' );
    }

    settings_errors( 'sdc_gmb_messages' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'SDC GMB Review Badge', 'sdc-gmb-review-badge' ); ?></h1>
        <p><?php esc_html_e( 'Display a Google Business Profile reviews badge anywhere via the [sdc_gmb_review_badge] shortcode.', 'sdc-gmb-review-badge' ); ?></p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'sdc_gmb_review_badge_options_group' );
            do_settings_sections( 'sdc_gmb_review_badge' );
            submit_button( __( 'Save Changes', 'sdc-gmb-review-badge' ) );
            ?>
        </form>
        <hr />
        <h2><?php esc_html_e( 'Help', 'sdc-gmb-review-badge' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Create or select a Google Cloud project, enable the Places API, and generate a restricted API key.', 'sdc-gmb-review-badge' ); ?></li>
            <li><?php esc_html_e( 'Use the Google Place ID Finder to look up the Place ID for your business.', 'sdc-gmb-review-badge' ); ?></li>
            <li><?php esc_html_e( 'Enter both values above and save. Then add the shortcode to any page, post, or widget.', 'sdc-gmb-review-badge' ); ?></li>
        </ol>
        <h3><?php esc_html_e( 'Shortcode Examples', 'sdc-gmb-review-badge' ); ?></h3>
        <ul>
            <li><code>[sdc_gmb_review_badge]</code></li>
            <li><code>[sdc_gmb_review_badge stars="5" star_color="#ff9900"]</code></li>
            <li><code>[sdc_gmb_review_badge place_id="YOUR_PLACE_ID" api_key="YOUR_KEY"]</code></li>
            <li><code>[sdc_gmb_review_badge cache_minutes="0"]</code></li>
        </ul>
        <p><?php esc_html_e( 'Change the cache duration or pass cache_minutes="0" in the shortcode to refresh the data on the next render.', 'sdc-gmb-review-badge' ); ?></p>
    </div>
    <?php
}

/**
 * Fetch rating data from Google Places API with caching.
 *
 * @param string $place_id      Place ID.
 * @param string $api_key       API key.
 * @param int    $cache_minutes Cache duration in minutes.
 * @return array|WP_Error
 */
function sdc_gmb_get_rating_data( $place_id, $api_key, $cache_minutes ) {
    $place_id = trim( $place_id );
    $api_key  = trim( $api_key );

    if ( '' === $place_id || '' === $api_key ) {
        return new WP_Error( 'sdc_gmb_missing_config', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    $transient_key = 'sdc_gmb_review_badge_' . md5( $place_id );
    $cache_minutes = absint( $cache_minutes );

    if ( $cache_minutes > 0 ) {
        $cached = get_transient( $transient_key );

        if ( false !== $cached ) {
            return $cached;
        }
    }

    $url = esc_url_raw(
        add_query_arg(
            array(
                'place_id' => $place_id,
                'fields'   => 'rating,user_ratings_total',
                'key'      => $api_key,
            ),
            'https://maps.googleapis.com/maps/api/place/details/json'
        )
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 15,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );

    if ( 200 !== $code ) {
        return new WP_Error( 'sdc_gmb_http_error', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! is_array( $data ) ) {
        return new WP_Error( 'sdc_gmb_json_error', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    if ( isset( $data['status'] ) && 'OK' !== $data['status'] ) {
        return new WP_Error( 'sdc_gmb_api_status', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    if ( empty( $data['result']['rating'] ) || ! isset( $data['result']['user_ratings_total'] ) ) {
        return new WP_Error( 'sdc_gmb_missing_fields', __( 'Reviews unavailable', 'sdc-gmb-review-badge' ) );
    }

    $result = array(
        'rating'             => (float) $data['result']['rating'],
        'user_ratings_total' => (int) $data['result']['user_ratings_total'],
    );

    if ( $cache_minutes > 0 ) {
        set_transient( $transient_key, $result, $cache_minutes * MINUTE_IN_SECONDS );
    }

    return $result;
}

/**
 * Ensure front-end style is registered and enqueued.
 */
function sdc_gmb_enqueue_badge_style() {
    static $style_added = false;
    $handle             = 'sdc-gmb-review-badge';

    if ( ! wp_style_is( $handle, 'registered' ) ) {
        wp_register_style( $handle, false, array(), '1.1.0' );
    }

    if ( ! wp_style_is( $handle, 'enqueued' ) ) {
        wp_enqueue_style( $handle );
    }

    if ( ! $style_added ) {
        $css = '.sdc-gmb-review-badge{display:inline-flex;align-items:center;color:#1E2A3A;border-radius:9999px;padding:6px 12px;font-size:14px;line-height:1.2;font-weight:600;font-family:inherit;gap:10px;}' .
            '.sdc-gmb-review-badge .sdc-gmb-visual{display:inline-flex;align-items:center;gap:8px;}' .
            '.sdc-gmb-review-badge .sdc-gmb-icon-wrap{display:inline-flex;align-items:center;}' .
            '.sdc-gmb-review-badge .sdc-gmb-google-icon{width:22px;height:22px;flex-shrink:0;}' .
            '.sdc-gmb-review-badge .sdc-gmb-google-icon path{fill:currentColor;}' .
            '.sdc-gmb-review-badge .sdc-gmb-stars{display:inline-flex;gap:2px;}' .
            '.sdc-gmb-review-badge .sdc-gmb-star{width:16px;height:16px;display:block;}' .
            '.sdc-gmb-review-badge .sdc-gmb-star-bg{fill:#4d5d72;}' .
            '.sdc-gmb-review-badge .sdc-gmb-text{white-space:nowrap;}' .
            '.sdc-gmb-review-badge .sdc-gmb-text strong{font-weight:700;}';

        wp_add_inline_style( $handle, $css );
        $style_added = true;
    }
}

/**
 * Render stars markup.
 *
 * @param float  $rating     Rating value (0-5).
 * @param int    $star_count Number of stars to display.
 * @param string $star_color Hex colour for filled area.
 * @return string
 */
function sdc_gmb_get_stars_markup( $rating, $star_count, $star_color ) {
    $rating     = max( 0, (float) $rating );
    $star_count = max( 1, (int) $star_count );
    $per_star   = 5 / $star_count;
    $clip_base  = 'sdc-gmb-clip-' . wp_unique_id();
    $star_path  = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z';
    $gray       = '#4d5d72';

    $markup = '';

    for ( $i = 0; $i < $star_count; $i++ ) {
        $star_start = $i * $per_star;
        $progress   = ( $rating - $star_start ) / $per_star;
        $progress   = max( 0, min( 1, $progress ) );
        $clip_id    = $clip_base . '-' . $i;
        $width      = $progress * 100;

        $markup .= '<svg class="sdc-gmb-star" viewBox="0 0 24 24" aria-hidden="true" focusable="false">';
        $markup .= '<defs><clipPath id="' . esc_attr( $clip_id ) . '"><rect x="0" y="0" width="' . esc_attr( $width ) . '%" height="100%" /></clipPath></defs>';
        $markup .= '<path class="sdc-gmb-star-bg" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $gray ) . '" />';
        $markup .= '<path class="sdc-gmb-star-fill" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $star_color ) . '" clip-path="url(#' . esc_attr( $clip_id ) . ')" />';
        $markup .= '</svg>';
    }

    return $markup;
}

/**
 * Shortcode handler.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function sdc_gmb_render_badge_shortcode( $atts ) {
    $options = sdc_gmb_get_options();

    $atts = shortcode_atts(
        array(
            'place_id'      => $options['place_id'],
            'api_key'       => $options['api_key'],
            'stars'         => $options['stars'],
            'star_color'    => $options['star_color'],
            'accent_color'  => $options['accent_color'],
            'cache_minutes' => $options['cache_minutes'],
        ),
        $atts,
        'sdc_gmb_review_badge'
    );

    $place_id      = sanitize_text_field( $atts['place_id'] );
    $api_key       = sanitize_text_field( $atts['api_key'] );
    $star_count    = absint( $atts['stars'] );
    $cache_minutes = absint( $atts['cache_minutes'] );
    $star_color    = sanitize_hex_color( $atts['star_color'] );
    $accent_color  = sanitize_hex_color( $atts['accent_color'] );

    if ( $star_count < 1 ) {
        $star_count = 1;
    } elseif ( $star_count > 10 ) {
        $star_count = 10;
    }

    if ( empty( $star_color ) ) {
        $star_color = sdc_gmb_get_default_options()['star_color'];
    }

    if ( empty( $accent_color ) ) {
        $accent_color = sdc_gmb_get_default_options()['accent_color'];
    }

    $data      = sdc_gmb_get_rating_data( $place_id, $api_key, $cache_minutes );
    $has_error = is_wp_error( $data );

    $rating_value = $has_error ? 0 : (float) $data['rating'];
    $total        = $has_error ? 0 : (int) $data['user_ratings_total'];

    $aria_label = $has_error
        ? __( 'Google rating: Reviews unavailable', 'sdc-gmb-review-badge' )
        : sprintf(
            /* translators: 1: rating value, 2: total reviews */
            __( 'Google rating: %1$s out of 5 from %2$s reviews', 'sdc-gmb-review-badge' ),
            number_format_i18n( round( $rating_value, 1 ), 1 ),
            number_format_i18n( $total )
        );

    $stars_markup = sdc_gmb_get_stars_markup( $rating_value, $star_count, $star_color );

    sdc_gmb_enqueue_badge_style();

    $icon_svg = '<svg class="sdc-gmb-google-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<path fill="currentColor" d="M21.6 12.227c0-.74-.066-1.45-.19-2.14H12v4.05h5.44a4.65 4.65 0 0 1-2.02 3.05v2.53h3.27c1.92-1.77 3-4.38 3-7.49z" />'
        . '<path fill="currentColor" d="M12 22c2.7 0 4.96-.9 6.62-2.43l-3.27-2.53c-.91.61-2.07.97-3.35.97-2.58 0-4.77-1.74-5.55-4.07H2.97v2.56A9.99 9.99 0 0 0 12 22z" />'
        . '<path fill="currentColor" d="M6.45 13.94a6.004 6.004 0 0 1 0-3.88V7.5H2.97a10 10 0 0 0 0 8.99l3.48-2.55z" />'
        . '<path fill="currentColor" d="M12 6.38c1.47 0 2.79.5 3.83 1.47l2.86-2.86C16.96 2.92 14.7 2 12 2a9.99 9.99 0 0 0-9.03 5.5l3.48 2.56C7.23 8.12 9.42 6.38 12 6.38z" />'
        . '</svg>';

    if ( $has_error ) {
        $text_markup = '<span class="sdc-gmb-text">' . esc_html__( 'Reviews unavailable', 'sdc-gmb-review-badge' ) . '</span>';
    } else {
        $formatted_rating = number_format_i18n( round( $rating_value, 1 ), 1 );
        $reviews_text     = sprintf(
            _n( '%s Google review', '%s Google reviews', $total, 'sdc-gmb-review-badge' ),
            number_format_i18n( $total )
        );

        /* translators: %s: human-readable Google review count (e.g. "120 Google reviews"). */
        $summary_text = sprintf( __( 'out of 5 from %s', 'sdc-gmb-review-badge' ), $reviews_text );

        $text_markup = '<span class="sdc-gmb-text"><strong>' . esc_html( $formatted_rating ) . '</strong> ' . esc_html( $summary_text ) . '</span>';
    }

    $style_attr = ' style="color:' . esc_attr( $accent_color ) . ';"';

    $badge  = '<div class="sdc-gmb-review-badge"' . $style_attr . ' role="img" aria-label="' . esc_attr( $aria_label ) . '">';
    $badge .= '<span class="sdc-gmb-visual" aria-hidden="true">';
    $badge .= '<span class="sdc-gmb-icon-wrap">' . $icon_svg . '</span>';
    $badge .= '<span class="sdc-gmb-stars">' . $stars_markup . '</span>';
    $badge .= $text_markup;
    $badge .= '</span>';
    $badge .= '</div>';

    return $badge;
}
add_shortcode( 'sdc_gmb_review_badge', 'sdc_gmb_render_badge_shortcode' );
add_shortcode( 'sdc_gmb_badge', 'sdc_gmb_render_badge_shortcode' );
add_shortcode( 'stoke_gbp_badge', 'sdc_gmb_render_badge_shortcode' );
