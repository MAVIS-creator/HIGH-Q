<?php
// public/chatbox.php - self-contained chat widget + lightweight backend
require_once __DIR__ . '/config/db.php';
header_remove();
		@import url('https://fonts.googleapis.com/css?family=Raleway|Ubuntu&display=swap');
		:root{ --hq-yellow:#f5b904; --hq-dark:#171716; --hq-gray:#818181; }
		*{box-sizing:border-box}
		body{font-family: 'Raleway', system-ui;}
		.chat-box{ position:fixed; right:20px; bottom:20px; width:360px; max-width:92%; height:520px; border-radius:14px; background:#fff; box-shadow:0 30px 80px rgba(11,37,64,0.12); overflow:hidden; z-index:1200; display:flex; flex-direction:column; }
		.chat-box-header{ padding:14px 16px; background:linear-gradient(90deg,var(--hq-yellow),#d99a00); color:var(--hq-dark); display:flex; justify-content:space-between; align-items:center; }
		.chat-header-left{ display:flex; gap:12px; align-items:center; }
		.chat-avatar{ width:44px; height:44px; border-radius:50%; background:linear-gradient(90deg,#ffdd66,#ffc107); box-shadow:0 6px 22px rgba(0,0,0,0.08) }
		.chat-header-left h3{ margin:0; font-size:16px }
		.chat-status{ display:block; font-size:12px; color:#222 }
		.chat-header-right button{ background:transparent;border:none;font-size:18px;cursor:pointer }
		.chat-box-body{ flex:1; padding:14px; background:linear-gradient(180deg,#fff,#fbfbfb); overflow:auto }
		.hq-messages{ display:flex; flex-direction:column; gap:12px }
		.bubble{ max-width:78%; padding:12px 14px; border-radius:16px; font-size:14px; line-height:1.4 }
		.bubble.user{ background:linear-gradient(90deg,var(--hq-yellow),#d99a00); color:#111; align-self:flex-end; border-bottom-right-radius:6px }
		.bubble.admin{ background:#f1f6ff; color:#08204a; align-self:flex-start; border-bottom-left-radius:6px }
		.bubble .time{ display:block; font-size:11px; color:var(--hq-gray); margin-top:6px }
		.chat-attachment img{ max-width:200px; border-radius:8px; display:block; margin-top:8px }
		.chat-box-footer{ padding:12px; display:flex; gap:8px; align-items:flex-end; border-top:1px solid rgba(0,0,0,0.04) }
		.chat-meta{ padding:8px 10px; border-radius:10px; border:1px solid #eee; font-size:13px }
		#chatInput{ flex:1; padding:10px; border-radius:10px; border:1px solid #eee; resize:vertical; min-height:44px }
		.btn-primary{ background:var(--hq-dark); color:#fff; border:none; padding:10px 14px; border-radius:10px; cursor:pointer }
		.btn-ghost{ background:#fff; border:1px solid rgba(0,0,0,0.06); padding:8px 10px; border-radius:8px; cursor:pointer }
		.chat-button{ position:fixed; right:24px; bottom:24px; background:var(--hq-yellow); color:var(--hq-dark); padding:12px 18px; border-radius:999px; box-shadow:0 18px 40px rgba(217,154,0,0.18); cursor:pointer; z-index:1199 }
		.hidden{ display:none }
		@media (max-width:600px){ .chat-box{ width:100%; right:0; bottom:0; border-radius:0; height:70vh } .chat-meta{ display:none } }
	</style>
    margin: 10px;
    font-family: ubuntu;
    font-weight: 600;
    color: #444;
   
    width: 280px;
    
			
			&:focus{
				outline: none;
			}
			
		}
		& .send{
    	vertical-align: middle;
    	align-items: center;
    	justify-content: center;
    	transform: translate(0px, 20px);
			cursor: pointer;
		}
	}
}

.chat-button{
	padding: 25px 16px;
	background: #2C50EF;
	width: 120px;
	position: absolute;
	bottom: 0;
	right: 0;
	margin: 15px;
	border-top-left-radius: 25px;
	border-top-right-radius: 25px;
	border-bottom-left-radius: 25px;
	box-shadow: 0 2px 15px rgba(#2C50EF,.21);
	cursor: pointer;
	
	& span{

		&::before{
		content: '';
    height: 15px;
    width: 15px;
    background: #47cf73;
    position: absolute;
    transform: translate(0, -7px);
    border-radius: 15px;
		}
		
		&::after{
		content: "Message Us";
    font-size: 14px;
    color: white;
    position: absolute;
    left: 50px;
    top: 18px;
		}
	}
}



		.modal {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transform: scale(1.1);
        transition: visibility 0s linear 0.25s, opacity 0.25s 0s, transform 0.25s;
    }
    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 1rem 1.5rem;
        width: 24rem;
        border-radius: 0.5rem;
    }
    .modal-close-button {
        float: right;
        width: 1.5rem;
        line-height: 1.5rem;
        text-align: center;
        cursor: pointer;
        border-radius: 0.25rem;
        background-color: lightgray;
    }
    .close-button:hover {
        background-color: darkgray;
    }
    .show-modal {
        opacity: 1;
        visibility: visible;
        transform: scale(1.0);
        transition: visibility 0s linear 0s, opacity 0.25s 0s, transform 0.25s;
			z-index: 30;
    }
	


@media screen only and(max-width: 450px)
{
	.chat-box{
		min-width: 100% !important;
	}
}
    </style>

    <script>
        $('.chat-button').on('click' , function(){
	$('.chat-button').css({"display": "none"});
	
	$('.chat-box').css({"visibility": "visible"});
});

$('.chat-box .chat-box-header p').on('click' , function(){
	$('.chat-button').css({"display": "block"});
	$('.chat-box').css({"visibility": "hidden"});
})

$("#addExtra").on("click" , function(){
		
		$(".modal").toggleClass("show-modal");
})

$(".modal-close-button").on("click" , function(){
	$(".modal").toggleClass("show-modal");
})
    </script>
	<script>
	(function(){
		function setCookie(name,value,days){ var d=new Date(); d.setTime(d.getTime()+(days*24*60*60*1000)); document.cookie = name+"="+encodeURIComponent(value)+";path=/;expires="+d.toUTCString(); }
		function getCookie(name){ var m=document.cookie.match(new RegExp('(^| )'+name+'=([^;]+)')); return m? decodeURIComponent(m[2]) : null; }

		var openBtn = document.getElementById('hqOpenButton');
		var chatBox = document.getElementById('hqChatBox');
		var closeBtn = document.getElementById('closeChat');
		var sendBtn = document.getElementById('chatSend');
		var addExtra = document.getElementById('addExtra');
		var fileInput = document.getElementById('chatFile');
		var emojiBtn = document.getElementById('emojiBtn');
		var messagesEl = document.getElementById('hqMessages');
		var input = document.getElementById('chatInput');
		var nameInput = document.getElementById('chatName');
		var emailInput = document.getElementById('chatEmail');

		var pollTimer = null;

		openBtn.addEventListener('click', function(){ openBtn.classList.add('hidden'); chatBox.classList.remove('hidden'); });
		closeBtn.addEventListener('click', function(){ openBtn.classList.remove('hidden'); chatBox.classList.add('hidden'); });

		// file attachment
		addExtra.addEventListener('click', function(){ fileInput.click(); });
		fileInput.addEventListener('change', function(){ if(fileInput.files && fileInput.files[0]){ var p = document.createElement('div'); p.className='bubble user'; p.textContent = 'Attachment ready: ' + fileInput.files[0].name; messagesEl.appendChild(p); messagesEl.scrollTop = messagesEl.scrollHeight; } });

		// emoji quick insert
		emojiBtn.addEventListener('click', function(){ input.value = input.value + ' 😊'; input.focus(); });

		async function sendMessage(){ var name = nameInput.value.trim() || 'Guest'; var email = emailInput.value.trim() || ''; var msg = input.value.trim(); if(!msg && !(fileInput.files && fileInput.files[0])){ alert('Please enter a message or attach an image'); return; }
			var fd = new FormData(); fd.append('name', name); fd.append('email', email); fd.append('message', msg);
			if(fileInput.files && fileInput.files[0]) fd.append('attachment', fileInput.files[0]);
			try{
				var res = await fetch(window.location.pathname + '?action=send_message', { method: 'POST', body: fd });
				var j = await res.json(); if(j.status==='ok'){ setCookie('hq_thread_id', j.thread_id, 7); renderUserMessage(msg, fileInput.files && fileInput.files[0]); input.value=''; fileInput.value=''; if(!pollTimer) startPolling(j.thread_id); }
			}catch(e){ console.error(e); alert('Failed to send'); }
		}

		sendBtn.addEventListener('click', sendMessage);

		function renderUserMessage(text, hasFile){ var b = document.createElement('div'); b.className='bubble user'; if(text) b.textContent = text; if(hasFile){ var hint = document.createElement('div'); hint.className='chat-attachment'; hint.textContent = 'Image attached'; b.appendChild(hint); } messagesEl.appendChild(b); messagesEl.scrollTop = messagesEl.scrollHeight; }

		function renderMessages(list){ messagesEl.innerHTML = ''; list.forEach(function(m){ var d = document.createElement('div'); d.className = 'bubble ' + (m.is_from_staff==1 ? 'admin' : 'user'); d.innerHTML = m.message + '<span class="time">' + m.created_at + '</span>'; messagesEl.appendChild(d); }); messagesEl.scrollTop = messagesEl.scrollHeight; }

		async function fetchMessages(threadId){ try{ var r = await fetch(window.location.pathname + '?action=get_messages&thread_id='+encodeURIComponent(threadId)); var j = await r.json(); if(j.status==='ok'){ renderMessages(j.messages); } }catch(e){}
		}

		function startPolling(threadId){ if(pollTimer) return; pollTimer = setInterval(function(){ fetchMessages(threadId); }, 2500); fetchMessages(threadId); }

		// start if existing thread
		var existing = getCookie('hq_thread_id'); if(existing){ startPolling(existing); }

	})();
	</script>