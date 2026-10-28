<?php
session_start();
require 'vendor/autoload.php';

$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$collection = $client->uiurp->faculties;

if (!isset($_GET['id'])) {
    header("Location: faculty_page.php");
    exit();
}

try {
    $faculty_id = new MongoDB\BSON\ObjectId($_GET['id']);
    $faculty = $collection->findOne(['_id' => $faculty_id]);

    if (!$faculty || $_SESSION['user_id'] !== (string)$faculty['_id']) {
        die("Unauthorized access.");
    }
} catch (Exception $e) {
    die("Invalid ID or error fetching data.");
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Edit Faculty Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/edit_faculty_modern.css">
</head>
<body>
<?php include 'src/includes/navbar.php'; ?>

<div class="container my-5">
    <div class="card shadow p-4">
        <h2 class="mb-4 text-center">Edit Profile</h2>

        <form method="POST" action="update_faculty.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $faculty['_id']; ?>">

            <!-- Name -->
            <div class="mb-4">
                <h5 class="mb-3">Name</h5>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($faculty['name'] ?? ''); ?>" required>
            </div>

            <!-- Bio -->
            <div class="mb-4">
                <h5 class="mb-3">Bio</h5>
                <textarea name="bio" class="form-control" rows="4" required><?= htmlspecialchars($faculty['bio'] ?? ''); ?></textarea>
            </div>

            <!-- Profile Image -->
             <div class="mb-4">
                <h5 class="mb-3">Profile Image</h5>
                <input type="file" name="profile_image" class="form-control">
                <?php if (!empty($faculty['profile_image'])): ?>
                    <div class="mt-2">
                        <strong>Current Image:</strong><br>
                        <img src="<?= htmlspecialchars($faculty['profile_image']); ?>" alt="Profile Image" width="150" class="mt-2">
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contact Information -->
            <div class="mb-4">
                <h5 class="mb-3">Contact Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($faculty['email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($faculty['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Office Number</label>
                    <input type="text" name="office_number" class="form-control" value="<?= htmlspecialchars($faculty['office_number'] ?? ''); ?>">
                </div>
            </div>

            <!-- Research Fields -->
            <div class="mb-4">
                <h5 class="mb-3">Research Fields</h5>
                <?php 
                $researchFields = $faculty['interested_fields_of_research'] ?? [];
                if ($researchFields instanceof \MongoDB\Model\BSONArray) {
                    $researchFields = (array)$researchFields;
                }
                ?>

                <div id="researchFieldsContainer">
                    <?php if (!empty($researchFields)): ?>
                        <?php foreach ($researchFields as $index => $field): ?>
                            <?php if ($field instanceof \MongoDB\Model\BSONDocument) $field = (array)$field; ?>
                            <div class="mb-3 p-3 border rounded research-field-block">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Research Field <?= $index + 1; ?></h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-research-field-btn">Remove</button>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Field Name</label>
                                        <input type="text" name="research_fields[<?= $index; ?>][name]" class="form-control" 
                                               value="<?= htmlspecialchars($field['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Description</label>
                                        <textarea name="research_fields[<?= $index; ?>][description]" class="form-control" rows="2"><?= htmlspecialchars($field['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No research fields available.</p>
                    <?php endif; ?>
                </div>

                <!-- Add Research Field Button -->
                <button type="button" class="btn btn-outline-primary mt-3" id="addResearchFieldBtn">
                    <i class="bi bi-plus-lg"></i> Add Research Field
                </button>
            </div>

            <!-- Projects -->
            <div class="mb-4">
                <h5 class="mb-3">Publications</h5>
                <?php $projects = isset($faculty['projects']) ? (array)$faculty['projects'] : []; ?>

                <div id="projectsContainer">
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $index => $project): ?>
                            <?php if ($project instanceof \MongoDB\Model\BSONDocument) $project = (array)$project; ?>
                            <div class="mb-3 p-3 border rounded project-block">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Publication <?= $index + 1; ?></h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-project-btn">Remove</button>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="projects[<?= $index; ?>][title]" class="form-control"
                                        value="<?= htmlspecialchars($project['title'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Description</label>
                                    <textarea name="projects[<?= $index; ?>][description]" class="form-control" rows="3"><?= htmlspecialchars($project['description'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Link</label>
                                    <input type="url" name="projects[<?= $index; ?>][link]" class="form-control"
                                        value="<?= htmlspecialchars($project['link'] ?? ''); ?>" placeholder="https://...">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No publications available.</p>
                    <?php endif; ?>
                </div>

                <!-- Add Project Button -->
                <button type="button" class="btn btn-outline-primary mt-3" id="addProjectBtn">
                    <i class="bi bi-plus-lg"></i> Add Publication
                </button>
            </div>

            <!-- Prerequisites -->
            <div class="mb-4">
                <h5 class="mb-3">Prerequisites</h5>
                <?php 
                $prerequisites = $faculty['prerequisites'] ?? [];
                if ($prerequisites instanceof \MongoDB\Model\BSONArray) {
                    $prerequisites = (array)$prerequisites;
                }
                ?>

                <div id="prerequisitesContainer">
                    <?php if (!empty($prerequisites)): ?>
                        <?php foreach ($prerequisites as $index => $prerequisite): ?>
                            <?php if ($prerequisite instanceof \MongoDB\Model\BSONDocument) $prerequisite = (array)$prerequisite; ?>
                            <div class="mb-3 p-3 border rounded prerequisite-block">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Prerequisite <?= $index + 1; ?></h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-prerequisite-btn">Remove</button>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Prerequisite Name</label>
                                        <input type="text" name="prerequisites[<?= $index; ?>][name]" class="form-control"
                                               value="<?= htmlspecialchars($prerequisite['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Level</label>
                                        <select name="prerequisites[<?= $index; ?>][level]" class="form-select">
                                            <option value="Beginner" <?= ($prerequisite['level'] ?? '') === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                            <option value="Intermediate" <?= ($prerequisite['level'] ?? '') === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                            <option value="Advanced" <?= ($prerequisite['level'] ?? '') === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label">Description</label>
                                    <textarea name="prerequisites[<?= $index; ?>][description]" class="form-control" rows="2"><?= htmlspecialchars($prerequisite['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No prerequisites available.</p>
                    <?php endif; ?>
                </div>

                <!-- Add Prerequisite Button -->
                <button type="button" class="btn btn-outline-primary mt-3" id="addPrerequisiteBtn">
                    <i class="bi bi-plus-lg"></i> Add Prerequisite
                </button>
            </div>

            <!-- Resources -->
            <div class="mb-4">
                <h5 class="mb-3">Learning Resources</h5>

                <?php
                $resources = $faculty['resources_to_learn_prerequisites'] ?? [];
                if ($resources instanceof \MongoDB\Model\BSONArray) {
                    $resources = (array)$resources;
                }
                ?>

                <div id="resourcesContainer">
                    <?php if (!empty($resources) && is_array($resources)): ?>
                        <?php foreach ($resources as $index => $res): ?>
                            <?php if ($res instanceof \MongoDB\Model\BSONDocument) $res = (array)$res; ?>
                            <div class="mb-3 p-3 border rounded resource-block">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Resource <?= $index + 1; ?></h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-resource-btn">Remove</button>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Topic</label>
                                    <input type="text" name="resources[<?= $index; ?>][topic]" class="form-control"
                                        value="<?= htmlspecialchars($res['topic'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Description</label>
                                    <textarea name="resources[<?= $index; ?>][description]" class="form-control" rows="2"><?= htmlspecialchars($res['description'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Link</label>
                                    <input type="url" name="resources[<?= $index; ?>][link]" class="form-control"
                                        value="<?= htmlspecialchars($res['link'] ?? ''); ?>" placeholder="https://...">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No resources listed.</p>
                    <?php endif; ?>
                </div>

                <!-- Add Resource Button -->
                <button type="button" class="btn btn-outline-primary mt-3" id="addResourceBtn">
                    <i class="bi bi-plus-lg"></i> Add Resource
                </button>
            </div>

            <!-- Submit Button -->
            <div class="text-center mt-5">
                <button type="submit" class="btn btn-primary px-5 py-2">
                    <i class="bi bi-check-lg"></i> Update Information
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Pass PHP values to JS
    window.initialResourceCount = <?= count($resources); ?>;
    window.initialResearchFieldCount = <?= count($researchFields); ?>;
    window.initialPrerequisiteCount = <?= count($prerequisites); ?>;
    window.initialProjectCount = <?= count($projects); ?>;
    
    // Check for user's theme preference
    document.addEventListener('DOMContentLoaded', function() {
        // Check for saved theme preference or default to 'dark'
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Listen for theme changes from navbar (if it has theme toggle functionality)
        window.addEventListener('themeChanged', function(e) {
            const newTheme = e.detail.theme;
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    });
</script>

<script>
    // Dynamic functionality for all sections
    document.addEventListener('DOMContentLoaded', function() {
        // Research Fields functionality
        let researchFieldCounter = window.initialResearchFieldCount || 0;
        const addResearchFieldBtn = document.getElementById('addResearchFieldBtn');
        if (addResearchFieldBtn) {
            addResearchFieldBtn.addEventListener('click', function() {
                researchFieldCounter++;
                const container = document.getElementById('researchFieldsContainer');
                const newField = document.createElement('div');
                newField.className = 'mb-3 p-3 border rounded research-field-block';
                newField.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Research Field ${researchFieldCounter}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-research-field-btn">Remove</button>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Field Name</label>
                            <input type="text" name="research_fields[${researchFieldCounter-1}][name]" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <textarea name="research_fields[${researchFieldCounter-1}][description]" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                `;
                container.appendChild(newField);
            });
        }

        // Prerequisites functionality
        let prerequisiteCounter = window.initialPrerequisiteCount || 0;
        const addPrerequisiteBtn = document.getElementById('addPrerequisiteBtn');
        if (addPrerequisiteBtn) {
            addPrerequisiteBtn.addEventListener('click', function() {
                prerequisiteCounter++;
                const container = document.getElementById('prerequisitesContainer');
                const newPrerequisite = document.createElement('div');
                newPrerequisite.className = 'mb-3 p-3 border rounded prerequisite-block';
                newPrerequisite.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Prerequisite ${prerequisiteCounter}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-prerequisite-btn">Remove</button>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Prerequisite Name</label>
                            <input type="text" name="prerequisites[${prerequisiteCounter-1}][name]" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Level</label>
                            <select name="prerequisites[${prerequisiteCounter-1}][level]" class="form-select">
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Description</label>
                        <textarea name="prerequisites[${prerequisiteCounter-1}][description]" class="form-control" rows="2"></textarea>
                    </div>
                `;
                container.appendChild(newPrerequisite);
            });
        }

        // Projects functionality
        let projectCounter = window.initialProjectCount || 0;
        const addProjectBtn = document.getElementById('addProjectBtn');
        if (addProjectBtn) {
            addProjectBtn.addEventListener('click', function() {
                projectCounter++;
                const container = document.getElementById('projectsContainer');
                const newProject = document.createElement('div');
                newProject.className = 'mb-3 p-3 border rounded project-block';
                newProject.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Publication ${projectCounter}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-project-btn">Remove</button>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Title</label>
                        <input type="text" name="projects[${projectCounter-1}][title]" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="projects[${projectCounter-1}][description]" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Link</label>
                        <input type="url" name="projects[${projectCounter-1}][link]" class="form-control" placeholder="https://...">
                    </div>
                `;
                container.appendChild(newProject);
            });
        }

        // Resources functionality
        let resourceCounter = window.initialResourceCount || 0;
        const addResourceBtn = document.getElementById('addResourceBtn');
        if (addResourceBtn) {
            addResourceBtn.addEventListener('click', function() {
                resourceCounter++;
                const container = document.getElementById('resourcesContainer');
                const newResource = document.createElement('div');
                newResource.className = 'mb-3 p-3 border rounded resource-block';
                newResource.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Resource ${resourceCounter}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-resource-btn">Remove</button>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Topic</label>
                        <input type="text" name="resources[${resourceCounter-1}][topic]" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="resources[${resourceCounter-1}][description]" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Link</label>
                        <input type="url" name="resources[${resourceCounter-1}][link]" class="form-control" placeholder="https://...">
                    </div>
                `;
                container.appendChild(newResource);
            });
        }

        // Remove functionality for all sections
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-research-field-btn')) {
                e.target.closest('.research-field-block').remove();
            }
            if (e.target.classList.contains('remove-prerequisite-btn')) {
                e.target.closest('.prerequisite-block').remove();
            }
            if (e.target.classList.contains('remove-project-btn')) {
                e.target.closest('.project-block').remove();
            }
            if (e.target.classList.contains('remove-resource-btn')) {
                e.target.closest('.resource-block').remove();
            }
        });
    });
</script>

<?php include 'src/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
