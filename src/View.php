<?php

class View {
    public function render(string $template, array $variables = []) {
        $path = 'public/views/' . $template . '.php';

        if (!file_exists($path)) {
            die("Widok nie istnieje: " . $path);
        }

        extract($variables);
        
        ob_start();
        include $path;
        $file = ob_get_clean();
        
        echo $file;
    }
}