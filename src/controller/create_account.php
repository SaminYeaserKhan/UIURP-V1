<?php
// create_account.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';

// Helper - sanitize input
function post($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

// Helper - strong password policy validation
function is_password_strong($password, $username, $email) {
    // Minimum 8 chars (Unicode safe), at least 1 uppercase, 1 lowercase, 1 digit, 1 special
    $lengthOk  = mb_strlen($password) >= 8;
    $upperOk   = (bool)preg_match('/[A-Z]/', $password);
    $lowerOk   = (bool)preg_match('/[a-z]/', $password);
    $digitOk   = (bool)preg_match('/\d/', $password);
    $specialOk = (bool)preg_match('/[^a-zA-Z\d]/', $password);

    // Prevent password from containing username or email local-part
    $passwordLower = strtolower($password);

    // Handle username
    $usernameOk = true;
    if (!empty($username) && strlen($username) >= 3) {
        $usernameOk = strpos($passwordLower, strtolower($username)) === false;
    }

    // Handle email local-part
    $emailLocal = strtolower(strtok((string)$email, '@')) ?: '';
    $emailOk = true;
    if (!empty($emailLocal) && strlen($emailLocal) >= 3) {
        $emailOk = strpos($passwordLower, $emailLocal) === false;
    }

    return $lengthOk && $upperOk && $lowerOk && $digitOk && $specialOk && $usernameOk && $emailOk;
}

// read real POST values from your login.php form
$username = post('username');
$email    = post('email');
$password = post('password'); // we'll hash before storing
$role     = post('role');

// Basic server-side presence checks
if (!$username || !$email || !$password || !$role) {
    echo "<p style='color:red;'>Missing required fields. Please go back and try again.</p>";
    exit;
}

// Enforce strong password policy before proceeding
if (!is_password_strong($password, $username, $email)) {
    $message = "Password is too weak. Use 8+ chars with upper, lower, number, special, and don't include your name/email.";

    // If AJAX request, return JSON
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        echo "<p style='color:red;'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
    }
    exit;
}

// Validate email by role
$studentPattern = '/^[a-zA-Z0-9._%+-]+@bscse\.uiu\.ac\.bd$/';
$facultyPattern = '/^[a-zA-Z0-9._%+-]+@cse\.uiu\.ac\.bd$/';

if ($role === 'student' && !preg_match($studentPattern, $email)) {
    echo "<p style='color:red;'>Invalid student email. It should end with @bscse.uiu.ac.bd</p>";
    exit;
}
if ($role === 'faculty' && !preg_match($facultyPattern, $email)) {
    echo "<p style='color:red;'>Invalid faculty email. It should end with @cse.uiu.ac.bd</p>";
    exit;
}

// Hash the password before saving to session/DB
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Pre-create an empty profile document based on role (to be removed if OTP fails)
try {
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;

    if ($role === 'student') {
        $collection = $db->students;
        $emptyDoc = [
            'basic_info' => [
                'name' => $username,
                'profile_image_url' => ''
            ],
            'contact_info' => [
                'primary_email' => $email
            ],
            'academic_info' => [
                'student_id_components' => [
                    'program_name' => '',
                    'program_code' => '',
                    'admission_year' => '',
                    'admission_trimester' => '',
                    'serial_number' => ''
                ],
                'student_id' => '',
                'department' => '',
                'faculty' => '',
                'current_status' => 'Pending Verification'
            ],
            'university_research_profile' => [
                'research_interests' => [],
                'skills_expertise' => [],
                'university_publications' => [],
                'university_projects' => []
            ],
            'platform_settings' => [
                'user_role' => 'student'
            ],
            'createdAt' => time(),
            'status' => 'pending_verification'
        ];
    } else {
        $collection = $db->faculties;
        $emptyDoc = [
            'name' => $username,
            'phone' => '',
            'job_title' => '',
            'bio' => '',
            'profile_image' => '',
            'interested_fields_of_research' => [],
            'department' => '',
            'projects' => [],
            'prerequisites' => [],
            'resources_to_learn_prerequisites' => [],
            'office_number' => '',
            'email' => $email,
            'createdAt' => time(),
            'status' => 'pending_verification'
        ];
    }

    $insertResult = $collection->insertOne($emptyDoc);
    $precreatedProfileId = (string) $insertResult->getInsertedId();
} catch (Exception $e) {
    echo "<p style='color:red;'>Failed to initialize profile: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Generate a 6-digit OTP
$otp = random_int(100000, 999999);

// Save registration data temporarly in session
$_SESSION['registration'] = [
    'username' => $username,
    'email'    => $email,
    'password' => $hashedPassword,
    'role'     => $role,
    'otp'      => $otp,
    'otp_generated_at' => time(),
    'profile_id' => $precreatedProfileId,
    'profile_collection' => ($role === 'student') ? 'students' : 'faculties'
];

// Send OTP via email
$subject = "Your UIURP OTP Verification Code";
$body    = "Hello {$username},\n\nYour OTP is: {$otp}\n\nIf you didn't request this, ignore this email.\n\n— UIURP Team";

$headers = "From: noreply@uiu.ac.bd\r\n";
$headers .= "Reply-To: noreply@uiu.ac.bd\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

if (mail($email, $subject, $body, $headers)) {
    // If AJAX request, return JSON so UI can open OTP modal
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email.']);
        exit;
    }
    header('Location: verify_otp.php');
} else {
    // Cleanup the pre-created profile if email fails
    try {
        if (!empty($precreatedProfileId)) {
            $col = ($_SESSION['registration']['profile_collection'] === 'students') ? $db->students : $db->faculties;
            $col->deleteOne(['_id' => new MongoDB\BSON\ObjectId($precreatedProfileId)]);
        }
    } catch (Exception $e) {
        // log silently
        error_log('Cleanup after mail failure: ' . $e->getMessage());
    }
    unset($_SESSION['registration']);
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again later.']);
        exit;
    }
    echo "<p style='color:red;'>Failed to send OTP. Your server mail() may not be configured. Configure SMTP or use PHPMailer.</p>";
    // For debugging, print the OTP
    // echo "<p>DEBUG OTP: {$otp}</p>";
}
