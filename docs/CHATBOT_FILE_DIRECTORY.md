# 🤖 Chatbot File Directory & Access Guide

## 📁 Quick File Locations

### **Main Chatbot Files**

```
HIGH-Q/
├── public/
│   ├── includes/
│   │   └── chat-widget.php ..................... Main chat widget (466 lines)
│   ├── chatbox.php ............................ Backend API (865 lines)
│   ├── footer.php ............................ Includes chat-widget.php
│   ├── test_chatbot_demo.php .................. Interactive demo
│   └── test_chat_widget.php ................... Simple test page
│
└── docs/
    ├── CHATBOT_GUIDE.md ...................... Complete implementation guide
    ├── CHATBOT_QUICK_REFERENCE.md ............ Visual summary
    ├── CHAT_WIDGET_IMPLEMENTATION.md ......... Original implementation notes
    └── CHATBOT_FILE_DIRECTORY.md ............ This file
```

---

## 🔗 Direct File Links

### **To View/Edit Chat Widget**
📝 **File:** `public/includes/chat-widget.php`
- **Size:** 20 KB (466 lines)
- **Contains:** HTML + CSS + JavaScript
- **Edit for:** Colors, text, FAQ options, icons

### **To Edit Chat API**
⚙️ **File:** `public/chatbox.php`
- **Size:** 38 KB (865+ lines)
- **Handles:** Message saving, retrieval, file uploads
- **Edit for:** Database queries, validation, file handling

### **To Include Chat on New Pages**
📌 **Add this line to any page's footer:**
```php
<?php include __DIR__ . '/includes/chat-widget.php'; ?>
```

---

## 🎯 Access URLs

### **Test the Chatbot**
```
Interactive Demo:
http://localhost/HIGH-Q/public/test_chatbot_demo.php

Simple Test:
http://localhost/HIGH-Q/public/test_chat_widget.php

Live on Main Pages:
http://localhost/HIGH-Q/public/index.php
http://localhost/HIGH-Q/public/about.php
http://localhost/HIGH-Q/public/programs.php
... (any of 22+ pages)
```

---

## 💾 Database Tables

### **Create Tables (if not exists)**

```sql
-- Chat Threads/Conversations
CREATE TABLE IF NOT EXISTS chat_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(100),
    visitor_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'closed', 'archived') DEFAULT 'active'
);

-- Chat Messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    sender_name VARCHAR(100),
    message LONGTEXT,
    is_from_staff BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE
);

-- Chat Attachments
CREATE TABLE IF NOT EXISTS chat_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_url VARCHAR(255),
    original_name VARCHAR(255),
    mime_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_thread_id ON chat_messages(thread_id);
CREATE INDEX idx_message_id ON chat_attachments(message_id);
CREATE INDEX idx_created_at ON chat_threads(created_at);
```

---

## 🎨 Customization Quick Links

### **To Change Colors**
📄 **File:** `public/includes/chat-widget.php`
📍 **Lines:** ~110-160 (CSS section)
```css
/* Primary color gradients */
background: linear-gradient(135deg, #ffbf00, #d99a00);
```

**Replace with your colors:**
- Primary: #ffbf00 → your-color
- Dark: #d99a00 → darker-shade

### **To Add/Edit FAQ Options**
📄 **File:** `public/includes/chat-widget.php`
📍 **Lines:** ~30-75 (HTML buttons)
📍 **Lines:** ~285-290 (JavaScript answers)

```html
<button class="faq-option" data-question="Your Question?">
    <i class="bx bx-icon-name"></i>
    <span>Button Text</span>
</button>
```

```javascript
const faqData = {
    "Your Question?": "Your answer here with <strong>HTML</strong>"
};
```

### **To Change Header Text**
📄 **File:** `public/includes/chat-widget.php`
📍 **Lines:** ~12-16
```html
<span>Live Support</span> ← Change this
```

### **To Change Welcome Message**
📄 **File:** `public/includes/chat-widget.php`
📍 **Lines:** ~22-25
```html
<h3>Hi there! 👋</h3> ← Change greeting
<p>How can we help you today?</p> ← Change subtitle
```

---

## 🔐 Admin/Support Access

### **View All Conversations**
```sql
SELECT * FROM chat_threads ORDER BY last_activity DESC;
```

### **View Messages for Specific Thread**
```sql
SELECT * FROM chat_messages WHERE thread_id = [THREAD_ID];
```

### **Send Reply as Agent**
```sql
INSERT INTO chat_messages 
(thread_id, sender_name, message, is_from_staff, created_at)
VALUES (
    1, 
    'Agent Name', 
    'Your response message', 
    1, 
    NOW()
);
```

### **View Attachments**
```sql
SELECT 
    m.id as message_id,
    a.original_name,
    a.file_url,
    a.mime_type
FROM chat_attachments a
JOIN chat_messages m ON a.message_id = m.id
WHERE m.thread_id = [THREAD_ID];
```

---

## 🚀 Integration Checklist

### **For New Pages**
- [ ] Include footer.php (which includes chat-widget)
- [ ] Verify chat button appears
- [ ] Test FAQ options
- [ ] Test agent escalation
- [ ] Check database inserts

