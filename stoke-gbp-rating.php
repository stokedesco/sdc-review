<?php
/**
 * Plugin Name: Stoke GBP Rating Badge
 * Plugin URI: https://example.com/
 * Description: Display a Google Business Profile rating badge via shortcode with configurable styling and caching.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com/
 * Text Domain: stoke-gbp-rating
 * Domain Path: /languages
 *
 * README
 * ======
 * Getting started
 * ---------------
 * 1. Visit https://developers.google.com/maps/documentation/places/web-service/get-api-key to create a Google Cloud project and enable the Places API. Generate a restricted API key that allows the Places Details method.
 * 2. Find your Place ID using Google's Place ID Finder at https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder . Copy the ID shown for your business.
 * 3. Enter both values on the plugin settings page under **Settings → Stoke GBP Rating Badge** and save.
 *
 * Shortcode usage
 * ---------------
 * [stoke_gbp_badge]
 * [stoke_gbp_badge stars="5" star_color="#ff9900"]
 * [stoke_gbp_badge place_id="YOUR_PLACE_ID" api_key="YOUR_KEY"]
 *
 * Caching
 * -------
 * Responses from the Google Places Details API are cached for the configured number of minutes (default 720). Update the Cache Duration setting or pass cache_minutes="0" in the shortcode to force a refresh on the next render.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load plugin text domain.
 */
