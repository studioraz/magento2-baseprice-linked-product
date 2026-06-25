<?php

declare(strict_types=1);

// Use the Magento project vendor autoloader which provides Magento framework classes.
$magentoVendor = '/Users/itay/PhpstormProjects/magento/vendor/autoload.php';
if (file_exists($magentoVendor)) {
    require_once $magentoVendor;
} else {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Register bridge module source as PSR-4
spl_autoload_register(function (string $class): void {
    $prefix = 'SR\\BasePriceLinkedProduct\\';
    $baseDir = __DIR__ . '/../src/';

    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Also register SR_SimpleProductLink source so bridge tests can reference its interfaces
spl_autoload_register(function (string $class): void {
    $prefix = 'SR\\SimpleProductLink\\';
    $splSrc = '/Users/itay/PhpstormProjects/magento/modules/studioraz/magento2-simple-product-link/src/';

    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = $splSrc . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Provide a stub for Magento's translation function when running standalone unit tests
if (!function_exists('__')) {
    function __(string $text, ...$args): string
    {
        return $args ? vsprintf($text, $args) : $text;
    }
}
