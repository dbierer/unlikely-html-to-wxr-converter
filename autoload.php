<?php
spl_autoload_register(function ($class) {
    if (strpos($class, 'Unlikely') === 0) {
        $fn = str_replace('\\', '/', $class) . '.php';
        $load = __DIR__ . '/src/' . $fn;
        if (!file_exists($load)) {
            $load = __DIR__ . '/tests/' . $fn;
        }
        require $load;
    }
});
