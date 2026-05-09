# 🤖 Chatbot Implementation Guide

## ✅ Status: Fully Implemented & Active

The intelligent chatbot with FAQ support and live agent escalation is **now live on your entire website**!

---

## 📍 Where to Find the Chatbot

### ✨ On Every Page
The chat widget appears as a **floating yellow button** in the bottom-right corner of every page:
- **Desktop:** Fixed position, 60px button
- **Mobile:** Responsive, adapts to smaller screens
- **Tablet:** Optimized for medium screens

### 📄 Pages with Chat Widget
The chatbot is available on all main pages:
- ✅ Home (index.php)
- ✅ About (about.php)
- ✅ Programs (programs.php, program.php, program-single.php)
- ✅ Registration (register.php, register_v2.php, register-new.php)
- ✅ Contact (contact.php)
- ✅ Exams (exams.php, path-*.php)
- ✅ Post-UTME (post-utme.php)
- ✅ Community (community.php)
- ✅ News (news.php, post.php)
- ✅ Legal (privacy.php, terms.php)
- ✅ Quiz (find-your-path-quiz.php)
- ✅ **All 22+ public pages**

---

## 🎯 How the Chatbot Works

### Phase 1: Bot Welcome Screen
When user clicks the chat button, they see:
1. **Bot Avatar** - Yellow gradient circle with bot icon
2. **Welcome Message** - "Hi there! 👋 How can we help you today?"
3. **6 FAQ Options** with icons:
   - 🔐 How to Register
   - 📚 Available Programs
   - 💳 Payment Options
   - 📋 Check Admission Status
   - ☎️ Contact Details
   - 💬 Talk to Agent (Escalation)

### Phase 2: Bot Response
When user clicks an FAQ option:
1. User's question appears as a chat message
2. Bot shows its icon and response (animated)
3. Response includes links and formatting
4. "Talk to Agent" button appears for escalation

**Sample FAQ Answers:**
- **How to Register?** → Links to Programs page + instructions
- **Available Programs?** → Lists JAMB, WAEC, POST-UTME, tutoring
- **Payment Options?** → Bank, Paystack, Stripe details
- **Check Admission?** → Instructions for dashboard login
- **Contact Details?** → Email, phone, direct chat

### Phase 3: Live Agent Escalation
When user clicks "Talk to Agent":
1. Form appears: Name, Email, Message fields
2. User fills form and clicks "Start Chat"
3. Chat thread created in database (chat_threads table)
4. Switches to live chat mode with input footer

### Phase 4: Live Chat
Once connected:
1. User can type and send messages
2. Messages appear with green avatar
3. Agent responses appear with purple avatar
4. Chat history persists in database
5. Attachments supported (images, PDF, DOCX)

---

## 💾 Data Storage

### Database Tables
Chat messages are stored in:
- **`chat_threads`** - Conversation threads
  - Fields: id, visitor_name, visitor_email, created_at, last_activity, status
  
- **`chat_messages`** - Individual messages
  - Fields: id, thread_id, sender_name, message, is_from_staff, created_at
  
- **`chat_attachments`** - File uploads
  - Fields: id, message_id, file_url, original_name, mime_type, created_at

### Persistence
- **Client-side:** `localStorage` (hq_chat_thread_id)
- **Server-side:** MySQL database
- **History:** Available when reopening chat window

---

## 🎨 Customization

### Change FAQ Options

Edit `public/includes/chat-widget.php` (around line 60):

```html
<div class="faq-options">
    <button class="faq-option" data-question="Your Question Here?">
        <i class="bx bx-icon-name"></i>
        <span>Display Text</span>
    </button>
</div>
```

And add the answer in the JavaScript faqData object:

```javascript
const faqData = {
    "Your Question Here?": "Your answer with <strong>HTML</strong> support"
};
```

### Available Icons (BoxIcons)
- `bx-user-plus` - User registration
- `bx-book-open` - Programs/courses
- `bx-credit-card` - Payment
- `bx-file-find` - Document search
- `bx-phone` - Contact/phone
- `bx-message-dots` - Message
- `bx-headphone` - Agent/support
- `bx-bot` - Chatbot
- Browse more at https://boxicons.com/

### Change Colors

Edit CSS in `public/includes/chat-widget.php`:

```css
/* Change gradient from yellow to your color */
.chat-toggle-btn {
    background: linear-gradient(135deg, #your-color, #darker-shade);
}

.chat-widget-header {
    background: linear-gradient(135deg, #your-color, #darker-shade);
}

.btn-submit {
    background: linear-gradient(135deg, #your-color, #darker-shade);
}
```

**Current colors:**
- Primary: `#ffbf00` (Yellow)
- Dark: `#d99a00` (Dark Yellow)
- Text: `#111` (Dark)
- Background: `#f9fafb` (Light gray)

### Change Text

Edit strings in `public/includes/chat-widget.php`:

```javascript
// Header
<span>Live Support</span>

// Welcome
<h3>Hi there! 👋</h3>
<p>How can we help you today?</p>

// FAQ buttons
data-question="Custom Question?"
<span>Custom Button Text</span>

// FAQ answers
"Custom Question?": "Custom Answer with HTML"

// Agent form
<h4>Connect with an Agent</h4>
<p>Please provide your details to start chatting</p>
```

---

## 🔧 Technical Implementation

### Files Involved

1. **Chat Widget Component**
   - File: `public/includes/chat-widget.php` (466 lines)
   - Contains: HTML, CSS, JavaScript
   - Status: ✅ Complete and functional

