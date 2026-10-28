<?php
session_start();
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

// Update the MongoDB connection string and database name
$mongoClient = new Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;

$loginInfoCollection = $db->login_info;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a signup request (email and role fields are present)
    if (isset($_POST['email']) && isset($_POST['role'])) {
        // Handle signup
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = strtolower($_POST['role']);
        
        // Validate role (only allow 'resident' or 'admin')
        if ($role !== 'resident' && $role !== 'admin') {
            $_SESSION['error'] = 'Invalid role. Please use resident or admin.';
            header('Location: /../../login.php');
            exit();
        }
        
        // Check if username already exists
        $existingUser = $loginInfoCollection->findOne(['username' => $username]);
        if ($existingUser) {
            $_SESSION['error'] = 'Username already exists. Please choose another username.';
            header('Location: /../../login.php');
            exit();
        }
        
        // Create a new user ID
        $userId = (string) new MongoDB\BSON\ObjectId();
        
        // Add user to login_info collection
        $newUser = [
            'type' => $role === 'resident' ? 'student' : 'faculty', // Map resident to student, admin to faculty
            'id' => $userId,
            'username' => $username,
            'password' => $password
        ];
        
        $loginInfoCollection->insertOne($newUser);
        
        // Create corresponding user document in appropriate collection
        if ($role === 'resident') {
            $studentCollection = $db->students;
            $newStudent = [
                '_id' => new MongoDB\BSON\ObjectId($userId),
                'name' => $username,
                'email' => $email,
                'role' => $role
            ];
            $studentCollection->insertOne($newStudent);
        } else {
            $facultyCollection = $db->faculties;
            $newFaculty = [
                '_id' => new MongoDB\BSON\ObjectId($userId),
                'name' => $username,
                'email' => $email,
                'role' => $role
            ];
            $facultyCollection->insertOne($newFaculty);
        }
        
        // Set session and redirect
        $_SESSION['success'] = 'Account created successfully. Please log in.';
        header('Location: /../../login.php');
        exit();
    } else {
        // Handle login
        $username = $_POST['username'];
        $password = $_POST['Password'];
        
        // Check if username and password match
        $user = $loginInfoCollection->findOne(['username' => $username]);

        if ($user && $password === $user['password']) {
            $type = $user['type'];
            $id = $user['id'];
           
            // Fetch data from the corresponding collection
            if ($type === 'faculty') {
                $facultyCollection = $db->faculties;
                $facultyData = $facultyCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
                if ($facultyData) {
                    $_SESSION['user'] = $facultyData;
                    header('Location: /../../index.php'); // Redirect to faculty dashboard
                    exit();
                }
            } elseif ($type === 'student') {
                echo "<script>console.log('" . $id . "');</script>";
                $studentCollection = $db->students;
                $studentData = $studentCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
                echo "<script>console.log('" . json_encode($studentData) . "');</script>"; // Log the data for debugging
                if ($studentData) {
                    $_SESSION['user'] = $studentData;
                    header('Location: /../../index.php'); // Redirect to student dashboard
                    exit();
                }
            }
        }

        // If login fails
        $_SESSION['error'] = 'Invalid username or password';
        header('Location: /../../login.php'); // Redirect back to login page
        exit();
    }
}
?>
