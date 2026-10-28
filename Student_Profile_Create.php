<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $_SESSION['error'] = "Please login to create your profile";
    header('Location: login.php');
    exit();
}

// Include MongoDB connection
require __DIR__ . '/vendor/autoload.php';

// Connect to MongoDB
$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$db = $client->uiurp;
$studentsCollection = $db->students;

// Get the current user's ID from the session
$userId = $_SESSION['user_id'];

// Check if a profile already exists
$existingProfile = $studentsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
if ($existingProfile) {
    // Profile already exists, redirect to edit page
    header('Location: Student_Profile_Edit.php');
    exit();
}

// Get basic user data from session
$userData = $_SESSION['user_data'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <style>
        .form-section {
            margin-bottom: 30px;
        }
        .section-title {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .add-btn {
            margin-top: 10px;
        }
        .form-floating {
            margin-bottom: 15px;
        }
        .welcome-banner {
            background: linear-gradient(135deg, #4cc9f0, #7209b7);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .dynamic-form-item {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            position: relative;
        }
        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #dc3545;
            font-size: 20px;
        }
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Create Your Student Profile</h1>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                </div>
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['info'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= $_SESSION['info'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['info']); ?>
                <?php endif; ?>

                <form action="src/controller/create_student_profile.php" method="POST" enctype="multipart/form-data">
                    <!-- Basic Information Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="section-title">Basic Information</h2>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Name" value="<?= htmlspecialchars($userData['name'] ?? '') ?>" required>
                                        <label for="name" class="required-field">Full Name</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="profile_image" class="form-label">Profile Image</label>
                                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                        <div class="form-text">Upload a profile picture (optional)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="section-title">Contact Information</h2>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="primary_email" name="primary_email" placeholder="Email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
                                        <label for="primary_email" class="required-field">Primary Email</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Information Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="section-title">Academic Information</h2>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Student ID" required>
                                        <label for="student_id" class="required-field">Student ID</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-control" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <option value="Computer Science and Engineering">Computer Science and Engineering</option>
                                            <option value="Electrical & Electronic Engineering">Electrical & Electronic Engineering</option>
                                            <option value="Civil Engineering">Civil Engineering</option>
                                            <option value="Business Administration">Business Administration</option>
                                            <option value="Data Science">Data Science</option>
                                        </select>
                                        <label for="department" class="required-field">Department</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-control" id="faculty" name="faculty" required>
                                            <option value="">Select Faculty</option>
                                            <option value="School of Science & Engineering">School of Science & Engineering</option>
                                            <option value="School of Business & Economics">School of Business & Economics</option>
                                        </select>
                                        <label for="faculty" class="required-field">Faculty</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="current_year_trimester" name="current_year_trimester" placeholder="Year/Trimester" required>
                                        <label for="current_year_trimester" class="required-field">Current Year & Trimester</label>
                                        <div class="form-text">Example: 2nd Year, Spring Trimester</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" required>
                                        <label for="enrollment_date" class="required-field">Enrollment Date</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="date" class="form-control" id="expected_graduation_date" name="expected_graduation_date" required>
                                        <label for="expected_graduation_date" class="required-field">Expected Graduation Date</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="number" step="0.01" min="0" max="4" class="form-control" id="cgpa" name="cgpa" placeholder="CGPA">
                                        <label for="cgpa">CGPA (if applicable)</label>
                                    </div>
                                </div>
                            </div>
                            
                            <h4 class="mt-4 mb-3">Current Degree Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="degree_name" name="degree_name" placeholder="Degree" required>
                                        <label for="degree_name" class="required-field">Degree Name</label>
                                        <div class="form-text">Example: B.Sc. in Computer Science and Engineering</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="major_field" name="major_field" placeholder="Major" required>
                                        <label for="major_field" class="required-field">Major Field of Study</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Research Profile Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="section-title">Research Profile</h2>
                            
                            <div class="mb-3">
                                <label for="research-interests-input" class="form-label">Research Interests</label>
                                <input type="text" class="form-control" id="research-interests-input" name="research_interests" placeholder="e.g. Machine Learning, Cybersecurity, Data Science">
                                <div class="form-text">Enter your research interests separated by commas</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="skills-input" class="form-label">Skills & Expertise</label>
                                <input type="text" class="form-control" id="skills-input" name="skills" placeholder="e.g. Python, JavaScript, React, MongoDB">
                                <div class="form-text">Enter your skills separated by commas</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Learning Resources Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="section-title">Learning Resources</h2>
                            <p class="text-muted mb-4">Share useful resources, tutorials, documentation, and learning materials that have helped you learn your skills. This is optional but helps other students discover valuable content!</p>
                            
                            <div id="learning-resources-container">
                                <!-- Learning resources will be added dynamically -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary add-btn" onclick="addLearningResource()">
                                <i class="bi bi-plus-circle"></i> Add Learning Resource
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Create Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Variable to track the current index for learning resources
        let learningResourceIndex = 0;
        
        // Function to add a new learning resource field
        function addLearningResource() {
            const container = document.getElementById('learning-resources-container');
            const newItem = document.createElement('div');
            newItem.className = 'dynamic-form-item';
            newItem.innerHTML = `
                <button type="button" class="remove-btn" onclick="removeLearningResource(this)">
                    <i class="bi bi-x-circle"></i>
                </button>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="learning_resources[${learningResourceIndex}][title]" placeholder="Title" required>
                            <label>Resource Title</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control" name="learning_resources[${learningResourceIndex}][url]" placeholder="URL" required>
                            <label>Resource URL</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <select class="form-control" name="learning_resources[${learningResourceIndex}][category]" required>
                                <option value="">Select Category</option>
                                <option value="Programming">Programming</option>
                                <option value="Machine Learning">Machine Learning</option>
                                <option value="Data Science">Data Science</option>
                                <option value="Web Development">Web Development</option>
                                <option value="Mobile Development">Mobile Development</option>
                                <option value="Cybersecurity">Cybersecurity</option>
                                <option value="Database">Database</option>
                                <option value="DevOps">DevOps</option>
                                <option value="Research Methods">Research Methods</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Statistics">Statistics</option>
                                <option value="Other">Other</option>
                            </select>
                            <label>Category</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <select class="form-control" name="learning_resources[${learningResourceIndex}][type]" required>
                                <option value="">Select Type</option>
                                <option value="Tutorial">Tutorial</option>
                                <option value="Documentation">Documentation</option>
                                <option value="Course">Online Course</option>
                                <option value="Video">Video</option>
                                <option value="Article">Article</option>
                                <option value="Book">Book</option>
                                <option value="Tool">Tool/Software</option>
                                <option value="Repository">Code Repository</option>
                                <option value="Dataset">Dataset</option>
                                <option value="Paper">Research Paper</option>
                            </select>
                            <label>Resource Type</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <textarea class="form-control" name="learning_resources[${learningResourceIndex}][description]" style="height: 80px;" placeholder="Description"></textarea>
                            <label>Description (optional)</label>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            learningResourceIndex++;
        }
        
        // Function to remove a learning resource field
        function removeLearningResource(button) {
            button.parentElement.remove();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 