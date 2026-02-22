<?php
class Controller {
    protected array $request;
    public function __construct() {
        $this->request = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri' => strtok($_SERVER['REQUEST_URI'] ?? '/', '?'),
            'query' => $_GET,
            'body' => json_decode(file_get_contents('php://input'), true) ?? [],
            'post' => $_POST,
            'files' => $_FILES
        ];
    }
}
