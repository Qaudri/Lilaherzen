<?php
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}
?>

<div class="nearby-helpers-finder">
    <form id="helper-location-form">
        <?php wp_nonce_field('nearby_helpers_search', 'helpers_nonce'); ?>
        <input 
            type="text" 
            id="search-address" 
            placeholder="<?php esc_attr_e('Enter your address', 'nearby-helpers'); ?>" 
            required
        >
        <button type="submit">
            <?php esc_html_e('Find Nearby Helpers', 'nearby-helpers'); ?>
        </button>
    </form>
    <div id="nearby-helpers-results"></div>
</div> 