<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location:' . APP_URL . 'public/login.php');
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location:' . APP_URL . 'public/login.php');
        exit;
    }
}

function authUser($usr, $pass) {
    
    $db = new Database();

    //echo "auth_user: DB instance created ...<br>";
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    
    //echo "auth_user: SQL prepared...<br>";
    //echo "auth_user: ". $stmt . "<br>";
    
    $stmt->execute([$usr]);
    
    //echo "auth_user: SQL executed...<br>";
    
    if ($user = $stmt->fetch()) {
        //echo "auth_user: user info extracted...<br>";    
        // Verificare parolă cu hash
    
        if (password_verify($pass, $user['password'])) {
            // Ștergem parola din array-ul returnat
            unset($user['password']);
            //echo "auth_user: user auth success...<br>";
            return $user;
        }
        

        // Verificare parola fara hash
        //if ($password == $user['password']){
            // Ștergem parola din array-ul returnat
        //    unset($user['password']);
        //    return $user;
        //}

    }
    //echo "auth_user: user auth failed...<br>";
    return false;
}
