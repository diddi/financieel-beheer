<?php
namespace App\Models;

use App\Core\Database;

class User {
    protected static $table = 'users';
    
    public static function getById($id) {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM " . self::$table . " WHERE id = ?", [$id]);
    }
    
    public static function getByEmail($email) {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM " . self::$table . " WHERE email = ?", [$email]);
    }
    
    public static function getByUsername($username) {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM " . self::$table . " WHERE username = ?", [$username]);
    }
    
    public static function getByToken($token) {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM " . self::$table . " WHERE reset_token = ?", [$token]);
    }
    
    public static function create($data) {
        $db = Database::getInstance();
        return $db->insert(self::$table, $data);
    }
    
    public static function update($id, $data) {
        $db = Database::getInstance();
        $db->update(self::$table, $data, "id = ?", [$id]);
    }
}