function stoke_gbp_load_textdomain() {
    load_plugin_textdomain( 'stoke-gbp-rating', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'stoke_gbp_load_textdomain' );

/**
 * Retrieve default options.
 *
 * @return array
 */
function stoke_gbp_get_default_options() {
    return array(
        'api_key'       => '',
        'place_id'      => '',
        'stars'         => 5,
        'star_color'    => '#E9966F',
        'cache_minutes' => 720,
    );
}

/**
 * Retrieve saved options merged with defaults.
 *
 * @return array
 */
function stoke_gbp_get_options() {
    $options = get_option( 'stoke_gbp_rating_options', array() );

    if ( ! is_array( $options ) ) {
        $options = array();
    }

    return wp_parse_args( $options, stoke_gbp_get_default_options() );
}

/**
 * Register settings and fields.
 */
function stoke_gbp_register_settings() {
    register_setting( 'stoke_gbp_rating_options_group', 'stoke_gbp_rating_options', 'stoke_gbp_sanitize_options' );

    add_settings_section(
        'stoke_gbp_rating_section',
        __( 'Google Business Profile', 'stoke-gbp-rating' ),
        '__return_false',
        'stoke_gbp_rating'
    );

    add_settings_field(
        'api_key',
        __( 'Google Places API Key', 'stoke-gbp-rating' ),
        'stoke_gbp_render_api_key_field',
        'stoke_gbp_rating',
        'stoke_gbp_rating_section'
    );

    add_settings_field(
        'place_id',
        __( 'Place ID', 'stoke-gbp-rating' ),
        'stoke_gbp_render_place_id_field',
        'stoke_gbp_rating',
        'stoke_gbp_rating_section'
    );

    add_settings_field(
        'stars',
        __( 'Number of Stars to Display', 'stoke-gbp-rating' ),
        'stoke_gbp_render_stars_field',
        'stoke_gbp_rating',
        'stoke_gbp_rating_section'
    );

    add_settings_field(
        'star_color',
        __( 'Star Colour', 'stoke-gbp-rating' ),
        'stoke_gbp_render_star_color_field',
        'stoke_gbp_rating',
        'stoke_gbp_rating_section'
    );

    add_settings_field(
        'cache_minutes',
        __( 'Cache Duration (minutes)', 'stoke-gbp-rating' ),
        'stoke_gbp_render_cache_field',
        'stoke_gbp_rating',
        'stoke_gbp_rating_section'
    );
}
add_action( 'admin_init', 'stoke_gbp_register_settings' );

/**
 * Sanitize options before saving.
 *
 * @param array $input Raw input values.
 * @return array
 */
function stoke_gbp_sanitize_options( $input ) {
    $options   = stoke_gbp_get_options();
    $sanitized = array();

    $sanitized['api_key']       = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : $options['api_key'];
    $sanitized['place_id']      = isset( $input['place_id'] ) ? sanitize_text_field( $input['place_id'] ) : $options['place_id'];
    $sanitized['stars']         = isset( $input['stars'] ) ? absint( $input['stars'] ) : $options['stars'];
    $sanitized['star_color']    = isset( $input['star_color'] ) ? sanitize_hex_color( $input['star_color'] ) : $options['star_color'];
    $sanitized['cache_minutes'] = isset( $input['cache_minutes'] ) ? absint( $input['cache_minutes'] ) : $options['cache_minutes'];

    if ( $sanitized['stars'] < 1 ) {
        $sanitized['stars'] = 1;
    } elseif ( $sanitized['stars'] > 10 ) {
        $sanitized['stars'] = 10;
    }

    if ( empty( $sanitized['star_color'] ) ) {
        $sanitized['star_color'] = stoke_gbp_get_default_options()['star_color'];
    }

    return $sanitized;
}

/**
 * Render API key field.
 */
function stoke_gbp_render_api_key_field() {
    $options = stoke_gbp_get_options();
    echo '<input type="text" name="stoke_gbp_rating_options[api_key]" id="stoke-gbp-api-key" class="regular-text" value="' . esc_attr( $options['api_key'] ) . '" />';
    echo '<p class="description">' . esc_html__( 'Enter the restricted Google Places API key for your Google Business Profile.', 'stoke-gbp-rating' ) . '</p>';
}

/**
 * Render place ID field.
 */
function stoke_gbp_render_place_id_field() {
    $options = stoke_gbp_get_options();
    echo '<input type="text" name="stoke_gbp_rating_options[place_id]" id="stoke-gbp-place-id" class="regular-text" value="' . esc_attr( $options['place_id'] ) . '" />';
    echo '<p class="description">' . esc_html__( 'Paste the Place ID for your Google Business Profile location.', 'stoke-gbp-rating' ) . '</p>';
}

/**
 * Render stars field.
 */
function stoke_gbp_render_stars_field() {
    $options = stoke_gbp_get_options();
    echo '<input type="number" min="1" max="10" name="stoke_gbp_rating_options[stars]" id="stoke-gbp-stars" value="' . absint( $options['stars'] ) . '" />';
    echo '<p class="description">' . esc_html__( 'Choose how many star icons appear in the badge (between 1 and 10).', 'stoke-gbp-rating' ) . '</p>';
}

/**
 * Render star colour field.
 */
function stoke_gbp_render_star_color_field() {
    $options = stoke_gbp_get_options();
    $color   = sanitize_hex_color( $options['star_color'] );

    if ( ! $color ) {
        $color = stoke_gbp_get_default_options()['star_color'];
    }

    echo '<input type="text" name="stoke_gbp_rating_options[star_color]" id="stoke-gbp-star-color" class="stoke-gbp-color-field" value="' . esc_attr( $color ) . '" data-default-color="' . esc_attr( stoke_gbp_get_default_options()['star_color'] ) . '" />';
    echo '<p class="description">' . esc_html__( 'Select the colour used for the filled portion of the stars.', 'stoke-gbp-rating' ) . '</p>';
}

/**
 * Render cache field.
 */
function stoke_gbp_render_cache_field() {
    $options = stoke_gbp_get_options();
    echo '<input type="number" min="0" name="stoke_gbp_rating_options[cache_minutes]" id="stoke-gbp-cache" value="' . absint( $options['cache_minutes'] ) . '" />';
    echo '<p class="description">' . esc_html__( 'Cache the Google API response for this many minutes. Set to 0 to disable caching temporarily.', 'stoke-gbp-rating' ) . '</p>';
}

/**
 * Add settings page.
 */
function stoke_gbp_add_settings_page() {
    add_options_page(
        esc_html__( 'Stoke GBP Rating Badge', 'stoke-gbp-rating' ),
        esc_html__( 'Stoke GBP Rating Badge', 'stoke-gbp-rating' ),
        'manage_options',
        'stoke-gbp-rating',
        'stoke_gbp_render_settings_page'
    );
}
add_action( 'admin_menu', 'stoke_gbp_add_settings_page' );

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current admin page hook.
 */
function stoke_gbp_admin_enqueue_assets( $hook ) {
    if ( 'settings_page_stoke-gbp-rating' !== $hook ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".stoke-gbp-color-field").wpColorPicker();});' );
}
add_action( 'admin_enqueue_scripts', 'stoke_gbp_admin_enqueue_assets' );

/**
 * Render settings page markup.
 */
