<?php
class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/layout'): string
    {
        extract($data);

        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $title   = $title ?? 'PunyaSiapa';
        $content = ob_get_clean();

        ob_start();
        require __DIR__ . '/../views/' . $layout . '.php';

        return ob_get_clean();
    }
}
