<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$csrf = generateToken('registration_form');

// Get registration type from query param, default to regular
$registrationType = $_GET['type'] ?? 'regular';

// Fixed fees for POST UTME
$post_utme_form_fee = 1000;  // ₦1,000 compulsory form fee
$post_utme_tutor_fee = 8000; // ₦8,000 optional tutorial fee

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - HIGH Q SOLID ACADEMY</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./assets/css/public.css">
    <style>
        /* Registration type toggle */
        .registration-toggle {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 32px auto;
            max-width: 500px;
        }

        .toggle-btn {
            flex: 1;
            padding: 12px 24px;
            border: 2px solid var(--hq-primary);
            background: none;
            color: var(--hq-primary);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 15px;
        }

        .toggle-btn.active {
            background: var(--hq-primary);
            color: white;
        }

        .toggle-btn:hover:not(.active) {
            background: rgba(0, 102, 255, 0.1);
        }

        /* Form sections */
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        /* POST UTME specific styles */
        .form-steps {
            display: flex;
            margin: 24px auto;
            gap: 4px;
            justify-content: center;
            max-width: 300px;
        }

        .step-indicator {
            width: 40px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            transition: background-color 0.3s;
        }

        .step-indicator.active {
            background: var(--hq-primary);
        }

        .step-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .step-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .passport-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 24px;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #f9fafb;
        }

        .passport-preview {
            width: 150px;
            height: 150px;
            border-radius: 4px;
            object-fit: cover;
            display: none;
            border: 1px solid #e5e7eb;
        }

        .upload-btn {
            background: #f3f4f6;
            color: #374151;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e5e7eb;
        }

        .upload-btn:hover {
            background: #e5e7eb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .step-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .fee-options {
            margin: 24px 0;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }

        .fee-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .fee-option:last-child {
            border-bottom: none;
        }

        .fee-option .small-meta {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body class="public-body">
    <?php include __DIR__ . '/includes/header.php'; ?>