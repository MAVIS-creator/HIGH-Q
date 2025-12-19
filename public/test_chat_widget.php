<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Widget Test</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
        h1 { color: #111; margin-bottom: 20px; }
        .test-info { background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0284c7; margin-bottom: 20px; }
        .test-info h2 { margin-top: 0; color: #0284c7; font-size: 18px; }
        .test-info ul { margin: 10px 0; padding-left: 20px; }
        .test-info li { margin: 6px 0; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Chat Widget Test Page</h1>
    
    <div class="test-info">
        <h2>Test Instructions</h2>
        <ul>
            <li>Look for the chat button in the bottom-right corner</li>
            <li>Click it to open the chat widget</li>
            <li>Test the FAQ options (bot responses)</li>
            <li>Click "Talk to Agent" to test live agent form</li>
            <li>Submit agent form and send messages</li>
            <li>Close and reopen to test persistence</li>
        </ul>
    </div>
    
    <div class="test-info">
        <h2>Features Implemented</h2>
        <ul>
            <li>✅ Global chat widget accessible from any page</li>
            <li>✅ Chatbot with predefined FAQ options</li>
            <li>✅ Smooth transition from bot to live agent</li>
            <li>✅ Thread persistence via localStorage</li>
            <li>✅ File attachment support (images, PDF, DOCX)</li>
            <li>✅ Modern animated UI with Boxicons</li>
        </ul>
    </div>
    
    <p>Scroll down to test chat widget positioning...</p>
    <div style="height: 1000px; background: linear-gradient(180deg, #f9fafb 0%, #e5e7eb 100%); margin-top: 20px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6b7280;">
        <span>Spacer content - scroll to see chat widget stick to viewport</span>
    </div>
    
    <?php
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/functions.php';
    include __DIR__ . '/includes/chat-widget.php';
    ?>
</body>
</html>