function stoke_gbp_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_SPECIAL_CHARS );

    if ( $updated ) {
        add_settings_error( 'stoke_gbp_messages', 'stoke_gbp_message', esc_html__( 'Settings saved.', 'stoke-gbp-rating' ), 'updated' );
    }

    settings_errors( 'stoke_gbp_messages' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Stoke GBP Rating Badge', 'stoke-gbp-rating' ); ?></h1>
        <p><?php esc_html_e( 'Display a Google Business Profile reviews badge anywhere via the [stoke_gbp_badge] shortcode.', 'stoke-gbp-rating' ); ?></p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'stoke_gbp_rating_options_group' );
            do_settings_sections( 'stoke_gbp_rating' );
            submit_button( __( 'Save Changes', 'stoke-gbp-rating' ) );
            ?>
        </form>
        <hr />
        <h2><?php esc_html_e( 'Help', 'stoke-gbp-rating' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Create or select a Google Cloud project, enable the Places API, and generate a restricted API key.', 'stoke-gbp-rating' ); ?></li>
            <li><?php esc_html_e( 'Use the Google Place ID Finder to look up the Place ID for your business.', 'stoke-gbp-rating' ); ?></li>
            <li><?php esc_html_e( 'Enter both values above and save. Then add the shortcode to any page, post, or widget.', 'stoke-gbp-rating' ); ?></li>
        </ol>
        <h3><?php esc_html_e( 'Shortcode Examples', 'stoke-gbp-rating' ); ?></h3>
        <ul>
            <li><code>[stoke_gbp_badge]</code></li>
            <li><code>[stoke_gbp_badge stars="5" star_color="#ff9900"]</code></li>
            <li><code>[stoke_gbp_badge place_id="YOUR_PLACE_ID" api_key="YOUR_KEY"]</code></li>
        </ul>
        <p><?php esc_html_e( 'Change the cache duration or pass cache_minutes="0" in the shortcode to refresh the data on the next render.', 'stoke-gbp-rating' ); ?></p>
    </div>
    <?php
}

/**
 * Fetch rating data from Google Places API with caching.
 *
 * @param string $place_id Place ID.
 * @param string $api_key  API key.
 * @param int    $cache_minutes Cache duration in minutes.
 * @return array|WP_Error
 */
