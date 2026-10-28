<?php
// verify_otp.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';
// Check session existence
if (!isset($_SESSION['registration'])) {
    echo "<p style='color:red;'>No registration data found. Please register again.</p>";
    exit;
}

// If the form posted an OTP, validate it
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    // optional: check OTP expiry (e.g., 10 minutes = 600 seconds)
    $maxAge = 600;
    if (time() - $_SESSION['registration']['otp_generated_at'] > $maxAge) {
        // OTP expired — remove pre-created profile
        try {
            $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
            $db = $client->uiurp;
            $colName = $_SESSION['registration']['profile_collection'] ?? '';
            $profileId = $_SESSION['registration']['profile_id'] ?? '';
            if ($colName && $profileId) {
                $col = ($colName === 'students') ? $db->students : $db->faculties;
                $col->deleteOne(['_id' => new MongoDB\BSON\ObjectId($profileId)]);
            }
        } catch (Exception $e) {
            error_log('OTP expiry cleanup failed: ' . $e->getMessage());
        }
        echo "<p style='color:red;'>OTP expired. Please register again to receive a new OTP.</p>";
        unset($_SESSION['registration']);
        exit;
    }

    if ($enteredOtp === (string)$_SESSION['registration']['otp']) {
        // OTP correct — finalize account
        $data = $_SESSION['registration'];
        try {
            $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
            $db = $client->uiurp;

            // Ensure pre-created profile exists and mark as active
            $col = ($data['profile_collection'] === 'students') ? $db->students : $db->faculties;
            $profileId = $data['profile_id'];
            $col->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($profileId)],
                ['$set' => ['status' => 'active', 'verifiedAt' => time()]]
            );

            // Upsert into login_info by email to avoid duplicates/stale records
            $loginInfo = $db->login_info;
            $loginInfo->updateOne(
                ['email' => $data['email']],
                ['$set' => [
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'password' => $data['password'], // already hashed
                    'type' => $data['role'],
                    'id' => $profileId,
                    'createdAt' => time()
                ]],
                ['upsert' => true]
            );

            // If AJAX, return JSON instead of redirect
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            unset($_SESSION['registration']);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Account verified. Please log in.']);
                exit;
            }
            $_SESSION['success'] = 'Account verified. Please log in with your email and password.';
            header('Location: ../../login.php');
            exit;
        } catch (Exception $e) {
            // On failure, attempt cleanup of pre-created profile
            try {
                if (!empty($profileId)) {
                    $col->deleteOne(['_id' => new MongoDB\BSON\ObjectId($profileId)]);
                }
            } catch (Exception $inner) {
                error_log('Finalize cleanup failed: ' . $inner->getMessage());
            }
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to finalize account.']);
                exit;
            }
            echo "<p style='color:red;'>Failed to finalize account: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit;
        }
    } else {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
            exit;
        }
        echo "<p style='color:red;'>Invalid OTP. Try again.</p>";
    }
}
?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Verify OTP</title></head>
<body>
    <h2>Enter OTP sent to <?php echo htmlspecialchars($_SESSION['registration']['email']); ?></h2>
    <form action="verify_otp.php" method="post">
        <input type="text" name="otp" placeholder="Enter OTP" required autofocus>
        <button type="submit">Verify</button>
    </form>
    <p>If you didn't receive the email, check spam or come back and re-register.</p>
</body>
</html>
