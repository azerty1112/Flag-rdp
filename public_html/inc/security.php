<?php
class Security {
    public static function encrypt($data) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', SECRET_KEY, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    public static function decrypt($data) {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        return openssl_decrypt($cipher, 'AES-256-CBC', SECRET_KEY, 0, $iv);
    }
}