2. **Chat API Endpoint**
   - File: `public/chatbox.php` (865+ lines)
   - Handles: Message sending, retrieval, file uploads
   - Endpoints:
     - `action=send_message` - POST new message
     - `action=get_messages` - GET chat history

3. **Footer Integration**
   - File: `public/includes/footer.php`
   - Line: 163 includes chat-widget.php
   - Status: ✅ Global on all pages

### How It Works (Technical)

```
User visits page
      ↓
Footer loads chat-widget.php
      ↓
Chat button appears (fixed position, z-index 9999)
      ↓
User clicks button
      ↓
chatPanel.classList.toggle('show')
      ↓
Bot landing screen displays
      ↓
User clicks FAQ option
      ↓
showBotResponse() function triggers
      ↓
Message sent to chatMessages div
      ↓
If "Talk to Agent": showAgentForm()
      ↓
Form submitted → fetch(chatbox.php?action=send_message)
      ↓
chatbox.php handles request:
  - Creates chat_thread (if new)
  - Inserts chat_message
  - Returns JSON response
      ↓
showAgentChat() displays live chat
      ↓
User can type messages
      ↓
chatInput keyboard listener:
  - Enter key sends message
  - Auto-resizes textarea
      ↓
sendAgentMessage() fetches API
      ↓
Thread ID saved to localStorage
      ↓
History loads on chat reopen
```

---

## 📱 Mobile Optimization

### Responsive Breakpoints
- **Mobile:** < 480px - Full width, bottom-left position
- **Tablet:** 480px - 1024px - Partial width, standard position
- **Desktop:** > 1024px - 380px fixed width, bottom-right

### Mobile Features
- ✅ Touch-friendly buttons (larger hit area)
- ✅ Responsive panel sizing
- ✅ Mobile navbar doesn't cover widget
- ✅ Keyboard doesn't scroll chat away
- ✅ Haptic feedback on button tap (via mobile-animations.js)

---

## 🐛 Testing

### Test URLs

```
Main Demo Page:
http://localhost/HIGH-Q/public/test_chatbot_demo.php

Chat Widget Test:
http://localhost/HIGH-Q/public/test_chat_widget.php

Live on Any Page:
http://localhost/HIGH-Q/public/index.php
http://localhost/HIGH-Q/public/about.php
http://localhost/HIGH-Q/public/contact.php
... (any of the 22+ pages)
```

### Test Scenarios

1. **Bot Response Test**
   - Click chat button
   - Click each FAQ option
   - Verify instant responses
   - Check "Talk to Agent" button appears

2. **Agent Escalation Test**
   - Click "Talk to Agent"
   - Fill form: Name, Email, Message
   - Click "Start Chat"
   - Verify thread created in database
   - Check chat interface appears

3. **Message History Test**
   - Send a message in agent chat
   - Close chat window
   - Reopen chat
   - Verify message history still visible
   - Check localStorage has thread_id

4. **Database Check**
   ```sql
   -- View all chat threads
   SELECT * FROM chat_threads;
   
   -- View messages for thread
   SELECT * FROM chat_messages WHERE thread_id = 1;
   
   -- View attachments
   SELECT * FROM chat_attachments WHERE message_id = 1;
   ```

---

## 🔐 Security Features

- ✅ HTML escaping on all messages
- ✅ File type validation (image, PDF, DOCX only)
- ✅ File size limit (100MB)
- ✅ Prepared statements (PDO)
- ✅ Numeric thread_id validation
- ✅ XSS protection with ENT_QUOTES

---

## 📊 Admin View

### Viewing Chats
Admins can view all conversations in:
- Admin panel → Chat Management (if available)
- Direct database query
- PHP staff dashboard

### Responding to Users
Staff can respond through:
- Chat admin interface
- Insert messages with `is_from_staff = 1` flag
- Messages will appear with purple agent icon

---

## 🚀 Future Enhancements

Potential features to add:
- [ ] Typing indicators ("Agent is typing...")
- [ ] Auto-reply for business hours
- [ ] Chat transcript email
- [ ] Rating/feedback system
- [ ] AI chatbot (replace static FAQ)
- [ ] Video chat support
- [ ] Mobile app integration
- [ ] Analytics dashboard

---

## 📞 Contact & Support

**Current FAQ Content:**
- Email: highqsolidacademy@gmail.com
- Phone: +234 807 208 8794
- Programs: JAMB, WAEC, POST-UTME, Tutoring
- Registration: Find Your Path quiz → Registration form

**To Edit These:**
Edit lines 285-290 in `public/includes/chat-widget.php` or the faqData object

---

## ✅ Verification Checklist

- ✅ Chat widget loads on all pages
- ✅ Bot FAQ options respond instantly
- ✅ Agent form submits successfully
- ✅ Chat messages save to database
- ✅ Thread history persists
- ✅ Mobile responsive
- ✅ Icons display correctly
- ✅ Animations smooth
- ✅ No console errors
- ✅ Accessible (aria labels)

---

## 📝 Summary

Your chatbot is **fully implemented** with:
- 🤖 AI-style bot responses
- 📝 6 customizable FAQ options
- 👤 Live agent escalation
- 💾 Message history
- 📱 Mobile optimized
- 🔐 Secure & fast
- 🎨 Professional design
- ✨ Smooth animations

**Users can now get instant answers OR connect with your team** - all from a single floating chat button!
