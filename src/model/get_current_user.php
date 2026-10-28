<?php
session_start();

// Get user data from session
$userData = null;
$isLoggedIn = false;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['user_data'])) {
    $isLoggedIn = true;
    $userData = $_SESSION['user_data'];
    
    // Extract user ID
    $userId = null;
    if (isset($userData['_id']['$oid'])) {
        $userId = $userData['_id']['$oid'];
    } elseif (isset($userData['_id'])) {
        $userId = (string)$userData['_id'];
    }
    
    // Extract name
    $userName = '';
    if (isset($userData['basic_info']['name'])) {
        $userName = $userData['basic_info']['name'];
    } elseif (isset($userData['name'])) {
        $userName = $userData['name'];
    }
    
    // Extract student ID
    $studentId = '';
    if (isset($userData['academic_info']['student_id'])) {
        $studentId = $userData['academic_info']['student_id'];
    } elseif (isset($userData['student_id'])) {
        $studentId = $userData['student_id'];
    }
    
    // Return formatted user data
    echo json_encode([
        'success' => true,
        'isLoggedIn' => $isLoggedIn,
        'user' => [
            'id' => $userId,
            'name' => $userName,
            'student_id' => $studentId,
            'role' => 'Creator', // Default role for project creator
            'contribution' => 100 // Default contribution for creator
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'isLoggedIn' => false,
        'user' => null
    ]);
}
?> 