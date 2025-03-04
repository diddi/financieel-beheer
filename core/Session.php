<?php
namespace App\Core;

class Session {
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        session_destroy();
        $_SESSION = [];
    }
    
    public static function regenerateId() {
        session_regenerate_id(true);
    }
    
    public static function flash($key, $value) {
        self::set('_flash_' . $key, $value);
    }
    
    public static function getFlash($key, $default = null) {
        $value = self::get('_flash_' . $key, $default);
        self::remove('_flash_' . $key);
        return $value;
    }
    
    public static function hasFlash($key) {
        return self::has('_flash_' . $key);
    }
}
