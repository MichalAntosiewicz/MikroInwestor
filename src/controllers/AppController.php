<?php

require_once 'src/View.php';

class AppController {
    private $view;

    public function __construct() {
        $this->view = new View();
    }

    protected function render(string $template = null, array $variables = [])
{
    $templatePath = 'public/views/' . $template . '.html';

    if (file_exists($templatePath)) {
        extract($variables);

        ob_start();
        include $templatePath;
        $output = ob_get_clean();
    } else {
        $output = "<h1>404 Not Found</h1><p>Template $templatePath not found.</p>";
    }
    
    echo $output;
}

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}