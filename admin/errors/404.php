<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float { animation: float 3s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-purple-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
            <div class="flex justify-center mb-6">
                <div class="float bg-gradient-to-br from-indigo-600 to-purple-600 text-white rounded-full w-24 h-24 flex items-center justify-center text-4xl font-bold shadow-lg">
                    404
                </div>
            </div>
            
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                Admin Page Not Found
            </h1>
            
            <p class="text-gray-600 text-lg mb-8">
                The admin page you're looking for doesn't exist.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="../index.php?pages=dashboard" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go to Dashboard
                </a>
                <button onclick="window.history.back()" 
                        class="inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:border-gray-400 hover:bg-gray-50 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Go Back
                </button>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-500">© <?= date('Y') ?> HIGH Q SOLID ACADEMY - Admin Panel</p>
            </div>
        </div>
    </div>
</body>
</html>
