<?php
namespace App\Models;

use App\Core\Database;

class AccountType {
    protected static $table = 'account_types';
    
    public static function getAll() {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM " . self::$table . " ORDER BY name");
    }
    
    public static function getById($id) {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM " . self::$table . " WHERE id = ?", [$id]);
    }
}
