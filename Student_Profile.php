<?php
// Set secure session cookie parameters before session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $_SESSION['error'] = "Please login to view profiles";
    header('Location: login.php');
    exit();
}

// Session idle timeout (30 minutes)
$timeout = 30 * 60; // 30 minutes in seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Include MongoDB connection
require __DIR__ . '/vendor/autoload.php';

// Helper function to safely convert various ObjectId formats
function convertToObjectId($id) {
    if (empty($id)) {
        return null;
    }
    
    try {
        // If it's already a MongoDB ObjectId object
        if (is_object($id) && $id instanceof MongoDB\BSON\ObjectId) {
            return $id;
        }
        
        // If it's an array (serialized ObjectId format)
        if (is_array($id)) {
            if (isset($id['$oid'])) {
                return new MongoDB\BSON\ObjectId($id['$oid']);
            }
            // Sometimes it might be nested differently
            if (isset($id['oid'])) {
                return new MongoDB\BSON\ObjectId($id['oid']);
            }
        }
        
        // If it's a string representation
        if (is_string($id)) {
            // Remove any whitespace
            $id = trim($id);
            // Check if it's a valid 24-character hex string
            if (strlen($id) === 24 && ctype_xdigit($id)) {
                return new MongoDB\BSON\ObjectId($id);
            }
        }
        
        // If we can't convert it, log for debugging and return null
        error_log("Unable to convert ID to ObjectId: " . print_r($id, true));
        return null;
        
    } catch (Exception $e) {
        error_log("Error converting ID to ObjectId: " . $e->getMessage() . " | ID: " . print_r($id, true));
        return null;
    }
}

// Connect to MongoDB
$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$db = $client->uiurp;
$studentsCollection = $db->students;
$loginInfoCollection = $db->login_info;

