<?php
// Test dynamic app_url function

echo "Testing dynamic app_url()...\n\n";

// Test 1: ngrok domain
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'slaphappy-premillennially-louann.ngrok-free.dev';
$_SERVER['SCRIPT_NAME'] = '/HIGH-Q/public/index.php';

require 'public/config/functions.php';

echo "Test 1: ngrok domain\n";
echo "  app_url() = " . app_url() . "\n";
echo "  app_url('assets/css/public.css') = " . app_url('assets/css/public.css') . "\n\n";

// Test 2: localhost domain
$_SERVER['HTTPS'] = '';
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = '/HIGH-Q/public/home.php';

// Reset to call function again
echo "Test 2: localhost domain\n";
echo "  app_url() = " . app_url() . "\n";
echo "  app_url('assets/css/public.css') = " . app_url('assets/css/public.css') . "\n\n";

// Test 3: Different domain
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'myschool.com';
$_SERVER['SCRIPT_NAME'] = '/HIGH-Q/public/programs.php';

echo "Test 3: custom domain\n";
echo "  app_url() = " . app_url() . "\n";
echo "  app_url('assets/css/public.css') = " . app_url('assets/css/public.css') . "\n";
