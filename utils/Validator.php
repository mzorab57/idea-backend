<?php
class Validator {
    public static function require(array $data, array $keys): array {
        $errors = [];
        foreach ($keys as $k) {
            if (!isset($data[$k]) || $data[$k] === '' || $data[$k] === null) {
                $errors[$k] = 'required';
            }
        }
        return $errors;
    }
    public static function email(string $email): bool {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    public static function string(string $val, int $min = 1, int $max = 255): bool {
        $len = strlen($val);
        return $len >= $min && $len <= $max;
    }
    public static function slug(string $val): bool {
        return (bool)preg_match('/^[a-z0-9-]+$/', $val);
    }
}
