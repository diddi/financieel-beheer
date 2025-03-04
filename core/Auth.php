<?php
namespace App\Core;

use App\Models\User;

class Auth {
    public static function attempt($email, $password) {
        $user = User::getByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        
        self::login($user);
        return true;
    }
    
    public static function login($user) {
        Session::set('user_id', $user['id']);
        Session::set('authenticated', true);
        Session::regenerateId();
    }
    
    public static function logout() {
        Session::destroy();
    }
    
    public static function check() {
        return Session::has('authenticated') && Session::get('authenticated') === true;
    }
    
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        $userId = Session::get('user_id');
        return User::getById($userId);
    }
    
    public static function id() {
        return self::check() ? Session::get('user_id') : null;
    }
    
    public static function register($userData) {
        // Valideer data en maak nieuwe gebruiker
        if (User::getByEmail($userData['email'])) {
            throw new \Exception("E-mailadres is al in gebruik");
        }
        
        if (User::getByUsername($userData['username'])) {
            throw new \Exception("Gebruikersnaam is al in gebruik");
        }
        
        // Hash wachtwoord
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Voeg gebruiker toe
        $userId = User::create($userData);
        
        return $userId;
    }
}
