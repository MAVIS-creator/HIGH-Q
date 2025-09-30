<?php
require __DIR__ . '/../vendor/autoload.php';
if (class_exists('\Dompdf\Dompdf')) {
    echo "dompdf available\n";
} else {
    echo "dompdf NOT available\n";
}
