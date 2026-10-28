<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $_SESSION['error'] = "Please login to update your profile";
    header('Location: /../../login.php');
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header('Location: /../../Student_Profile.php');
    exit();
}

// Include MongoDB connection
require __DIR__ . '/../../vendor/autoload.php';

// Connect to MongoDB
$client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $client->uiurp;
$studentsCollection = $db->students;

// Get the current user's ID from the session
$userId = $_SESSION['user_id'];

// Initialize the update array with the new structure
$updateData = [
    'basic_info.name' => $_POST['name'] ?? '',
    'contact_info.primary_email' => $_POST['primary_email'] ?? '',
    'academic_info.department' => $_POST['department'] ?? '',
    'academic_info.current_status' => $_POST['current_status'] ?? 'Active',
    'academic_info.current_year_trimester' => $_POST['current_year_trimester'] ?? '',
    'platform_settings.last_updated_date' => new MongoDB\BSON\UTCDateTime()
];

// Update CGPA if provided
if (isset($_POST['cgpa']) && !empty($_POST['cgpa'])) {
    $updateData['academic_info.cgpa'] = (float)$_POST['cgpa'];
}

// Update thesis information if provided
if (!empty($_POST['thesis_title']) || !empty($_POST['thesis_supervisor']) || !empty($_POST['thesis_status']) || !empty($_POST['thesis_description'])) {
    if (!empty($_POST['thesis_title'])) {
        $updateData['academic_info.thesis_info.title'] = $_POST['thesis_title'];
    }
    if (!empty($_POST['thesis_supervisor'])) {
        $updateData['academic_info.thesis_info.supervisor_name'] = $_POST['thesis_supervisor'];
    }
    if (!empty($_POST['thesis_status'])) {
        $updateData['academic_info.thesis_info.status'] = $_POST['thesis_status'];
    }
    if (!empty($_POST['thesis_description'])) {
        $updateData['academic_info.thesis_info.description'] = $_POST['thesis_description'];
    }
}

// Handle research interests (convert comma-separated string to array)
if (isset($_POST['research_interests']) && !empty($_POST['research_interests'])) {
    $interestsArray = array_map('trim', explode(',', $_POST['research_interests']));
    $interestsArray = array_filter($interestsArray, function($interest) {
        return !empty($interest);
    });
    $updateData['university_research_profile.research_interests'] = array_values($interestsArray);
} else {
    $updateData['university_research_profile.research_interests'] = [];
}

// Handle skills (convert comma-separated string to array)
if (isset($_POST['skills']) && !empty($_POST['skills'])) {
    $skillsArray = array_map('trim', explode(',', $_POST['skills']));
    $skillsArray = array_filter($skillsArray, function($skill) {
        return !empty($skill);
    });
    $updateData['university_research_profile.skills_expertise'] = array_values($skillsArray);
} else {
    $updateData['university_research_profile.skills_expertise'] = [];
}

// Handle publications
if (isset($_POST['publications']) && is_array($_POST['publications'])) {
    $publicationsData = [];
    foreach ($_POST['publications'] as $publication) {
        // Validate required fields
        if (!empty($publication['title']) && !empty($publication['authors']) && !empty($publication['type']) && !empty($publication['venue'])) {
            $authors = array_map('trim', explode(',', $publication['authors']));
            $publicationsData[] = [
                'title' => $publication['title'],
                'authors' => $authors,
                'publication_type' => $publication['type'],
                'venue_or_journal_name' => $publication['venue'],
                'publication_date' => !empty($publication['date']) ? new MongoDB\BSON\UTCDateTime(strtotime($publication['date']) * 1000) : null,
                'doi_url' => $publication['doi'] ?? '',
                'paper_url' => $publication['paper_url'] ?? '',
                'status' => $publication['status'] ?? 'Published',
                'abstract' => $publication['abstract'] ?? ''
            ];
        }
    }
    $updateData['university_research_profile.university_publications'] = $publicationsData;
} else {
    $updateData['university_research_profile.university_publications'] = [];
}

// Handle learning resources
if (isset($_POST['learning_resources']) && is_array($_POST['learning_resources'])) {
    $learningResourcesData = [];
    foreach ($_POST['learning_resources'] as $resource) {
        // Validate required fields
        if (!empty($resource['title']) && !empty($resource['url']) && !empty($resource['category']) && !empty($resource['type'])) {
            $learningResourcesData[] = [
                'title' => $resource['title'],
                'url' => $resource['url'],
                'category' => $resource['category'],
                'type' => $resource['type'],
                'description' => $resource['description'] ?? ''
            ];
        }
    }
    $updateData['learning_resources'] = $learningResourcesData;
} else {
    $updateData['learning_resources'] = [];
}

// Handle profile image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $_FILES['profile_image']['tmp_name']);
    finfo_close($fileInfo);
    
    if (in_array($mimeType, $allowedTypes)) {
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate a unique filename
        $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        // Move the uploaded file
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            // URL path for the image (relative to document root)
            $profileImagePath = 'uploads/profile_images/' . $filename;
            $updateData['basic_info.profile_image_url'] = $profileImagePath;
        } else {
            $_SESSION['error'] = "Failed to upload profile image";
            header('Location: /../../Student_Profile_Edit.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
        header('Location: /../../Student_Profile_Edit.php');
        exit();
    }
}

try {
    // Update the student record in MongoDB
    $result = $studentsCollection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($userId)],
        ['$set' => $updateData]
    );
    
    if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
        // Update the session data
        $updatedStudent = $studentsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
        $_SESSION['user_data'] = json_decode(json_encode($updatedStudent), true);
        
        $_SESSION['success'] = "Profile updated successfully";
    } else {
        $_SESSION['error'] = "No changes were made to your profile";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
}

// Redirect back to the profile page
header('Location: /../../Student_Profile.php');
exit();
?> 