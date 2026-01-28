<?php

require_once 'src/View.php'; // Upewnij się, że ścieżka jest poprawna

class AppController {
    private $view;

    public function __construct() {
        $this->view = new View();
    }

    protected function render(string $template = null, array $variables = []) {
        // Zmieniliśmy na .php, więc szukamy .php
        $templatePath = 'public/views/' . $template . '.php'; 

        if (file_exists($templatePath)) {
            extract($variables);
            ob_start();
            include $templatePath;
            echo ob_get_clean();
        } else {
            // Jeśli nie ma .php, sprawdźmy .html na wszelki wypadek (zgodnie z Twoją nową nazwą)
            $altPath = 'public/views/' . $template . '.html.php';
            if (file_exists($altPath)) {
                extract($variables);
                ob_start();
                include $altPath;
                echo ob_get_clean();
            } else {
                echo "<h1>404 Not Found</h1><p>Template $templatePath not found.</p>";
            }
        }
    }

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}