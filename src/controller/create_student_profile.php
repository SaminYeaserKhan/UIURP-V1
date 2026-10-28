<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $_SESSION['error'] = "Please login to create your profile";
    header('Location: /../../login.php');
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header('Location: /../../Student_Profile_Create.php');
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

// Check if a profile already exists
$existingProfile = $studentsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
if ($existingProfile) {
    $_SESSION['info'] = "Your profile already exists. You can edit it here.";
    header('Location: /../../Student_Profile_Edit.php');
    exit();
}

// Function to parse student ID
function parseStudentId($studentId) {
    // Example student ID: 0112310001
    // First 3 digits: program_code
    // Next 2: admission_year  
    // Next 1: admission_trimester
    // Last 4: serial_number
    
    $components = [];
    if (strlen($studentId) == 10 && is_numeric($studentId)) {
        $components['program_code'] = substr($studentId, 0, 3);
        $components['admission_year'] = substr($studentId, 3, 2);
        $components['admission_trimester'] = substr($studentId, 5, 1);
        $components['serial_number'] = substr($studentId, 6, 4);
    }
    
    return $components;
}

// Parse student ID components
$studentIdComponents = parseStudentId($_POST['student_id'] ?? '');

// Initialize the data array with the new structure
$profileData = [
    '_id' => new MongoDB\BSON\ObjectId($userId),
    'basic_info' => [
        'name' => $_POST['name'] ?? '',
        'profile_image_url' => ''
    ],
    'contact_info' => [
        'primary_email' => $_POST['primary_email'] ?? ''
    ],
    'academic_info' => [
        'student_id_components' => array_merge([
            'program_name' => $_POST['degree_name'] ?? '',
            'program_code' => '',
            'admission_year' => '',
            'admission_trimester' => '',
            'serial_number' => ''
        ], $studentIdComponents),
        'student_id' => $_POST['student_id'] ?? '',
        'department' => $_POST['department'] ?? '',
        'faculty' => $_POST['faculty'] ?? '',
        'current_status' => 'Active',
        'enrollment_date' => !empty($_POST['enrollment_date']) ? new MongoDB\BSON\UTCDateTime(strtotime($_POST['enrollment_date']) * 1000) : null,
        'expected_graduation_date' => !empty($_POST['expected_graduation_date']) ? new MongoDB\BSON\UTCDateTime(strtotime($_POST['expected_graduation_date']) * 1000) : null,
        'current_year_trimester' => $_POST['current_year_trimester'] ?? '',
        'cgpa' => !empty($_POST['cgpa']) ? (float)$_POST['cgpa'] : null,
        'current_degree_info' => [
            'degree_level' => "Bachelor's", // Default for now
            'degree_name' => $_POST['degree_name'] ?? '',
            'institution_name' => 'United International University',
            'major_field_of_study' => $_POST['major_field'] ?? '',
            'start_date' => !empty($_POST['enrollment_date']) ? new MongoDB\BSON\UTCDateTime(strtotime($_POST['enrollment_date']) * 1000) : null
        ],
        'thesis_info' => null
    ],
    'university_research_profile' => [
        'research_interests' => [],
        'skills_expertise' => [],
        'university_publications' => [],
        'university_projects' => []
    ],
    'platform_settings' => [
        'user_role' => 'student',
        'account_creation_date' => new MongoDB\BSON\UTCDateTime(),
        'last_updated_date' => new MongoDB\BSON\UTCDateTime()
    ]
];

// Handle research interests (convert comma-separated string to array)
if (isset($_POST['research_interests']) && !empty($_POST['research_interests'])) {
    $interestsArray = array_map('trim', explode(',', $_POST['research_interests']));
    $interestsArray = array_filter($interestsArray, function($interest) {
        return !empty($interest);
    });
    $profileData['university_research_profile']['research_interests'] = array_values($interestsArray);
}

// Handle skills (convert comma-separated string to array)
if (isset($_POST['skills']) && !empty($_POST['skills'])) {
    $skillsArray = array_map('trim', explode(',', $_POST['skills']));
    $skillsArray = array_filter($skillsArray, function($skill) {
        return !empty($skill);
    });
    $profileData['university_research_profile']['skills_expertise'] = array_values($skillsArray);
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
    $profileData['learning_resources'] = $learningResourcesData;
} else {
    $profileData['learning_resources'] = [];
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
            $profileData['basic_info']['profile_image_url'] = $profileImagePath;
        } else {
            $_SESSION['error'] = "Failed to upload profile image";
            header('Location: /../../Student_Profile_Create.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
        header('Location: /../../Student_Profile_Create.php');
        exit();
    }
}

try {
    // Insert the new student profile into MongoDB
    $result = $studentsCollection->insertOne($profileData);
    
    if ($result->getInsertedCount() > 0) {
        // Update the session data
        $updatedStudent = $studentsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
        $_SESSION['user_data'] = json_decode(json_encode($updatedStudent), true);
        
        $_SESSION['success'] = "Profile created successfully!";
        // Redirect to the profile page
        header('Location: /../../Student_Profile.php');
    } else {
        $_SESSION['error'] = "Failed to create profile";
        header('Location: /../../Student_Profile_Create.php');
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error creating profile: " . $e->getMessage();
    header('Location: /../../Student_Profile_Create.php');
}

exit();
?> 