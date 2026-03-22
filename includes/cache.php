<?php
/**
 * Simple Cache System for Performance
 */

class SimpleCache {
    private $cacheDir = 'cache/';
    private $cacheTime = 300; // 5 minutes
    
    public function __construct() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if (time() > $data['expires']) {
            unlink($file);
            return null;
        }
        
        return $data['content'];
    }
    
    public function set($key, $content, $time = null) {
        $file = $this->cacheDir . md5($key) . '.cache';
        $time = $time ?? $this->cacheTime;
        
        $data = [
            'expires' => time() + $time,
            'content' => $content
        ];
        
        file_put_contents($file, serialize($data));
    }
    
    public function delete($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}