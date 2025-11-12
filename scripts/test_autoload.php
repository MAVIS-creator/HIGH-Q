<?php
require __DIR__ . '/../vendor/autoload.php';
if (class_exists(\Dotenv\Dotenv::class)) {
    echo "AOK\n";
} else {
    echo "Dotenv missing\n";
}
