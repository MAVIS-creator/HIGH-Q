<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('ai_assistant');

$pageTitle = 'HighQ AI';
$pageSubtitle = 'Site-aware admin help, grounded answers, and safe drafting support';
$csrf = function_exists('generateToken') ? generateToken('ai_assistant_api') : '';
$actionCsrf = function_exists('generateToken') ? generateToken('ai_action_api') : '';
?>

<div class="dashboard-container">
    <div class="page-hero">
        <div class="page-hero-content">
            <div>
                <span class="page-hero-badge"><i class='bx bx-bot'></i> HighQ AI</span>
                <h1 class="page-hero-title">HighQ AI</h1>
                <p class="page-hero-subtitle">Your site-aware admin copilot is ready across the whole panel.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Information Card -->
        <div class="col-lg-8" style="margin-bottom: 24px;">
            <div class="admin-card h-100" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 40px 20px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #ffd600, #ffb300); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #111; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(255, 179, 0, 0.4);">
                    <i class='bx bx-bot' style="animation: hqFabPulse 2s infinite;"></i>
                </div>
                <h2 style="font-weight: 800; margin-bottom: 15px;">HighQ AI is ready in the bottom right corner.</h2>
                <p style="font-size: 1.1rem; color: #555; max-width: 500px; line-height: 1.6; margin-bottom: 30px;">
                    We've upgraded HighQ AI to understand the admin panel, the public workflows, and the live site settings. Click the floating button from <strong>any page</strong> in the admin panel to ask questions, draft content, or prepare safe actions for review.
                </p>
                <div style="text-align: left; background: #f8f9fa; padding: 15px 20px; border-radius: 12px; border: 1px solid #eee; width: 100%; max-width: 500px;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0 0 10px 0;"><i class='bx bx-check-shield' style="color: #28a745;"></i> What can HighQ AI do?</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #444; line-height: 1.6; font-size: 0.9rem;">
                        <li>Answer admin-page questions with real HighQ site context.</li>
                        <li>Explain registrations, payments, chat, settings, and operational flows.</li>
                        <li>Draft posts, emails, support replies, and announcements in a cleaner structure.</li>
                        <li>Propose sensitive actions safely through the Review Queue.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Settings & Security Cards -->
        <div class="col-lg-4" style="margin-bottom: 24px;">
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <h3 class="admin-card-title"><i class='bx bx-shield-quarter'></i> Review Queue</h3>
                </div>
                <div class="admin-card-body">
                    <p style="font-size: 0.9rem; color: #555; margin-bottom: 15px;">
                        Any database modifications or critical actions proposed by HighQ AI are sent to the Review Queue. Nothing runs automatically.
                    </p>
                    <a href="index.php?pages=ai_queue" class="btn btn-dark w-100"><i class='bx bx-list-check'></i> Open Review Queue</a>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title"><i class='bx bx-slider-alt'></i> AI Settings</h3>
                </div>
                <div class="admin-card-body">
                    <p style="font-size: 0.9rem; color: #555; margin-bottom: 15px;">
                        Manage your active AI models (Gemini, Groq, OpenRouter), API connections, and performance settings.
                    </p>
                    <a href="index.php?pages=ai_provider" class="btn btn-outline-dark w-100"><i class='bx bx-cog'></i> Provider Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>
