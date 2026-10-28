<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $_SESSION['error'] = "Please login to edit your profile";
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

// Fetch the student data from MongoDB
$studentData = $studentsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);

// If student data not found, redirect to create profile page
if (!$studentData) {
    $_SESSION['info'] = "You need to set up your profile first";
    header('Location: Student_Profile_Create.php');
    exit();
}

// Convert MongoDB document to an array
$student = json_decode(json_encode($studentData), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?= htmlspecialchars($student['basic_info']['name'] ?? 'Student') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/styles/student_profile_edit.css">
    
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <!-- Page Header -->
                <div class="page-header fade-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1>Edit Profile</h1>
                            <p class="subtitle">Update your academic and research information</p>
                        </div>
                        <a href="Student_Profile.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Profile
                        </a>
                    </div>
                </div>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action="src/controller/update_student_profile.php" method="POST" enctype="multipart/form-data">
                    <!-- Basic Information Section -->
                    <div class="modern-card fade-in">
                        <h2 class="section-title">
                            <i class="bi bi-person me-2"></i>Basic Information
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Name" value="<?= htmlspecialchars($student['basic_info']['name'] ?? $student['name'] ?? '') ?>" required>
                                    <label for="name" class="required-field">Full Name</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="primary_email" name="primary_email" placeholder="Email" value="<?= htmlspecialchars($student['contact_info']['primary_email'] ?? $student['email'] ?? '') ?>" required>
                                    <label for="primary_email" class="required-field">Primary Email</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Profile Image</label>
                                <div class="file-upload-area">
                                    <?php 
                                    $currentImage = $student['basic_info']['profile_image_url'] ?? $student['profile_image'] ?? $student['profile_image_url'] ?? null;
                                    if(!empty($currentImage)): ?>
                                        <img src="<?= htmlspecialchars($currentImage) ?>" alt="Current Profile Image" class="current-image">
                                    <?php else: ?>
                                        <i class="bi bi-camera fs-1 text-muted mb-3 d-block"></i>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                    <div class="form-text mt-2">Upload a new image to replace the current one</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Information Section -->
                    <div class="modern-card fade-in">
                        <h2 class="section-title">
                            <i class="bi bi-mortarboard me-2"></i>Academic Information
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Student ID" value="<?= htmlspecialchars($student['academic_info']['student_id'] ?? $student['student_id'] ?? '') ?>" readonly>
                                    <label for="student_id">Student ID (Cannot be changed)</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-control" id="department" name="department">
                                        <option value="">Select Department</option>
                                        <option value="Computer Science and Engineering" <?= ($student['academic_info']['department'] ?? $student['department'] ?? '') == 'Computer Science and Engineering' ? 'selected' : '' ?>>Computer Science and Engineering</option>
                                        <option value="Electrical & Electronic Engineering" <?= ($student['academic_info']['department'] ?? $student['department'] ?? '') == 'Electrical & Electronic Engineering' ? 'selected' : '' ?>>Electrical & Electronic Engineering</option>
                                        <option value="Civil Engineering" <?= ($student['academic_info']['department'] ?? $student['department'] ?? '') == 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                                        <option value="Business Administration" <?= ($student['academic_info']['department'] ?? $student['department'] ?? '') == 'Business Administration' ? 'selected' : '' ?>>Business Administration</option>
                                        <option value="Data Science" <?= ($student['academic_info']['department'] ?? $student['department'] ?? '') == 'Data Science' ? 'selected' : '' ?>>Data Science</option>
                                    </select>
                                    <label for="department">Department</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-control" id="current_status" name="current_status">
                                        <option value="Active" <?= ($student['academic_info']['current_status'] ?? $student['current_status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                                        <option value="Graduated" <?= ($student['academic_info']['current_status'] ?? $student['current_status'] ?? '') == 'Graduated' ? 'selected' : '' ?>>Graduated</option>
                                        <option value="On Leave" <?= ($student['academic_info']['current_status'] ?? $student['current_status'] ?? '') == 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                    </select>
                                    <label for="current_status">Current Status</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="current_year_trimester" name="current_year_trimester" placeholder="Year/Trimester" value="<?= htmlspecialchars($student['academic_info']['current_year_trimester'] ?? $student['current_year_trimester'] ?? '') ?>">
                                    <label for="current_year_trimester">Current Year & Trimester</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" step="0.01" min="0" max="4" class="form-control" id="cgpa" name="cgpa" placeholder="CGPA" value="<?= htmlspecialchars($student['academic_info']['cgpa'] ?? $student['cgpa'] ?? '') ?>">
                                    <label for="cgpa">CGPA</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Research Profile Section -->
                    <div class="modern-card fade-in">
                        <h2 class="section-title">
                            <i class="bi bi-research me-2"></i>Research Profile
                        </h2>
                        
                        <div class="mb-3">
                            <label for="research-interests-input" class="form-label">Research Interests</label>
                            <input type="text" class="form-control" id="research-interests-input" name="research_interests" 
                                placeholder="e.g. Machine Learning, Cybersecurity, Data Science" 
                                value="<?= htmlspecialchars(isset($student['university_research_profile']['research_interests']) && is_array($student['university_research_profile']['research_interests']) ? implode(', ', $student['university_research_profile']['research_interests']) : '') ?>">
                            <div class="form-text">Enter your research interests separated by commas</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="skills-input" class="form-label">Skills & Expertise</label>
                            <input type="text" class="form-control" id="skills-input" name="skills" 
                                placeholder="e.g. Python, JavaScript, React, MongoDB" 
                                value="<?= htmlspecialchars(isset($student['university_research_profile']['skills_expertise']) && is_array($student['university_research_profile']['skills_expertise']) ? implode(', ', $student['university_research_profile']['skills_expertise']) : '') ?>">
                            <div class="form-text">Enter your skills separated by commas</div>
                        </div>
                    </div>
                    
                    <!-- Publications Section -->
                    <div class="modern-card fade-in">
                        <h2 class="section-title">
                            <i class="bi bi-book me-2"></i>Publications
                        </h2>
                        
                        <div class="section-container">
                            <div id="publications-container">
                                <?php if(isset($student['university_research_profile']['university_publications']) && is_array($student['university_research_profile']['university_publications']) && count($student['university_research_profile']['university_publications']) > 0): ?>
                                    <?php foreach($student['university_research_profile']['university_publications'] as $index => $publication): ?>
                                        <div class="dynamic-form-item">
                                            <button type="button" class="remove-btn" onclick="removePublication(this)">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" name="publications[<?= $index ?>][title]" placeholder="Title" value="<?= htmlspecialchars($publication['title'] ?? '') ?>" required>
                                                        <label>Publication Title</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" name="publications[<?= $index ?>][authors]" placeholder="Authors" value="<?= htmlspecialchars(isset($publication['authors']) ? implode(', ', $publication['authors']) : '') ?>" required>
                                                        <label>Authors (comma separated)</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <select class="form-control" name="publications[<?= $index ?>][type]" required>
                                                            <option value="">Select Type</option>
                                                            <option value="Journal Article" <?= ($publication['publication_type'] ?? '') == 'Journal Article' ? 'selected' : '' ?>>Journal Article</option>
                                                            <option value="Conference Paper" <?= ($publication['publication_type'] ?? '') == 'Conference Paper' ? 'selected' : '' ?>>Conference Paper</option>
                                                            <option value="Book Chapter" <?= ($publication['publication_type'] ?? '') == 'Book Chapter' ? 'selected' : '' ?>>Book Chapter</option>
                                                        </select>
                                                        <label>Publication Type</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" name="publications[<?= $index ?>][venue]" placeholder="Venue" value="<?= htmlspecialchars($publication['venue_or_journal_name'] ?? '') ?>" required>
                                                        <label>Journal/Conference Name</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="date" class="form-control" name="publications[<?= $index ?>][date]" value="<?= isset($publication['publication_date']['$date']) ? date('Y-m-d', strtotime($publication['publication_date']['$date'])) : '' ?>">
                                                        <label>Publication Date</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <select class="form-control" name="publications[<?= $index ?>][status]">
                                                            <option value="Published" <?= ($publication['status'] ?? '') == 'Published' ? 'selected' : '' ?>>Published</option>
                                                            <option value="Accepted" <?= ($publication['status'] ?? '') == 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                                                            <option value="Under Review" <?= ($publication['status'] ?? '') == 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                                                        </select>
                                                        <label>Status</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="url" class="form-control" name="publications[<?= $index ?>][doi]" placeholder="DOI" value="<?= htmlspecialchars($publication['doi_url'] ?? '') ?>">
                                                        <label>DOI URL (optional)</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="url" class="form-control" name="publications[<?= $index ?>][paper_url]" placeholder="Paper URL" value="<?= htmlspecialchars($publication['paper_url'] ?? '') ?>">
                                                        <label>Paper URL (optional)</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-floating">
                                                        <textarea class="form-control" name="publications[<?= $index ?>][abstract]" style="height: 100px;" placeholder="Abstract"><?= htmlspecialchars($publication['abstract'] ?? '') ?></textarea>
                                                        <label>Abstract (optional)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="section-buttons">
                                <button type="button" class="btn add-btn" onclick="addPublication()">
                                    <i class="bi bi-plus-circle"></i>Add Publication
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Learning Resources Section -->
                    <div class="modern-card fade-in">
                        <h2 class="section-title">
                            <i class="bi bi-book-half me-2"></i>Learning Resources
                        </h2>
                        <p class="text-muted mb-4">Share useful resources, tutorials, documentation, and learning materials related to your skills and interests. Help other students discover valuable learning content!</p>
                        
                        <div class="section-container">
                            <div id="learning-resources-container">
                                <?php if(isset($student['learning_resources']) && is_array($student['learning_resources']) && count($student['learning_resources']) > 0): ?>
                                    <?php foreach($student['learning_resources'] as $index => $resource): ?>
                                        <div class="dynamic-form-item">
                                            <button type="button" class="remove-btn" onclick="removeLearningResource(this)">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" name="learning_resources[<?= $index ?>][title]" placeholder="Title" value="<?= htmlspecialchars($resource['title'] ?? '') ?>" required>
                                                        <label>Resource Title</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <input type="url" class="form-control" name="learning_resources[<?= $index ?>][url]" placeholder="URL" value="<?= htmlspecialchars($resource['url'] ?? '') ?>" required>
                                                        <label>Resource URL</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <select class="form-control" name="learning_resources[<?= $index ?>][category]" required>
                                                            <option value="">Select Category</option>
                                                            <option value="Programming" <?= ($resource['category'] ?? '') == 'Programming' ? 'selected' : '' ?>>Programming</option>
                                                            <option value="Machine Learning" <?= ($resource['category'] ?? '') == 'Machine Learning' ? 'selected' : '' ?>>Machine Learning</option>
                                                            <option value="Data Science" <?= ($resource['category'] ?? '') == 'Data Science' ? 'selected' : '' ?>>Data Science</option>
                                                            <option value="Web Development" <?= ($resource['category'] ?? '') == 'Web Development' ? 'selected' : '' ?>>Web Development</option>
                                                            <option value="Mobile Development" <?= ($resource['category'] ?? '') == 'Mobile Development' ? 'selected' : '' ?>>Mobile Development</option>
                                                            <option value="Cybersecurity" <?= ($resource['category'] ?? '') == 'Cybersecurity' ? 'selected' : '' ?>>Cybersecurity</option>
                                                            <option value="Database" <?= ($resource['category'] ?? '') == 'Database' ? 'selected' : '' ?>>Database</option>
                                                            <option value="DevOps" <?= ($resource['category'] ?? '') == 'DevOps' ? 'selected' : '' ?>>DevOps</option>
                                                            <option value="Research Methods" <?= ($resource['category'] ?? '') == 'Research Methods' ? 'selected' : '' ?>>Research Methods</option>
                                                            <option value="Mathematics" <?= ($resource['category'] ?? '') == 'Mathematics' ? 'selected' : '' ?>>Mathematics</option>
                                                            <option value="Statistics" <?= ($resource['category'] ?? '') == 'Statistics' ? 'selected' : '' ?>>Statistics</option>
                                                            <option value="Other" <?= ($resource['category'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                                        </select>
                                                        <label>Category</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <select class="form-control" name="learning_resources[<?= $index ?>][type]" required>
                                                            <option value="">Select Type</option>
                                                            <option value="Tutorial" <?= ($resource['type'] ?? '') == 'Tutorial' ? 'selected' : '' ?>>Tutorial</option>
                                                            <option value="Documentation" <?= ($resource['type'] ?? '') == 'Documentation' ? 'selected' : '' ?>>Documentation</option>
                                                            <option value="Course" <?= ($resource['type'] ?? '') == 'Course' ? 'selected' : '' ?>>Online Course</option>
                                                            <option value="Video" <?= ($resource['type'] ?? '') == 'Video' ? 'selected' : '' ?>>Video</option>
                                                            <option value="Article" <?= ($resource['type'] ?? '') == 'Article' ? 'selected' : '' ?>>Article</option>
                                                            <option value="Book" <?= ($resource['type'] ?? '') == 'Book' ? 'selected' : '' ?>>Book</option>
                                                            <option value="Tool" <?= ($resource['type'] ?? '') == 'Tool' ? 'selected' : '' ?>>Tool/Software</option>
                                                            <option value="Repository" <?= ($resource['type'] ?? '') == 'Repository' ? 'selected' : '' ?>>Code Repository</option>
                                                            <option value="Dataset" <?= ($resource['type'] ?? '') == 'Dataset' ? 'selected' : '' ?>>Dataset</option>
                                                            <option value="Paper" <?= ($resource['type'] ?? '') == 'Paper' ? 'selected' : '' ?>>Research Paper</option>
                                                        </select>
                                                        <label>Resource Type</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-floating">
                                                        <textarea class="form-control" name="learning_resources[<?= $index ?>][description]" style="height: 80px;" placeholder="Description"><?= htmlspecialchars($resource['description'] ?? '') ?></textarea>
                                                        <label>Description (optional)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="section-buttons">
                                <button type="button" class="btn add-btn" onclick="addLearningResource()">
                                    <i class="bi bi-plus-circle"></i>Add Learning Resource
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5 py-3">
                            <i class="bi bi-check-circle me-2"></i>Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Variables to track the current index for dynamic fields
        let publicationIndex = <?= isset($student['university_research_profile']['university_publications']) && is_array($student['university_research_profile']['university_publications']) ? count($student['university_research_profile']['university_publications']) : 0 ?>;
        let learningResourceIndex = <?= isset($student['learning_resources']) && is_array($student['learning_resources']) ? count($student['learning_resources']) : 0 ?>;
        
        // Function to add a new publication field
        function addPublication() {
            const container = document.getElementById('publications-container');
            const newItem = document.createElement('div');
            newItem.className = 'dynamic-form-item';
            newItem.innerHTML = `
                <button type="button" class="remove-btn" onclick="removePublication(this)">
                    <i class="bi bi-x-circle"></i>
                </button>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="publications[${publicationIndex}][title]" placeholder="Title" required>
                            <label>Publication Title</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="publications[${publicationIndex}][authors]" placeholder="Authors" required>
                            <label>Authors (comma separated)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-control" name="publications[${publicationIndex}][type]" required>
                                <option value="">Select Type</option>
                                <option value="Journal Article">Journal Article</option>
                                <option value="Conference Paper">Conference Paper</option>
                                <option value="Book Chapter">Book Chapter</option>
                            </select>
                            <label>Publication Type</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="publications[${publicationIndex}][venue]" placeholder="Venue" required>
                            <label>Journal/Conference Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" name="publications[${publicationIndex}][date]">
                            <label>Publication Date</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-control" name="publications[${publicationIndex}][status]">
                                <option value="Published">Published</option>
                                <option value="Accepted">Accepted</option>
                                <option value="Under Review">Under Review</option>
                            </select>
                            <label>Status</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="url" class="form-control" name="publications[${publicationIndex}][doi]" placeholder="DOI">
                            <label>DOI URL (optional)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="url" class="form-control" name="publications[${publicationIndex}][paper_url]" placeholder="Paper URL">
                            <label>Paper URL (optional)</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <textarea class="form-control" name="publications[${publicationIndex}][abstract]" style="height: 100px;" placeholder="Abstract"></textarea>
                            <label>Abstract (optional)</label>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            publicationIndex++;
        }
        
        // Function to remove a publication field
        function removePublication(button) {
            button.parentElement.remove();
        }
        
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
                        <div class="form-floating">
                            <input type="text" class="form-control" name="learning_resources[${learningResourceIndex}][title]" placeholder="Title" required>
                            <label>Resource Title</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="url" class="form-control" name="learning_resources[${learningResourceIndex}][url]" placeholder="URL" required>
                            <label>Resource URL</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
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
                        <div class="form-floating">
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

        // Initialize fade-in animations
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 