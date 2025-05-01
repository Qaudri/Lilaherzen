<?php
class HelpersCache {
    private $cache_group = 'helpers';
    
    public function get_cached_results($key) {
        $cache_time = get_option('helpers_cache_time', 60); // Default 60 minutes
        $cached_data = wp_cache_get($key, $this->cache_group);
        
        if ($cached_data !== false) {
            $cache_time_elapsed = time() - $cached_data['timestamp'];
            if ($cache_time_elapsed < ($cache_time * 60)) {
                return $cached_data['data'];
            }
        }
        
        return false;
    }
    
    public function set_cached_results($key, $data) {
        $cache_data = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        wp_cache_set($key, $cache_data, $this->cache_group);
    }
    
    public function generate_cache_key($latitude, $longitude, $radius) {
        // Round coordinates to reduce cache variations
        $lat = round($latitude, 4);
        $lng = round($longitude, 4);
        return "helpers_{$lat}_{$lng}_{$radius}";
    }
    
    public function clear_cache() {
        wp_cache_delete_group($this->cache_group);
    }
}