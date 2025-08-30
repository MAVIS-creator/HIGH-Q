
require '../includes/db.php';
require '../includes/csrf.php';

$name     = trim($_POST['name']);
$phone    = trim($_POST['phone']);
$email    = trim($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Insert as pending, no role yet
$stmt = $pdo->prepare("
    INSERT INTO users (role_id, name, email, password, is_active)
    VALUES (NULL, ?, ?, ?, 0)
");
$stmt->execute([$name, $email, $password]);

// Send "pending approval" email
sendEmail($email, "Account Pending Approval", "
    Hi $name,<br><br>
    Thanks for registering with HIGH Q SOLID ACADEMY.<br>
    Your account is pending admin approval.<br>
    You’ll receive another email when approved.
");

// Redirect to a styled "Pending Approval" page
header("Location: pending.php");
exit;
