<?php
session_start();
require 'vendor/autoload.php';

// Check if user is logged in and is a faculty member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'faculty') {
    die("Unauthorized access.");
}

$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$collection = $client->uiurp->faculties;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $id = new MongoDB\BSON\ObjectId($_POST['id']);
        
        // Verify the user is editing their own profile
        if ($_SESSION['user_id'] !== (string)$id) {
            die("Unauthorized access.");
        }

        // Basic validation
        if (empty($_POST['name']) || empty($_POST['bio'])) {
            die("Name and bio are required fields.");
        }

        $updateData = [
            'name' => trim($_POST['name']),
            'bio' => trim($_POST['bio']),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'office_number' => trim($_POST['office_number'] ?? '')
        ];

        // Handle Research Fields
        $researchFields = [];
        if (isset($_POST['research_fields']) && is_array($_POST['research_fields'])) {
            foreach ($_POST['research_fields'] as $field) {
                if (!empty(trim($field['name']))) {
                    $researchFields[] = [
                        'name' => trim($field['name']),
                        'description' => trim($field['description'] ?? '')
                    ];
                }
            }
        }
        $updateData['interested_fields_of_research'] = $researchFields;

        // Handle Publications/Projects
        $projects = [];
        if (isset($_POST['projects']) && is_array($_POST['projects'])) {
            foreach ($_POST['projects'] as $project) {
                if (!empty(trim($project['title']))) {
                    $projects[] = [
                        'title' => trim($project['title']),
                        'description' => trim($project['description'] ?? ''),
                        'link' => trim($project['link'] ?? '')
                    ];
                }
            }
        }
        $updateData['projects'] = $projects;

        // Handle Prerequisites
        $prerequisites = [];
        if (isset($_POST['prerequisites']) && is_array($_POST['prerequisites'])) {
            foreach ($_POST['prerequisites'] as $prerequisite) {
                if (!empty(trim($prerequisite['name']))) {
                    $prerequisites[] = [
                        'name' => trim($prerequisite['name']),
                        'level' => trim($prerequisite['level'] ?? 'Beginner'),
                        'description' => trim($prerequisite['description'] ?? '')
                    ];
                }
            }
        }
        $updateData['prerequisites'] = $prerequisites;

        // Handle Learning Resources
        $resources = [];
        if (isset($_POST['resources']) && is_array($_POST['resources'])) {
            foreach ($_POST['resources'] as $resource) {
                if (!empty(trim($resource['topic']))) {
                    $resources[] = [
                        'topic' => trim($resource['topic']),
                        'description' => trim($resource['description'] ?? ''),
                        'link' => trim($resource['link'] ?? '')
                    ];
                }
            }
        }
        $updateData['resources_to_learn_prerequisites'] = $resources;

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "uploads/profile_images/";
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $imagePath = $uploadDir . $fileName;
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($fileExtension), $allowedTypes)) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $imagePath)) {
                    $updateData['profile_image'] = $imagePath;
                }
            }
        }

        // Update the faculty document
        $result = $collection->updateOne(
            ['_id' => $id], 
            ['$set' => $updateData]
        );

        if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
            // Success - redirect to profile page
            header("Location: Faculty_Profile.php?id=" . $_POST['id']);
            exit();
        } else {
            // No changes made or faculty not found
            header("Location: Faculty_Profile.php?id=" . $_POST['id'] . "&error=no_changes");
            exit();
        }

    } catch (Exception $e) {
        // Log error for debugging
        error_log("Error updating faculty profile: " . $e->getMessage());
        
        // Redirect with error
        header("Location: Faculty_Profile.php?id=" . $_POST['id'] . "&error=update_failed");
        exit();
    }
} else {
    // Not a POST request
    header("Location: faculty_page.php");
    exit();
}
?>