// Get user ID - either from URL parameter (viewing other profiles) or session (own profile)
$userId = null;
$isOwnProfile = false;
$targetUserId = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Viewing another user's profile
    try {
        $userId = new MongoDB\BSON\ObjectId($_GET['id']);
        $targetUserId = $userId;
        
        // First, get the student data to check if it exists
        $targetStudent = $studentsCollection->findOne(['_id' => $userId]);
        if (!$targetStudent) {
            $_SESSION['error'] = "Profile not found";
            header('Location: Research_page.php');
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Invalid profile ID";
        header('Location: Research_page.php');
        exit();
    }
} else {
    // Viewing own profile
    $isOwnProfile = true;
    // Get current user's ID from session using the helper function
    if (isset($_SESSION['user_data']['_id'])) {
        $targetUserId = convertToObjectId($_SESSION['user_data']['_id']);
        
        if (!$targetUserId) {
            $_SESSION['error'] = "Invalid user session data";
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "User session incomplete";
        header('Location: login.php');
        exit();
    }
}

// Check user type from login_info collection using the user ID
if ($targetUserId) {
    $loginInfo = $loginInfoCollection->findOne(['id' => $targetUserId]);
    
    if ($loginInfo && isset($loginInfo['type'])) {
        $accountType = $loginInfo['type'];
        
        // Debug: Log the account type for troubleshooting
        // error_log("Account type for user ID $targetUserId: $accountType");
        
        // If the user is faculty type, redirect to Faculty_Profile.php
        if ($accountType === 'faculty') {
            // Debug: Log the redirect
            // error_log("Redirecting faculty user ID $targetUserId to Faculty_Profile.php?id=$targetUserId");
            header("Location: Faculty_Profile.php?id=" . $targetUserId);
            exit();
        }
        // If type is 'student' or anything else, continue with student profile
    } else {
        // If no login info found or no type field, assume student
        // This provides backward compatibility for existing accounts
        // error_log("No login info or type found for user ID $targetUserId, assuming student");
    }
}

// Fetch the student data from MongoDB
if ($isOwnProfile) {
    $student = $_SESSION['user_data'];
} else {
    $studentDoc = $studentsCollection->findOne(['_id' => $userId]);
    if (!$studentDoc) {
        $_SESSION['error'] = "Student profile not found";
        header('Location: Research_page.php');
        exit();
    }
    // Convert to array for consistent handling and ensure deep array conversion
    $student = json_decode(json_encode($studentDoc), true);
}

// Ensure data consistency - normalize the data structure
if ($student) {
    // Ensure basic_info exists
    if (!isset($student['basic_info'])) {
        $student['basic_info'] = [];
    }
    
    // Ensure academic_info exists
    if (!isset($student['academic_info'])) {
        $student['academic_info'] = [];
    }
    
    // Ensure university_research_profile exists
    if (!isset($student['university_research_profile'])) {
        $student['university_research_profile'] = [];
    }
    
    // Ensure contact_info exists
    if (!isset($student['contact_info'])) {
        $student['contact_info'] = [];
    }
    
    // Normalize name field
    if (!isset($student['basic_info']['name']) && isset($student['name'])) {
        $student['basic_info']['name'] = $student['name'];
    }
    
    // Normalize email field
    if (!isset($student['contact_info']['primary_email']) && isset($student['email'])) {
        $student['contact_info']['primary_email'] = $student['email'];
    }
    
    // Normalize student_id field
    if (!isset($student['academic_info']['student_id']) && isset($student['student_id'])) {
        $student['academic_info']['student_id'] = $student['student_id'];
    }
    
    // Normalize research interests
    if (!isset($student['university_research_profile']['research_interests']) && isset($student['research_interests'])) {
        $student['university_research_profile']['research_interests'] = $student['research_interests'];
    }
    
    // Normalize skills
    if (!isset($student['university_research_profile']['skills_expertise']) && isset($student['skills'])) {
        $student['university_research_profile']['skills_expertise'] = $student['skills'];
    }
    
    // Ensure learning_resources exists
    if (!isset($student['learning_resources'])) {
        $student['learning_resources'] = [];
    }
}

// Handle profile image with proper placeholder
$profileImage = null;

// Try multiple possible locations for profile image
$possibleImagePaths = [
    $student['basic_info']['profile_image_url'] ?? null,
    $student['profile_image'] ?? null,
    $student['profile_image_url'] ?? null,
    $student['basic_info']['profile_image'] ?? null
];

foreach ($possibleImagePaths as $imagePath) {
    if (!empty($imagePath) && is_string($imagePath) && trim($imagePath) !== '') {
        $profileImage = $imagePath;
        break;
    }
}

// If no valid profile image found, use placeholder
if (empty($profileImage)) {
    $profileImage = 'assets/resources/user_avater.png';
}

// Verify placeholder exists, if not use a backup
if (!file_exists($profileImage)) {
    // Try alternative placeholders
    $placeholders = [
        'assets/resources/user_avater.png',
        'assets/resources/imgPlaceholder.png',
        'assets/resources/default-profile.jpg',
        'https://via.placeholder.com/250x250/6c757d/ffffff?text=Student'
    ];
    
    $profileImage = 'https://via.placeholder.com/250x250/6c757d/ffffff?text=Student'; // Default fallback
    
    foreach ($placeholders as $placeholder) {
        if (file_exists($placeholder)) {
            $profileImage = $placeholder;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?= htmlspecialchars($student['basic_info']['name'] ?? $student['name'] ?? 'Student') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/styles/student-profile.css">
</head>
<body>
  <?php include 'src/includes/navbar.php'; ?>

  <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
    
  <div class="container-fluid profile-header">
    <div class="container">
      <div class="row align-items-center">
          <div class="col-md-8">
              <h1 class="display-4">Hi, I'm <strong><?= htmlspecialchars($student['basic_info']['name'] ?? $student['name'] ?? 'Student') ?></strong></h1>
              <p class="lead text-secondary">Student ID: <?= htmlspecialchars($student['academic_info']['student_id'] ?? $student['student_id'] ?? 'N/A') ?></p>
              
              <div class="info-item">
                  <i class="bi bi-mortarboard-fill"></i> 
                  <strong><?= htmlspecialchars($student['academic_info']['department'] ?? $student['department'] ?? 'Department') ?></strong>
              </div>
              
              <div class="info-item">
                  <i class="bi bi-building"></i> 
                  <?= htmlspecialchars($student['academic_info']['faculty'] ?? $student['faculty'] ?? 'Faculty') ?>
              </div>
              
              <div class="info-item">
                  <i class="bi bi-calendar3"></i> 
                  <?= htmlspecialchars($student['academic_info']['current_year_trimester'] ?? $student['current_year_trimester'] ?? 'Year/Trimester') ?>
              </div>
              
              <div class="info-item">
                  <i class="bi bi-envelope"></i> 
                  <?= htmlspecialchars($student['contact_info']['primary_email'] ?? $student['email'] ?? 'email@example.com') ?>
              </div>
              
              <?php 
              $cgpa = $student['academic_info']['cgpa'] ?? $student['cgpa'] ?? null;
              if($cgpa !== null): ?>
              <div class="info-item">
                  <i class="bi bi-graph-up"></i> 
                  CGPA: <strong><?= htmlspecialchars($cgpa) ?></strong>
              </div>
              <?php endif; ?>
              
              <div class="mt-3">
                  <span class="badge bg-<?= ($student['academic_info']['current_status'] ?? $student['current_status'] ?? 'Active') === 'Active' ? 'success' : 'secondary' ?> status-badge">
                      <?= htmlspecialchars($student['academic_info']['current_status'] ?? $student['current_status'] ?? 'Active') ?>
                  </span>
              </div>
          </div>
          
          <div class="col-md-4 profile-image-container">
              <div class="profile-image-wrapper">
                  <img src="<?= htmlspecialchars($profileImage) ?>" 
                       alt="Profile Picture" 
                       loading="lazy"
                       onerror="this.onerror=null; this.src='https://via.placeholder.com/250x250/6c757d/ffffff?text=Student'; this.alt='Default Profile Picture';">
          </div>
              <?php if ($isOwnProfile): ?>
              <div class="mt-3">
                  <a href="Student_Profile_Edit.php" class="profile-edit-btn me-2">
                      <i class="bi bi-pencil-square"></i> Edit Profile
                  </a>
                  <button type="button" class="profile-edit-btn" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                      <i class="bi bi-calendar-week"></i> Schedule
                  </button>
              </div>
              <?php else: ?>
              <div class="mt-3">
                  <button type="button" class="profile-edit-btn" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                      <i class="bi bi-calendar-week"></i> View Schedule
                  </button>
              </div>
              <?php endif; ?>
          </div>
      </div>
    </div>
  </div>

  <!-- Academic Information Section -->
  <section class="profile-section py-5">
    <div class="container">
        <h2 class="mb-4">Academic Information</h2>
        <div class="row">
            <div class="col-md-12">
                <div class="card section-card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($student['academic_info']['current_degree_info']['degree_name'] ?? $student['degree_name'] ?? $student['degree'] ?? 'Degree Information') ?></h5>
                        <h6 class="card-subtitle mb-2 text-secondary"><?= htmlspecialchars($student['academic_info']['current_degree_info']['institution_name'] ?? $student['institution'] ?? 'United International University') ?></h6>
                        <p class="card-text">
                            <strong>Major:</strong> <?= htmlspecialchars($student['academic_info']['current_degree_info']['major_field_of_study'] ?? $student['major_field'] ?? $student['field'] ?? 'N/A') ?><br>
                            <strong>Enrollment:</strong> <?php 
                                // Handle MongoDB date format properly
                                if (isset($student['academic_info']['enrollment_date'])) {
                                    $enrollmentDate = $student['academic_info']['enrollment_date'];
                                    if (is_array($enrollmentDate) && isset($enrollmentDate['$date'])) {
                                        if (is_array($enrollmentDate['$date']) && isset($enrollmentDate['$date']['$numberLong'])) {
                                            // MongoDB format with $numberLong (milliseconds)
                                            $timestamp = intval($enrollmentDate['$date']['$numberLong']) / 1000;
                                            echo date('F Y', $timestamp);
                                        } elseif (is_string($enrollmentDate['$date'])) {
                                            // Direct date string format
                                            echo date('F Y', strtotime($enrollmentDate['$date']));
                                        } else {
                                            echo 'N/A';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                } elseif (isset($student['enrollment_date'])) {
                                    // Fallback to old structure
                                    $enrollmentDate = $student['enrollment_date'];
                                    if (is_array($enrollmentDate) && isset($enrollmentDate['$date'])) {
                                        if (is_array($enrollmentDate['$date']) && isset($enrollmentDate['$date']['$numberLong'])) {
                                            $timestamp = intval($enrollmentDate['$date']['$numberLong']) / 1000;
                                            echo date('F Y', $timestamp);
                                        } elseif (is_string($enrollmentDate['$date'])) {
                                            echo date('F Y', strtotime($enrollmentDate['$date']));
                                        } else {
                                            echo 'N/A';
                                        }
                                    } elseif (is_string($enrollmentDate)) {
                                        echo date('F Y', strtotime($enrollmentDate));
                                    } else {
                                        echo 'N/A';
                                    }
                                } else {
                                    echo 'N/A';
                                }
                            ?><br>
                            <strong>Expected Graduation:</strong> <?php 
                                // Handle MongoDB date format properly  
                                if (isset($student['academic_info']['expected_graduation_date'])) {
                                    $graduationDate = $student['academic_info']['expected_graduation_date'];
                                    if (is_array($graduationDate) && isset($graduationDate['$date'])) {
                                        if (is_array($graduationDate['$date']) && isset($graduationDate['$date']['$numberLong'])) {
                                            // MongoDB format with $numberLong (milliseconds)
                                            $timestamp = intval($graduationDate['$date']['$numberLong']) / 1000;
                                            echo date('F Y', $timestamp);
                                        } elseif (is_string($graduationDate['$date'])) {
                                            // Direct date string format
                                            echo date('F Y', strtotime($graduationDate['$date']));
                                        } else {
                                            echo 'N/A';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                } elseif (isset($student['expected_graduation_date'])) {
                                    // Fallback to old structure
                                    $graduationDate = $student['expected_graduation_date'];
                                    if (is_array($graduationDate) && isset($graduationDate['$date'])) {
                                        if (is_array($graduationDate['$date']) && isset($graduationDate['$date']['$numberLong'])) {
                                            $timestamp = intval($graduationDate['$date']['$numberLong']) / 1000;
                                            echo date('F Y', $timestamp);
                                        } elseif (is_string($graduationDate['$date'])) {
                                            echo date('F Y', strtotime($graduationDate['$date']));
                                        } else {
                                            echo 'N/A';
                                        }
                                    } elseif (is_string($graduationDate)) {
                                        echo date('F Y', strtotime($graduationDate));
                                    } else {
                                        echo 'N/A';
                                    }
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </section>

  <!-- Thesis Information Section (if exists) -->
  <?php if(isset($student['academic_info']['thesis_info']) && $student['academic_info']['thesis_info'] !== null): ?>
  <section class="profile-section bg-light py-5">
    <div class="container">
        <h2 class="mb-4">Thesis Information</h2>
        <div class="row">
            <div class="col-md-12">
                <div class="card section-card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($student['academic_info']['thesis_info']['title'] ?? 'Thesis Title') ?></h5>
                        <p class="card-text">
                            <strong>Supervisor:</strong> <span id="thesis-supervisor-display"><?= htmlspecialchars($student['academic_info']['thesis_info']['supervisor_name'] ?? 'N/A') ?></span><br>
                            <strong>Status:</strong> <span class="badge bg-info"><?= htmlspecialchars($student['academic_info']['thesis_info']['status'] ?? 'N/A') ?></span><br>
                            <strong>Description:</strong> <?= htmlspecialchars($student['academic_info']['thesis_info']['description'] ?? 'N/A') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Research Interests & Skills Section -->
  <section class="profile-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h3 class="mb-3">Research Interests</h3>
                <div class="card section-card">
                    <div class="card-body">
                        <?php 
                        $researchInterests = $student['university_research_profile']['research_interests'] ?? $student['research_interests'] ?? [];
                        if(is_array($researchInterests) && count($researchInterests) > 0): ?>
                            <?php foreach($researchInterests as $interest): ?>
                                <span class="skills-tag"><?= htmlspecialchars($interest) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-secondary">No research interests added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h3 class="mb-3">Skills & Expertise</h3>
                <div class="card section-card">
                    <div class="card-body">
                        <?php 
                        $skills = $student['university_research_profile']['skills_expertise'] ?? $student['skills'] ?? [];
                        if(is_array($skills) && count($skills) > 0): ?>
                            <?php foreach($skills as $skill): ?>
                                <span class="skills-tag"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-secondary">No skills added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </section>

  <!-- My Research Projects Section -->
  <section class="profile-section py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Research Projects</h2>
            <div>
                <a href="project_management.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-eye"></i> View All Projects
                </a>
                <a href="project_management.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Manage Projects
                </a>
            </div>
        </div>
        
        <!-- Projects Loading -->
        <div class="text-center" id="projects-loading" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading projects...</span>
            </div>
            <p class="mt-2 text-secondary">Loading your research projects...</p>
        </div>
        
        <!-- Projects Grid -->
        <div class="projects-grid" id="user-projects-grid">
            <!-- Projects will be loaded here via JavaScript -->
        </div>
        
        <!-- Random Selection Note -->
        <div class="text-center mt-3" id="projects-note" style="display: none;">
        </div>
        
        <!-- No Projects Message -->
        <div class="empty-state" id="no-projects" style="display: none;">
            <div class="mb-3">
                <i class="bi bi-folder2-open text-secondary" style="font-size: 4rem;"></i>
            </div>
            <h4 class="text-secondary">No Research Projects Yet</h4>
            <p class="text-secondary">You haven't joined any research projects yet. Start by creating a new project or join an existing one!</p>
            <div class="mt-3">
                <a href="project_management.php" class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle"></i> Create Your First Project
                </a>
                <a href="Research_page.php" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i> Browse Projects
                </a>
            </div>
        </div>
    </div>
  </section>

  <!-- Learning Resources Section -->
  <section class="profile-section learning-resources-section py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Learning Resources</h2>
            <?php if ($isOwnProfile): ?>
            <a href="Student_Profile_Edit.php#learning-resources" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square"></i> Edit Resources
            </a>
            <?php endif; ?>
        </div>
        
        <?php 
        $learningResources = $student['learning_resources'] ?? [];
        if(is_array($learningResources) && count($learningResources) > 0): ?>
            <div class="row">
                <?php foreach($learningResources as $resource): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card learning-resource-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-bookmark-star me-2"></i>
                                    <?= htmlspecialchars($resource['title']) ?>
                                </h5>
                                <?php if(!empty($resource['description'])): ?>
                                    <p class="card-text"><?= htmlspecialchars($resource['description']) ?></p>
                                <?php endif; ?>
                                <div class="mb-2">
                                    <span class="badge"><?= htmlspecialchars($resource['category']) ?></span>
                                    <span class="badge"><?= htmlspecialchars($resource['type']) ?></span>
                                </div>
                                <a href="<?= htmlspecialchars($resource['url']) ?>" target="_blank" class="btn btn-sm">
                                    <i class="bi bi-box-arrow-up-right"></i> Access Resource
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="mb-3">
                    <i class="bi bi-book text-secondary" style="font-size: 4rem;"></i>
                </div>
                <h4 class="text-secondary">No Learning Resources Added</h4>
                <p class="text-secondary">Share useful resources, tutorials, and learning materials related to your skills and interests!</p>
                <?php if ($isOwnProfile): ?>
                <a href="Student_Profile_Edit.php#learning-resources" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Your First Resource
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
  </section>

  <!-- Publications Section -->
  <?php if(isset($student['university_research_profile']['university_publications']) && count($student['university_research_profile']['university_publications']) > 0): ?>
  <section class="profile-section bg-light py-5">
    <div class="container">
        <h2 class="mb-4">Publications</h2>
        <div class="row">
            <div class="col-md-12">
                <?php foreach($student['university_research_profile']['university_publications'] as $publication): ?>
                    <div class="publication-item">
                        <h5><?= htmlspecialchars($publication['title']) ?></h5>
                        <p class="text-secondary mb-2">
                            <strong>Authors:</strong> <?= htmlspecialchars(implode(', ', $publication['authors'])) ?><br>
                            <strong>Type:</strong> <?= htmlspecialchars($publication['publication_type']) ?><br>
                            <strong>Venue:</strong> <?= htmlspecialchars($publication['venue_or_journal_name']) ?><br>
                            <strong>Date:</strong> <?php 
                                if (isset($publication['publication_date'])) {
                                    $pubDate = $publication['publication_date'];
                                    if (is_array($pubDate) && isset($pubDate['$date'])) {
                                        if (is_array($pubDate['$date']) && isset($pubDate['$date']['$numberLong'])) {
                                            // MongoDB format with $numberLong (milliseconds)
                                            $timestamp = intval($pubDate['$date']['$numberLong']) / 1000;
                                            echo date('F Y', $timestamp);
                                        } elseif (is_string($pubDate['$date'])) {
                                            // Direct date string format
                                            echo date('F Y', strtotime($pubDate['$date']));
                                        } else {
                                            echo 'N/A';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                } else {
                                    echo 'N/A';
                                }
                            ?><br>
                            <?php if(isset($publication['abstract'])): ?>
                                <strong>Abstract:</strong> <?= htmlspecialchars($publication['abstract']) ?>
                            <?php endif; ?>
                        </p>
                        <div>
                            <?php if(isset($publication['doi_url'])): ?>
                                <a href="<?= htmlspecialchars($publication['doi_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-link-45deg"></i> DOI
                                </a>
                            <?php endif; ?>
                            <?php if(isset($publication['paper_url'])): ?>
                                <a href="<?= htmlspecialchars($publication['paper_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-file-earmark-pdf"></i> View Paper
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Footer -->
  <?php include 'src/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Enhanced function to create clickable profile links with fallback
    async function createProfileLink(name, userId, userType = null) {
        if (!name || name === 'N/A') return name;
        
        // Primary method: Use userId if available
        if (userId) {
            try {
                const response = await fetch('src/model/check_profile_exists.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ userId: userId, userType: userType })
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.exists) {
                        const profileType = result.type === 'faculty' ? 'Faculty_Profile.php' : 'Student_Profile.php';
                        return `<a href="${profileType}?id=${userId}" class="supervisor-link" title="View ${result.type} profile">${name}</a>`;
                    }
                }
            } catch (error) {
                console.log('Profile check failed for user:', userId);
            }
        }
        
        // Fallback method: Try to find supervisor by name in global faculty data
        if (userType === 'faculty' || !userType) {
            try {
                // Check if global faculty data is available
                if (window.facultyData && Array.isArray(window.facultyData)) {
                    const matchingFaculty = window.facultyData.find(faculty => 
                        faculty.name && faculty.name.toLowerCase().trim() === name.toLowerCase().trim()
                    );
                    
                    if (matchingFaculty && matchingFaculty._id) {
                        console.log(`Found faculty by name: ${name} -> ${matchingFaculty._id}`);
                        return `<a href="Faculty_Profile.php?id=${matchingFaculty._id}" class="supervisor-link" title="View faculty profile">${name}</a>`;
                    }
                }
                
                // If global faculty data is not available, try to fetch it
                if (!window.facultyData) {
                    const facultyResponse = await fetch('src/model/load_faculty.php');
                    if (facultyResponse.ok) {
                        const facultyData = await facultyResponse.json();
                        window.facultyData = facultyData; // Cache for future use
                        
                        const matchingFaculty = facultyData.find(faculty => 
                            faculty.name && faculty.name.toLowerCase().trim() === name.toLowerCase().trim()
                        );
                        
                        if (matchingFaculty && matchingFaculty._id) {
                            console.log(`Found faculty by name (from fetch): ${name} -> ${matchingFaculty._id}`);
                            return `<a href="Faculty_Profile.php?id=${matchingFaculty._id}" class="supervisor-link" title="View faculty profile">${name}</a>`;
                        }
                    }
                }
            } catch (error) {
                console.log('Faculty name lookup failed:', error);
            }
        }
        
        // Return original name if no profile found
        return name;
    }
    
    // Load user projects on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadUserProjects();
        makeSupervisorClickable();
    });
    
    // Make thesis supervisor clickable
    function makeSupervisorClickable() {
        const supervisorElement = document.getElementById('thesis-supervisor-display');
        if (supervisorElement) {
            const supervisorName = supervisorElement.textContent.trim();
            if (supervisorName && supervisorName !== 'N/A') {
                // Try to make supervisor clickable
                createProfileLink(supervisorName, null, 'faculty').then(linkedName => {
                    if (linkedName !== supervisorName) {
                        supervisorElement.innerHTML = linkedName;
                    }
                }).catch(error => {
                    console.log('Failed to create supervisor profile link:', error);
                });
            }
        }
    }
    
    function loadUserProjects() {
        const projectsGrid = document.getElementById('user-projects-grid');
        const projectsLoading = document.getElementById('projects-loading');
        const noProjects = document.getElementById('no-projects');
        
        // Show loading
        if (projectsLoading) projectsLoading.style.display = 'block';
        if (noProjects) noProjects.style.display = 'none';
        if (projectsGrid) projectsGrid.innerHTML = '';
        
        // Fetch all user projects (both owned and member projects)
        // Use the same endpoint as project_management.php
        fetch('src/model/get_user_projects.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading
                if (projectsLoading) projectsLoading.style.display = 'none';
                if (projectsGrid) projectsGrid.innerHTML = '';
                
                const projectsNote = document.getElementById('projects-note');
                
                if (data.success && data.projects && data.projects.length > 0) {
                    const projects = data.projects;
                    
                    // Limit to first 3 projects for profile display
                    const displayProjects = projects.slice(0, 3);
                    const hasMoreProjects = projects.length > 3;
                    
                    // Display projects
                    displayProjects.forEach((project, index) => {
                        try {
                            const projectCard = createProjectCard(project);
                            if (projectsGrid) projectsGrid.appendChild(projectCard);
                        } catch (cardError) {
                            console.error('Error creating project card:', cardError);
                        }
                    });
                    
                    // Show note about displaying limited projects
                    if (projectsNote) {
                        if (hasMoreProjects) {
                            projectsNote.innerHTML = `
                                <p class="text-secondary mb-2">
                                    <i class="bi bi-info-circle"></i> 
                                    Showing 3 of ${projects.length} research projects
                                </p>
                                <a href="project_management.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-arrow-right"></i> View All ${projects.length} Projects
                                </a>
                            `;
                        } else {
                            projectsNote.innerHTML = `
                                <p class="text-secondary">
                                    <i class="bi bi-check-circle"></i> 
                                    Showing all ${projects.length} research project${projects.length === 1 ? '' : 's'}
                                </p>
                            `;
                        }
                        projectsNote.style.display = 'block';
                    }
                } else {
                    // Show no projects message
                    if (noProjects) noProjects.style.display = 'block';
                    if (projectsNote) projectsNote.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading projects:', error);
                if (projectsLoading) projectsLoading.style.display = 'none';
                if (noProjects) noProjects.style.display = 'block';
                const projectsNote = document.getElementById('projects-note');
                if (projectsNote) projectsNote.style.display = 'none';
                
                // Update no projects message for error case
                const noProjectsTitle = noProjects ? noProjects.querySelector('h4') : null;
                const noProjectsText = noProjects ? noProjects.querySelector('p') : null;
                if (noProjectsTitle) noProjectsTitle.textContent = 'Error Loading Projects';
                if (noProjectsText) noProjectsText.textContent = 'There was an error loading your research projects. Please try again later.';
            });
    }
    
    function createProjectCard(project) {
        // Get project ID
        const projectId = project._id && project._id.$oid ? project._id.$oid : project._id;
        
        // Format date
        const formattedDate = formatProjectDate(project.createdAt);
        
        // Get project image
        const imageSrc = getProjectImage(project.coverImage);
        
        // Format supervisor
        const supervisor = formatSupervisor(project.supervisor);
        
        // Truncate description
        const description = truncateText(project.abstract || project.description || 'No description available', 120);
        
        // Format team members
        const teamMembers = project.members ? project.members.length + ' team member(s)' : '0 team members';
        
        // Determine badge based on privacy and user role
        let badgeClass = 'research-project-badge';
        let badgeText = 'Public';
        
        if (project.userRole === 'owner') {
            badgeClass += ' owner';
            badgeText = 'Owner';
        } else if (project.userRole === 'member') {
            badgeClass += ' member';
            badgeText = 'Member';
        }
        
        if (project.privacy === 1) {
            badgeClass += ' private';
            badgeText += ' (Private)';
        }
        
        const card = document.createElement('a');
        card.href = `Project_details.php?id=${projectId}`;
        card.className = 'research-project-card';
        card.style.textDecoration = 'none';
        card.innerHTML = `
            <div class="research-card-image">
                <img src="${imageSrc}" alt="${project.title}" loading="lazy" onerror="this.src='assets/resources/research_picture/pub_1.jpg'">
            </div>
            <div class="research-card-content">
                <div class="${badgeClass}">${badgeText}</div>
                <h3 class="research-card-title">${project.title}</h3>
                <p class="research-card-description">${description}</p>
                <div class="research-project-meta">
                    <div class="research-meta-item">
                        <i class="research-meta-icon far fa-calendar-alt"></i>
                        <span>${formattedDate}</span>
                    </div>
                    <div class="research-meta-item">
                        <i class="research-meta-icon fas fa-chalkboard-teacher"></i>
                        <span>Supervisor: ${supervisor}</span>
                    </div>
                    <div class="research-meta-item">
                        <i class="research-meta-icon fas fa-users"></i>
                        <span>${teamMembers}</span>
                    </div>
                    <div class="research-meta-item">
                        <i class="research-meta-icon fas fa-graduation-cap"></i>
                        <span>${project.field || 'Research'}</span>
                    </div>
                </div>
            </div>
        `;
        
        return card;
    }
    
    function formatProjectDate(dateInput) {
        if (!dateInput) return 'Not specified';
        
        try {
            let dateValue = dateInput;
            
            if (typeof dateInput === 'object') {
                // MongoDB date object with $date property
                if (dateInput.$date) {
                    if (typeof dateInput.$date === 'string') {
                        dateValue = dateInput.$date;
                    } else if (typeof dateInput.$date === 'object' && dateInput.$date.$numberLong) {
                        // Handle MongoDB long format
                        dateValue = parseInt(dateInput.$date.$numberLong);
                    } else {
                        dateValue = dateInput.$date;
                    }
                }
            }
            
            const date = new Date(dateValue);
            
            if (isNaN(date.getTime())) {
                return 'Date not available';
            }
            
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        } catch (error) {
            console.error('Date formatting error:', error);
            return 'Date not available';
        }
    }
    
    function getProjectImage(coverImage) {
        const defaultImages = [
            'assets/resources/research_picture/pub_1.jpg',
            'assets/resources/research_picture/pub_2.jpg',
            'assets/resources/research_picture/pub_3.jpg',
            'assets/resources/research_picture/pub_4.jpg',
            'assets/resources/research_picture/pub_5.jpg',
            'assets/resources/research_picture/pub_6.jpg',
            'assets/resources/research_picture/pub_7.jpg',
            'assets/resources/research_picture/pub_8.jpeg',
            'assets/resources/research_picture/pub_9.jpeg',
            'assets/resources/research_picture/pub_10.jpeg'
        ];

        if (coverImage && coverImage.url && coverImage.url.trim()) {
            return coverImage.url;
        }
        
        return defaultImages[Math.floor(Math.random() * defaultImages.length)];
    }
    
    function formatSupervisor(supervisor) {
        if (!supervisor) return 'Not specified';
        
        if (typeof supervisor === 'string') return supervisor;
        if (typeof supervisor === 'object') {
            return supervisor.name || supervisor.userId || 'Not specified';
        }
        
        return 'Not specified';
    }
    
    function truncateText(text, maxLength) {
        if (!text || text.length <= maxLength) return text || '';
        return text.substring(0, maxLength) + '...';
    }
  </script>

  <!-- Schedule Modal -->
  <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content glass-card">
        <div class="modal-header">
          <h5 class="modal-title" id="scheduleModalLabel">
            <?= htmlspecialchars($student['basic_info']['name'] ?? $student['name'] ?? 'Student') ?>'s Schedule
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Loading indicator -->
          <div id="schedule-loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading schedule...</span>
            </div>
            <p class="mt-2 text-secondary">Loading schedule data...</p>
          </div>
          
          <!-- Schedule Display -->
          <div id="schedule-container" style="display: none;">
            <div id="schedule-list" class="mb-4">
              <!-- Schedule items will be displayed here -->
            </div>
            
            <!-- No schedules message -->
            <div id="no-schedules" class="empty-state" style="display: none;">
              <i class="bi bi-calendar-x"></i>
              <h4>No Schedule Items</h4>
              <p>No schedule items have been added yet.</p>
            </div>
          </div>
          
          <!-- Add/Edit Schedule Form -->
          <div id="schedule-form-container" style="display: none;">
            <h5 class="mb-3" id="form-title">Add Schedule Item</h5>
            <form id="schedule-form">
              <input type="hidden" id="schedule-id" value="">
              
              <div class="mb-3">
                <label for="schedule-title" class="form-label">Title*</label>
                <input type="text" class="form-control" id="schedule-title" required>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="schedule-day" class="form-label">Day*</label>
                  <select class="form-select" id="schedule-day" required>
                    <option value="">Select Day</option>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="schedule-type" class="form-label">Type*</label>
                  <select class="form-select" id="schedule-type" required>
                    <option value="">Select Type</option>
                    <option value="Class">Class</option>
                    <option value="Lab">Lab</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Study">Study Session</option>
                    <option value="Research">Research Work</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="schedule-start-time" class="form-label">Start Time*</label>
                  <input type="time" class="form-control" id="schedule-start-time" required>
                </div>
                <div class="col-md-6">
                  <label for="schedule-end-time" class="form-label">End Time*</label>
                  <input type="time" class="form-control" id="schedule-end-time" required>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="schedule-location" class="form-label">Location</label>
                <input type="text" class="form-control" id="schedule-location">
              </div>
              
              <div class="mb-3">
                <label for="schedule-description" class="form-label">Description</label>
                <textarea class="form-control" id="schedule-description" rows="3"></textarea>
              </div>
              
              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="cancel-schedule-form">Cancel</button>
                <div class="buttons-wrapper d-flex">
                  <button type="button" class="btn btn-danger me-2" id="delete-schedule" style="display: none;">Delete</button>
                  <button type="submit" class="btn btn-primary" id="save-schedule">Save</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="modal-footer">
          <?php if ($isOwnProfile): ?>
            <button type="button" class="btn btn-primary" id="add-schedule-btn">
              <i class="bi bi-plus-circle"></i> Add Schedule Item
            </button>
          <?php endif; ?>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add the schedule JavaScript before the closing body tag -->
  <script>
    // Schedule Management
    document.addEventListener('DOMContentLoaded', function() {
      // Variables
      const userId = <?= json_encode((string)$targetUserId) ?>;
      const isOwnProfile = <?= json_encode($isOwnProfile) ?>;
      const scheduleModal = document.getElementById('scheduleModal');
      const scheduleLoading = document.getElementById('schedule-loading');
      const scheduleContainer = document.getElementById('schedule-container');
      const scheduleList = document.getElementById('schedule-list');
      const noSchedules = document.getElementById('no-schedules');
      const scheduleFormContainer = document.getElementById('schedule-form-container');
      const scheduleForm = document.getElementById('schedule-form');
      const addScheduleBtn = document.getElementById('add-schedule-btn');
      const cancelFormBtn = document.getElementById('cancel-schedule-form');
      const deleteScheduleBtn = document.getElementById('delete-schedule');
      
      // Initialize schedule modal
      if (scheduleModal) {
        scheduleModal.addEventListener('shown.bs.modal', function() {
          loadSchedule();
        });
      }
      
      // Add schedule button
      if (addScheduleBtn) {
        addScheduleBtn.addEventListener('click', function() {
          showScheduleForm();
        });
      }
      
      // Cancel form button
      if (cancelFormBtn) {
        cancelFormBtn.addEventListener('click', function() {
          hideScheduleForm();
        });
      }
      
      // Schedule form submission
      if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
          e.preventDefault();
          saveSchedule();
        });
      }
      
      // Delete schedule button
      if (deleteScheduleBtn) {
        deleteScheduleBtn.addEventListener('click', function() {
          deleteSchedule();
        });
      }
      
      // Load schedule data
      function loadSchedule() {
        if (!scheduleLoading || !scheduleContainer || !scheduleList || !noSchedules) return;
        
        scheduleLoading.style.display = 'block';
        scheduleContainer.style.display = 'none';
        scheduleFormContainer.style.display = 'none';
        
        // Fetch schedule data from server
        fetch('src/model/get_schedule.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ userId: userId })
        })
        .then(response => response.json())
        .then(data => {
          scheduleLoading.style.display = 'none';
          scheduleContainer.style.display = 'block';
          
          if (data.success && data.schedule && data.schedule.length > 0) {
            scheduleList.innerHTML = '';
            noSchedules.style.display = 'none';
            
            // Sort schedule items by day of week
            const dayOrder = {
              'Monday': 1,
              'Tuesday': 2,
              'Wednesday': 3,
              'Thursday': 4,
              'Friday': 5,
              'Saturday': 6,
              'Sunday': 7
            };
            
            data.schedule.sort((a, b) => {
              if (dayOrder[a.day] !== dayOrder[b.day]) {
                return dayOrder[a.day] - dayOrder[b.day];
              }
              return a.startTime.localeCompare(b.startTime);
            });
            
            // Group schedule items by day
            const scheduleByDay = {};
            data.schedule.forEach(item => {
              if (!scheduleByDay[item.day]) {
                scheduleByDay[item.day] = [];
              }
              scheduleByDay[item.day].push(item);
            });
            
            // Create day sections
            for (const [day, items] of Object.entries(scheduleByDay)) {
              const daySection = document.createElement('div');
              daySection.className = 'mb-4';
              
              const dayHeader = document.createElement('h5');
              dayHeader.className = 'mb-3';
              dayHeader.textContent = day;
              daySection.appendChild(dayHeader);
              
              // Create schedule items for the day
              items.forEach(item => {
                const scheduleItem = createScheduleItem(item);
                daySection.appendChild(scheduleItem);
              });
              
              scheduleList.appendChild(daySection);
            }
          } else {
            scheduleList.innerHTML = '';
            noSchedules.style.display = 'block';
          }
        })
        .catch(error => {
          console.error('Error loading schedule:', error);
          scheduleLoading.style.display = 'none';
          scheduleContainer.style.display = 'block';
          scheduleList.innerHTML = '';
          noSchedules.style.display = 'block';
          
          const noSchedulesTitle = noSchedules.querySelector('h4');
          const noSchedulesText = noSchedules.querySelector('p');
          if (noSchedulesTitle) noSchedulesTitle.textContent = 'Error Loading Schedule';
          if (noSchedulesText) noSchedulesText.textContent = 'There was an error loading the schedule data. Please try again later.';
        });
      }
      
      // Create schedule item element
      function createScheduleItem(item) {
        const scheduleItem = document.createElement('div');
        scheduleItem.className = 'card section-card mb-3';
        scheduleItem.setAttribute('data-id', item._id);
        
        // Format time
        const startTime = formatTime(item.startTime);
        const endTime = formatTime(item.endTime);
        const timeString = `${startTime} - ${endTime}`;
        
        // Choose icon based on type
        let typeIcon = 'bi-calendar-event';
        switch (item.type) {
          case 'Class': typeIcon = 'bi-book'; break;
          case 'Lab': typeIcon = 'bi-flask'; break;
          case 'Meeting': typeIcon = 'bi-people'; break;
          case 'Study': typeIcon = 'bi-pencil-square'; break;
          case 'Research': typeIcon = 'bi-search'; break;
          default: typeIcon = 'bi-calendar-event';
        }
        
        scheduleItem.innerHTML = `
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="card-title mb-0">${item.title}</h5>
              <span class="badge bg-primary">${item.type}</span>
            </div>
            <div class="d-flex align-items-center text-secondary mb-2">
              <i class="bi bi-clock me-2"></i>
              <span>${timeString}</span>
            </div>
            ${item.location ? `
              <div class="d-flex align-items-center text-secondary mb-2">
                <i class="bi bi-geo-alt me-2"></i>
                <span>${item.location}</span>
              </div>
            ` : ''}
            ${item.description ? `
              <p class="card-text mt-2">${item.description}</p>
            ` : ''}
          </div>
        `;
        
        // Add edit capability if own profile
        if (isOwnProfile) {
          scheduleItem.style.cursor = 'pointer';
          scheduleItem.addEventListener('click', function() {
            editSchedule(item);
          });
        }
        
        return scheduleItem;
      }
      
      // Format time for display
      function formatTime(timeString) {
        try {
          const [hours, minutes] = timeString.split(':');
          const hour = parseInt(hours);
          const ampm = hour >= 12 ? 'PM' : 'AM';
          const hour12 = hour % 12 || 12;
          return `${hour12}:${minutes} ${ampm}`;
        } catch (e) {
          return timeString;
        }
      }
      
      // Show schedule form
      function showScheduleForm(scheduleData = null) {
        if (!scheduleFormContainer) return;
        
        scheduleContainer.style.display = 'none';
        scheduleFormContainer.style.display = 'block';
        
        // Reset form
        scheduleForm.reset();
        document.getElementById('schedule-id').value = '';
        deleteScheduleBtn.style.display = 'none';
        
        // If editing, populate form with schedule data
        if (scheduleData) {
          document.getElementById('form-title').textContent = 'Edit Schedule Item';
          document.getElementById('schedule-id').value = scheduleData._id;
          document.getElementById('schedule-title').value = scheduleData.title;
          document.getElementById('schedule-day').value = scheduleData.day;
          document.getElementById('schedule-type').value = scheduleData.type;
          document.getElementById('schedule-start-time').value = scheduleData.startTime;
          document.getElementById('schedule-end-time').value = scheduleData.endTime;
          document.getElementById('schedule-location').value = scheduleData.location || '';
          document.getElementById('schedule-description').value = scheduleData.description || '';
          deleteScheduleBtn.style.display = 'block';
        } else {
          document.getElementById('form-title').textContent = 'Add Schedule Item';
        }
      }
      
      // Hide schedule form
      function hideScheduleForm() {
        if (!scheduleFormContainer) return;
        
        scheduleFormContainer.style.display = 'none';
        scheduleContainer.style.display = 'block';
      }
      
      // Edit schedule
      function editSchedule(scheduleData) {
        showScheduleForm(scheduleData);
      }
      
      // Save schedule
      function saveSchedule() {
        const scheduleId = document.getElementById('schedule-id').value;
        const scheduleData = {
          userId: userId,
          title: document.getElementById('schedule-title').value,
          day: document.getElementById('schedule-day').value,
          type: document.getElementById('schedule-type').value,
          startTime: document.getElementById('schedule-start-time').value,
          endTime: document.getElementById('schedule-end-time').value,
          location: document.getElementById('schedule-location').value,
          description: document.getElementById('schedule-description').value
        };
        
        if (scheduleId) {
          scheduleData._id = scheduleId;
        }
        
        // Save to server
        const endpoint = scheduleId ? 'update_schedule.php' : 'add_schedule.php';
        fetch(`src/model/${endpoint}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(scheduleData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Reload schedule data
            hideScheduleForm();
            loadSchedule();
          } else {
            alert('Error saving schedule: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error saving schedule:', error);
          alert('Error saving schedule. Please try again later.');
        });
      }
      
      // Delete schedule
      function deleteSchedule() {
        const scheduleId = document.getElementById('schedule-id').value;
        if (!scheduleId) return;
        
        if (confirm('Are you sure you want to delete this schedule item?')) {
          fetch('src/model/delete_schedule.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
              userId: userId,
              scheduleId: scheduleId 
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Reload schedule data
              hideScheduleForm();
              loadSchedule();
            } else {
              alert('Error deleting schedule: ' + (data.message || 'Unknown error'));
            }
          })
          .catch(error => {
            console.error('Error deleting schedule:', error);
            alert('Error deleting schedule. Please try again later.');
          });
        }
      }
    });
  </script>

  <!-- Show timeout message if redirected due to inactivity -->
  <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
      <script>
          alert('Your session has expired due to inactivity. Please log in again.');
      </script>
  <?php endif; ?>
</body>
</html>
