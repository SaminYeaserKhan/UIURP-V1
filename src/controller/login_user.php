<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Update the MongoDB connection string and database name
$client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $client->uiurp;
$loginInfoCollection = $db->login_info;

session_start(); // Start session to track user login status

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = $_POST['username'];
    $password = $_POST['Password'];
    
    

    // Allow login by email primarily; fallback to username for legacy accounts
    $user = $loginInfoCollection->findOne(['email' => $emailOrUsername]);
    if (!$user) {
        $user = $loginInfoCollection->findOne(['username' => $emailOrUsername]);
    }

    // Primary: hashed password_verify. Fallback: legacy plaintext, then upgrade to hash.
    $authenticated = false;
    if ($user) {
        if (is_string($user['password']) && preg_match('/^\$2y\$/' , (string)$user['password'])) {
            $authenticated = password_verify($password, $user['password']);
        } else if (isset($user['password']) && $password === $user['password']) {
            // Upgrade legacy plaintext to hash after successful auth
            $authenticated = true;
            try {
                $loginInfoCollection->updateOne(
                    ['_id' => $user['_id']],
                    ['$set' => ['password' => password_hash($password, PASSWORD_BCRYPT)]]
                );
            } catch (Exception $e) {
                // ignore upgrade errors
            }
        }
    }

    if ($authenticated) {
        $type = $user['type'];
        $id = $user['id'];

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Decide where to redirect after login (default: index.php)
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '/index.php';

        // Fetch data from the corresponding collection
        if ($type === 'faculty') {
            $facultyCollection = $db->faculties;
            $facultyData = $facultyCollection->findOne(['_id' => new ObjectId($id)]);
            if ($facultyData) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_type'] = 'faculty';
                $_SESSION['user_data'] = json_decode(json_encode($facultyData), true);
                $_SESSION['username'] = $facultyData['name'];
                $_SESSION['profile_pic'] = $facultyData['profile_image'];
                $_SESSION['email'] = $facultyData['email'];
                $_SESSION['user_id'] = (string)$facultyData['_id'];

                header("Location: " . $redirect);
                exit();
            }
        } elseif ($type === 'student') {
            $studentCollection = $db->students;
            $studentData = $studentCollection->findOne(['_id' => new ObjectId($id)]);
            if ($studentData) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_type'] = 'student';
                $_SESSION['username'] = $studentData['basic_info']['name'];
                $_SESSION['profile_pic'] = $studentData['basic_info']['profile_image_url'];
                $_SESSION['email'] = $studentData['contact_info']['primary_email'];
                $_SESSION['user_data'] = json_decode(json_encode($studentData), true);
                $_SESSION['user_id'] = (string)$studentData['_id'];

                header("Location: " . $redirect);
                exit();
            }
        }
    } else if ($user) {
        // Failed login attempt
        $failedAttempts = isset($user['failed_attempts']) ? (int)$user['failed_attempts'] : 0;
        $lastFailed = isset($user['last_failed_attempt']) && $user['last_failed_attempt'] instanceof UTCDateTime
            ? $user['last_failed_attempt']->toDateTime()->getTimestamp() : 0;
        // If last failed attempt was more than 15 minutes ago, reset counter
        if ($nowTs - $lastFailed > $fifteenMinutes) {
            $failedAttempts = 0;
        }
        $failedAttempts++;
        $update = [
            'failed_attempts' => $failedAttempts,
            'last_failed_attempt' => $now
        ];
        if ($failedAttempts >= 5) {
            $update['lockout_until'] = new UTCDateTime(($nowTs + $fifteenMinutes) * 1000);
            $_SESSION['error'] = 'Your account is locked. Try again after 15 minutes.';
        } else {
            $_SESSION['error'] = 'Invalid username or password';
        }
        $loginInfoCollection->updateOne(
            ['username' => $username],
            ['$set' => $update]
        );
        header('Location: /../../login.php');
        exit();
    }

    // If login fails and user not found
    $_SESSION['error'] = 'Invalid username or password';
    header('Location: /../../login.php');
    exit();
}
?>