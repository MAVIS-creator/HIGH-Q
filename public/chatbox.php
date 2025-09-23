<?php
// public/chatbox.php - self-contained chat widget + lightweight backend
require_once __DIR__ . '/config/db.php';
header_remove();

// Simple API: POST to this file with action=send_message, or GET action=get_messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_GET['action'] ?? $_POST['action'] ?? 'send_message';
	header('Content-Type: application/json; charset=utf-8');
	try {
		if ($action === 'send_message') {
			$thread_id = !empty($_POST['thread_id']) ? intval($_POST['thread_id']) : null;
			$visitor_name = trim($_POST['name'] ?? 'Guest');
			$visitor_email = trim($_POST['email'] ?? '');
			$message = trim($_POST['message'] ?? '');

			if ($message === '' && empty($_FILES['attachment'])) {
				echo json_encode(['status'=>'error','message'=>'Empty message']); exit;
			}

			// handle attachment upload (optional)
			$attachmentHtml = '';
			if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
				$up = $_FILES['attachment'];
				$ext = pathinfo($up['name'], PATHINFO_EXTENSION);
				$allowed = ['jpg','jpeg','png','gif','webp'];
				if (!in_array(strtolower($ext), $allowed)) {
					echo json_encode(['status'=>'error','message'=>'File type not allowed']); exit;
				}
				$destDir = __DIR__ . '/uploads/chat';
				if (!is_dir($destDir)) mkdir($destDir, 0755, true);
				$basename = bin2hex(random_bytes(8)) . '.' . $ext;
				$dest = $destDir . '/' . $basename;
				if (move_uploaded_file($up['tmp_name'], $dest)) {
					// relative web path
					$webPath = str_replace('\\','/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $dest));
					// fallback if DOCUMENT_ROOT not matching - use relative path
					if (empty($webPath) || $webPath === $dest) {
						$webPath = '/HIGH-Q/public/uploads/chat/' . $basename;
					}
					$attachmentHtml = '<div class="chat-attachment"><img src="' . htmlspecialchars($webPath) . '" alt="attachment"/></div>';
				}
			}

			// create thread if needed
			if (!$thread_id) {
				$ins = $pdo->prepare('INSERT INTO chat_threads (visitor_name, visitor_email, created_at) VALUES (?, ?, NOW())');
				$ins->execute([$visitor_name, $visitor_email]);
				$thread_id = (int)$pdo->lastInsertId();
			}

			$finalMessage = $message . $attachmentHtml;
			$ins2 = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_id, sender_name, message, is_from_staff, created_at) VALUES (?, NULL, ?, ?, 0, NOW())');
			$ins2->execute([$thread_id, $visitor_name, $finalMessage]);
			$upd = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id = ?');
			$upd->execute([$thread_id]);
			echo json_encode(['status'=>'ok','thread_id'=>$thread_id]); exit;
		}
	} catch (Throwable $e) {
		http_response_code(500);
		echo json_encode(['status'=>'error','message'=>'Server error']); exit;
	}
}

// GET handlers for fetching messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['action']) && $_GET['action'] === 'get_messages')) {
	header('Content-Type: application/json; charset=utf-8');
	$thread_id = intval($_GET['thread_id'] ?? 0);
	if (!$thread_id) { echo json_encode(['status'=>'error','message'=>'Missing thread']); exit; }
	$stmt = $pdo->prepare('SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at ASC');
	$stmt->execute([$thread_id]);
	$msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status'=>'ok','messages'=>$msgs]); exit;
}

?>

<div  class="chat-box" id="hqChatBox">
	<div class="chat-box-header">
		 	<h3>Message Us</h3>
			<p>
				<i class="fa fa-times"></i>	
			</p>
	</div>
	<div class="chat-box-body">
		<div class="chat-box-body-send">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
		<div class="chat-box-body-receive">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
		<div class="chat-box-body-receive">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
		<div class="chat-box-body-send">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
		<div class="chat-box-body-send">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
		<div class="chat-box-body-receive">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
		<div class="chat-box-body-receive">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
		<div class="chat-box-body-send">
			<p>This is my message.</p>
			<span>12:00</span>
		</div>
	</div>
	<div class="chat-box-footer">
			<button id="addExtra">
				<i class="fa fa-plus"></i>	
			</button>
			<input placeholder="Enter Your Message" type="text">
			<i class="send far fa-paper-plane"></i>
	</div>
</div>


<div class="chat-button">
	<span></span>
</div>


<div class="modal">
        <div class="modal-content">
            <span class="modal-close-button">&times;</span>
            <h1>Add What you want here.</h1>
        </div>
    </div>


    <style>
        @import url('https://fonts.googleapis.com/css?family=Raleway|Ubuntu&display=swap');
body{
	background: #E8EBF5;
	padding: 0;
	margin: 0;
	font-family: Raleway;
}

.chat-box{
		height: 90%;
    width: 400px;
    position: absolute;
    margin: 0 auto;
    overflow: hidden;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
	
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
    z-index: 15;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.005);
    right: 0;
    bottom: 0;
    margin: 15px;
		background: #fff;
		border-radius: 15px;
  
visibility: hidden;
	
		&-header{
			height: 8%;
			border-top-left-radius: 15px;
			border-top-right-radius: 15px;
			display: flex;
			font-size: 14px;
			padding: .5em 0;
    	box-shadow: 0 0 3px rgba(0,0,0,.2);
    	box-shadow:0 0 3px rgba(0,0,0,.2), 0 -1px 10px 				rgba(172, 54, 195, 0.3);
			box-shadow: 0 1px 10px rgba(0,0,0,0.025);
			& h3{
				font-family: ubuntu;
				font-weight: 400;
				float: left;
   		 	position: absolute;
    		left: 25px;
			}
			
			& p{
		    float: right;
    position: absolute;
    right: 16px;
    cursor: pointer;
    height: 50px;
    width: 50px;
    text-align: center;
    line-height: 3.25;
				margin: 0;
			}
		}
		&-body{
			height: 75%;
			background: #f8f8f8;
			overflow-y: scroll;
			padding: 12px;
			
			&-send{
			width: 250px;
    	float: right;
    	background: white;
    	padding: 10px 20px;
			border-radius: 5px;
			box-shadow: 0 0 10px rgba(0,0,0,.015);
			margin-bottom: 14px;
				& p{
					margin: 0;
					color: #444;
					font-size: 14px;
					margin-bottom: .25rem;
				}
				& span{
				float: right;
    		color: #777;
    		font-size: 10px;
				}
			}
			&-receive{
			width: 250px;
    	float: left;
    	background: white;
    	padding: 10px 20px;
			border-radius: 5px;
			box-shadow: 0 0 10px rgba(0,0,0,.015);
			margin-bottom: 14px;
				& p{
					margin: 0;
					color: #444;
					font-size: 14px;
					margin-bottom: .25rem;
				}
				& span{
				float: right;
    		color: #777;
    		font-size: 10px;
				}
			}
			&::-webkit-scrollbar {
  			 width: 5px;
				opacity: 0;
			}
		}
	&-footer{
		position: relative;
		display: flex;
		
		& button{
		border: none;
    padding: 16px;
    font-size: 14px;
    background: white;
    cursor: pointer;
			&:focus{
				outline:none;
			}
		}
		& input{
    padding: 10px;
    border: none;
    -webkit-appearance: none;
    border-radius: 50px;
    background: whitesmoke;
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