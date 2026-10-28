<?php
// Get all images from the Resources folder
$imageFolder = "assets/resources/research_picture/";
$images = glob($imageFolder . "*.{jpg,png,jpeg,gif}", GLOB_BRACE);

// Start the session 
session_start();

// Include the MongoDB PHP library
require 'vendor/autoload.php';

// Create a MongoDB client
$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$collection = $client->uiurp->faculties;

// Check if the 'id' parameter is set in the URL
if (isset($_GET['id'])) { 
    try {
        // Convert the 'id' parameter to a MongoDB ObjectId
        $faculty_id = new MongoDB\BSON\ObjectId($_GET['id']);

        // Find the faculty member by _id
        $faculty = $collection->findOne(['_id' => $faculty_id]);

        // Check if the faculty member exists
        if ($faculty) {
            // Convert MongoDB BSON document to an associative array
            $faculty = iterator_to_array($faculty);
        } else {
            // If no faculty member is found, redirect or show an error
            header("Location: faculty_page.php");
            exit();
        }
    } catch (Exception $e) {
        // Handle invalid ObjectId or other errors
        header("Location: faculty_page.php");
        exit();
    }
} else {
    // If no 'id' parameter is provided, redirect or show an error
    header("Location: faculty_page.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Profile - <?= $faculty['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/faculty_profile.css">
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>

    <!-- Hero Section -->
    <header>
    <div class="container py-5 text-left">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 id="facultyName" style="font-size: 60px;">Hello, I'm <strong><?= $faculty['name']; ?></strong></h1>
                <p id="facultyBio"><?= $faculty['bio']; ?></p>
                <div class="py-3">
                    <!-- <a href="#" class="btn btn-primary me-2 mb-2">Say Hello!</a> -->
                    <a href="#projects" class="btn btn-primary me-2 mb-2">My Projects</a>
                    <!-- Button to trigger the popup window -->
                    <button class="btn btn-secondary mb-2" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                        Current Schedule
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <img src="<?= $faculty['profile_image'] ?? 'assets/resources/imgPlaceholder.png'; ?>" alt="Profile Picture" class="img-fluid rounded" id="facultyImage">
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Bootstrap Modal (Popup Window) -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">Current Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Embed the external website using an iframe -->
                <iframe src="https://now.nahid.org/" width="100%" height="500px" style="border: none;"></iframe>
            </div>
        </div>
    </div>
</div>


    <!-- Interested Fields Section -->
    <section class="py-5 bg-light">
    <h2 class="text-center mb-4">Interested Fields</h2>
    <div class="container p-3 text-center align-items-center">
        <div class="row text-center align-items-center justify-content-center">
            <?php foreach ($faculty['interested_fields_of_research'] as $field): ?>
                <div class="col-md-3 mb-4">
                    <div class="field-box p-4 shadow-sm rounded-lg">
                        <h3 class="text-secondary"><?= $field; ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


    <!-- Publications Section -->
    <section id="projects" class="container py-5">
    <h2 class="text-center mb-4">Publications</h2>
    <div class="row">
        <?php foreach ($faculty['projects'] as $project): ?>
            <div class="col-md-4 p-3">
                <div class="card p-3">
                    <?php 
                        // Select a random image from the folder
                        $randomImage = $images[array_rand($images)]; 
                    ?>
                    <img src="<?= $randomImage; ?>" class="card-img-top" alt="Project Image" style="width: 100%; height: auto;">
                    <div class="card-body">
                        <h5 class="card-title"><?= $project['title']; ?></h5>
                        <p class="card-text"><?= $project['description']; ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

    <!-- Contact Section -->
    <section class="bg-dark text-light py-5">
        <div class="container">
            <h2 class="text-center">Do you have a Project Idea? Let's discuss!</h2>
            <div class="row mt-4">
                <div class="col-md-6">
                    <h4>Need help accessing research papers?</h4>
                    <p>Address: <?= $faculty['office_number'] ?? 'N/A'; ?></p>
                    <p>Email: <?= $faculty['email'] ?? 'N/A'; ?></p>
                    <p>Call: <?= $faculty['phone'] ?? 'N/A'; ?></p>
                </div>
                <div class="col-md-6">
                    <form>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" placeholder="Enter your name">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" placeholder="Enter your email">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="4" placeholder="Enter your message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>