<?php

class User_model {
    private $table = 'users';
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function checkUser($username, $password)
    {
        // Support login via username atau email
        $this->db->query("SELECT * FROM " . $this->table . " WHERE username = :username OR email = :username LIMIT 1");
        $this->db->bind('username', $username);
        $user = $this->db->single();

        if($user) {
            if(password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function getUserByUsername($username)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE username = :username OR email = :username LIMIT 1");
        $this->db->bind('username', $username);
        return $this->db->single();
    }

    public function getUserByEmail($email)
    {
        $this->db->query("SELECT * FROM " . $this->table . " WHERE email = :email");
        $this->db->bind('email', $email);
        return $this->db->single();
    }
}
