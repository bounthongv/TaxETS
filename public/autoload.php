<?php
// public/autoload.php - Simple autoloader for TaxETS project

spl_autoload_register(function ($class) {
    // Project namespace is TaxETS
    $prefix = 'TaxETS\\';
    
    // Check if the class uses the TaxETS namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Class not in TaxETS namespace, skip
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});