function stoke_gbp_get_rating_data( $place_id, $api_key, $cache_minutes ) {
    $place_id = trim( $place_id );
    $api_key  = trim( $api_key );

    if ( '' === $place_id || '' === $api_key ) {
        return new WP_Error( 'stoke_gbp_missing_config', __( 'Reviews unavailable', 'stoke-gbp-rating' ) );
    }

    $transient_key = 'stoke_gbp_rating_' . md5( $place_id );
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
        return new WP_Error( 'stoke_gbp_http_error', __( 'Reviews unavailable', 'stoke-gbp-rating' ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! is_array( $data ) ) {
        return new WP_Error( 'stoke_gbp_json_error', __( 'Reviews unavailable', 'stoke-gbp-rating' ) );
    }

    if ( isset( $data['status'] ) && 'OK' !== $data['status'] ) {
        return new WP_Error( 'stoke_gbp_api_status', __( 'Reviews unavailable', 'stoke-gbp-rating' ) );
    }

    if ( empty( $data['result']['rating'] ) || ! isset( $data['result']['user_ratings_total'] ) ) {
        return new WP_Error( 'stoke_gbp_missing_fields', __( 'Reviews unavailable', 'stoke-gbp-rating' ) );
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
function stoke_gbp_enqueue_badge_style() {
    static $style_added = false;
    $handle             = 'stoke-gbp-rating-badge';

    if ( ! wp_style_is( $handle, 'registered' ) ) {
        wp_register_style( $handle, false, array(), '1.0.0' );
    }

    if ( ! wp_style_is( $handle, 'enqueued' ) ) {
        wp_enqueue_style( $handle );
    }

    if ( ! $style_added ) {
        $css = '.stoke-gbp-rating-badge{display:inline-flex;align-items:center;background-color:#1E2A3A;color:#ffffff;border-radius:9999px;padding:6px 12px;font-size:14px;line-height:1.2;font-weight:600;font-family:inherit;gap:10px;}' .
            '.stoke-gbp-rating-badge .stoke-gbp-visual{display:inline-flex;align-items:center;gap:8px;}' .
            '.stoke-gbp-rating-badge .stoke-gbp-google-icon{width:22px;height:22px;flex-shrink:0;}' .
            '.stoke-gbp-rating-badge .stoke-gbp-stars{display:inline-flex;gap:2px;}' .
            '.stoke-gbp-rating-badge .stoke-gbp-star{width:16px;height:16px;display:block;}' .
            '.stoke-gbp-rating-badge .stoke-gbp-star-bg{fill:#4d5d72;}' .
            '.stoke-gbp-rating-badge .stoke-gbp-text{white-space:nowrap;}' .
            '.stoke-gbp-rating-badge .stoke-gbp-text strong{font-weight:700;}';

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
function stoke_gbp_get_stars_markup( $rating, $star_count, $star_color ) {
    $rating     = max( 0, (float) $rating );
    $star_count = max( 1, (int) $star_count );
    $per_star   = 5 / $star_count;
    $clip_base  = 'stoke-gbp-clip-' . wp_unique_id();
    $star_path  = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z';
    $gray       = '#4d5d72';

    $markup = '';

    for ( $i = 0; $i < $star_count; $i++ ) {
        $star_start = $i * $per_star;
        $progress   = ( $rating - $star_start ) / $per_star;
        $progress   = max( 0, min( 1, $progress ) );
        $clip_id    = $clip_base . '-' . $i;
        $width      = $progress * 100;

        $markup .= '<svg class="stoke-gbp-star" viewBox="0 0 24 24" aria-hidden="true" focusable="false">';
        $markup .= '<defs><clipPath id="' . esc_attr( $clip_id ) . '"><rect x="0" y="0" width="' . esc_attr( $width ) . '%" height="100%" /></clipPath></defs>';
        $markup .= '<path class="stoke-gbp-star-bg" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $gray ) . '" />';
        $markup .= '<path class="stoke-gbp-star-fill" d="' . esc_attr( $star_path ) . '" fill="' . esc_attr( $star_color ) . '" clip-path="url(#' . esc_attr( $clip_id ) . ')" />';
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
function stoke_gbp_render_badge_shortcode( $atts ) {
    $options = stoke_gbp_get_options();

    $atts = shortcode_atts(
        array(
            'place_id'      => $options['place_id'],
            'api_key'       => $options['api_key'],
            'stars'         => $options['stars'],
            'star_color'    => $options['star_color'],
            'cache_minutes' => $options['cache_minutes'],
        ),
        $atts,
        'stoke_gbp_badge'
    );

    $place_id      = sanitize_text_field( $atts['place_id'] );
    $api_key       = sanitize_text_field( $atts['api_key'] );
    $star_count    = absint( $atts['stars'] );
    $cache_minutes = absint( $atts['cache_minutes'] );
    $star_color    = sanitize_hex_color( $atts['star_color'] );

    if ( $star_count < 1 ) {
        $star_count = 1;
    } elseif ( $star_count > 10 ) {
        $star_count = 10;
    }

    if ( empty( $star_color ) ) {
        $star_color = stoke_gbp_get_default_options()['star_color'];
    }

    $data      = stoke_gbp_get_rating_data( $place_id, $api_key, $cache_minutes );
    $has_error = is_wp_error( $data );

    $rating_value = $has_error ? 0 : (float) $data['rating'];
    $total        = $has_error ? 0 : (int) $data['user_ratings_total'];

    $aria_label = $has_error
        ? __( 'Google rating: Reviews unavailable', 'stoke-gbp-rating' )
        : sprintf(
            /* translators: 1: rating value, 2: total reviews */
            __( 'Google rating: %1$s out of 5 from %2$s reviews', 'stoke-gbp-rating' ),
            number_format_i18n( round( $rating_value, 1 ), 1 ),
            number_format_i18n( $total )
        );

    $stars_markup = stoke_gbp_get_stars_markup( $rating_value, $star_count, $star_color );

    stoke_gbp_enqueue_badge_style();

    $icon_svg = '<svg class="stoke-gbp-google-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<path fill="#4285F4" d="M21.6 12.227c0-.74-.066-1.45-.19-2.14H12v4.05h5.44a4.65 4.65 0 0 1-2.02 3.05v2.53h3.27c1.92-1.77 3-4.38 3-7.49z" />'
        . '<path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.62-2.43l-3.27-2.53c-.91.61-2.07.97-3.35.97-2.58 0-4.77-1.74-5.55-4.07H2.97v2.56A9.99 9.99 0 0 0 12 22z" />'
        . '<path fill="#FBBC05" d="M6.45 13.94a6.004 6.004 0 0 1 0-3.88V7.5H2.97a10 10 0 0 0 0 8.99l3.48-2.55z" />'
        . '<path fill="#EA4335" d="M12 6.38c1.47 0 2.79.5 3.83 1.47l2.86-2.86C16.96 2.92 14.7 2 12 2a9.99 9.99 0 0 0-9.03 5.5l3.48 2.56C7.23 8.12 9.42 6.38 12 6.38z" />'
        . '</svg>';

    $text_markup = $has_error
        ? '<span class="stoke-gbp-text">' . esc_html__( 'Reviews unavailable', 'stoke-gbp-rating' ) . '</span>'
        : '<span class="stoke-gbp-text"><strong>' . esc_html( number_format_i18n( round( $rating_value, 1 ), 1 ) ) . '</strong> ' . esc_html__( 'Stars out of', 'stoke-gbp-rating' ) . ' ' . esc_html( number_format_i18n( $total ) ) . ' ' . esc_html__( 'Reviews!', 'stoke-gbp-rating' ) . '</span>';

    $badge  = '<div class="stoke-gbp-rating-badge" role="img" aria-label="' . esc_attr( $aria_label ) . '">';
    $badge .= '<span class="stoke-gbp-visual" aria-hidden="true">';
    $badge .= '<span class="stoke-gbp-icon-wrap">' . $icon_svg . '</span>';
    $badge .= '<span class="stoke-gbp-stars">' . $stars_markup . '</span>';
    $badge .= $text_markup;
    $badge .= '</span>';
    $badge .= '</div>';

    return $badge;
}
add_shortcode( 'stoke_gbp_badge', 'stoke_gbp_render_badge_shortcode' );

