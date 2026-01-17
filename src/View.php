<?php

class View {
    public function render(string $template, array $variables = []) {
        // Ścieżka zgodna z Twoim wymaganiem: public/views/
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