### **For Custom Styling**
- [ ] Find CSS in chat-widget.php (~100 lines)
- [ ] Modify colors/fonts as needed
- [ ] Test on mobile/tablet
- [ ] Check responsive breakpoints

### **For Admin Panel**
- [ ] Query chat_threads table
- [ ] Display conversations list
- [ ] Show message history
- [ ] Allow staff to reply
- [ ] Mark as closed/archived

---

## 📊 JavaScript Functions Reference

### **Public Functions**
```javascript
window.hqChatShowAgentForm() 
// Shows agent contact form

// Internal functions (use in browser console for testing):
chatToggle.click()              // Toggle chat panel
showBotResponse(question)       // Show FAQ answer
showAgentForm()                 // Show agent form
showAgentChat()                 // Show live chat
loadMessages()                  // Load message history
sendAgentMessage()              // Send message
```

### **LocalStorage Keys**
```javascript
localStorage.getItem('hq_chat_thread_id')  // Get thread ID
localStorage.setItem('hq_chat_thread_id', id)  // Save thread ID
localStorage.removeItem('hq_chat_thread_id')   // Clear thread
```

---

## 🔌 API Endpoints

### **Send Message**
```
POST /chatbox.php?action=send_message

Parameters:
- name: Visitor name (first contact)
- email: Visitor email (first contact)
- message: Message text
- thread_id: Thread ID (existing chat)
- attachments: File(s) (optional)

Response:
{
  "status": "ok|error",
  "thread_id": 1,
  "message_id": 42
}
```

### **Get Messages**
```
GET /chatbox.php?action=get_messages&thread_id=1

Response:
{
  "status": "ok",
  "messages": [
    {
      "id": 1,
      "sender_name": "John",
      "message": "Hello",
      "is_from_staff": false,
      "created_at": "2025-01-24 10:30:00"
    }
  ]
}
```

---

## 📱 Mobile Breakpoints

```css
/* Mobile: < 480px */
@media (max-width: 480px) {
    .chat-panel { width: calc(100vw - 20px); }
    .chat-widget-container { bottom: 10px; right: 10px; }
}

/* Tablet: 481px - 1024px */
/* Uses default styling */

/* Desktop: > 1024px */
.chat-panel { width: 380px; }
.chat-widget-container { bottom: 20px; right: 20px; }
```

---

## 🐛 Troubleshooting

### **Chat button not showing?**
- [ ] Check footer.php includes chat-widget.php
- [ ] Verify z-index: 9999 is set
- [ ] Clear browser cache
- [ ] Check console for JS errors

### **Messages not saving?**
- [ ] Verify chat_threads table exists
- [ ] Check chat_messages table exists
- [ ] Verify database connection in chatbox.php
- [ ] Check file permissions on /uploads/chat/

### **FAQ answers not showing?**
- [ ] Check faqData object in chat-widget.php
- [ ] Verify question matches exactly (case-sensitive)
- [ ] Check HTML rendering (might have tags)

### **Agent form not submitting?**
- [ ] Check chatbox.php is accessible
- [ ] Verify POST request working (check Network tab)
- [ ] Check form validation (all fields required)
- [ ] Look for PHP errors in server logs

---

## 📝 Common Edits

### **Change Business Hours Message**
Add to showAgentForm():
```javascript
const now = new Date().getHours();
if (now < 9 || now > 17) {
    // Outside business hours
    agentForm.innerHTML += '<p>We\'re currently offline...</p>';
}
```

### **Add Auto-Reply**
In chatbox.php after INSERT:
```php
// Send auto-reply email
mail($email, 'We received your message', 'Thanks for contacting us!');
```

### **Show Typing Indicator**
Add bot typing animation:
```javascript
addMessage('bot', 'Bot', '🤖 typing...');
```

### **Limit Message Length**
In sendAgentMessage():
```javascript
if (message.length > 5000) {
    alert('Message too long (max 5000 chars)');
    return;
}
```

---

## 📞 Support Resources

### **Documentation Files**
1. **CHATBOT_GUIDE.md** - Comprehensive guide
2. **CHATBOT_QUICK_REFERENCE.md** - Visual summary
3. **CHAT_WIDGET_IMPLEMENTATION.md** - Original implementation notes

### **Test Pages**
1. **test_chatbot_demo.php** - Interactive demo
2. **test_chat_widget.php** - Simple test

### **Source Code**
1. **chat-widget.php** - Frontend (466 lines)
2. **chatbox.php** - Backend (865+ lines)

---

## ✅ Feature Checklist

- ✅ Global chat on all pages
- ✅ 6 FAQ options with instant answers
- ✅ Agent escalation form
- ✅ Live chat messaging
- ✅ Message persistence
- ✅ File attachments
- ✅ Mobile responsive
- ✅ Database integration
- ✅ Smooth animations
- ✅ Accessibility features

---

## 🎯 Next Steps

1. **Test the chatbot** - Visit any page, click yellow button
2. **Customize FAQ** - Edit questions/answers in chat-widget.php
3. **Change colors** - Modify CSS gradients to match brand
4. **Add to admin** - Create admin panel to view conversations
5. **Integrate analytics** - Track chatbot usage metrics

---

**Happy chatting! 🤖💬**
