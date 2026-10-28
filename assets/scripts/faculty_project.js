document.addEventListener('DOMContentLoaded', () => {
    let projectIndex = window.initialProjectCount || 0;

    const addProjectBtn = document.getElementById('addProjectBtn');
    const projectsContainer = document.getElementById('projectsContainer');

    if (addProjectBtn && projectsContainer) {
        addProjectBtn.addEventListener('click', function () {
            const newProject = document.createElement('div');
            newProject.classList.add('mb-3', 'p-3', 'project-block');

            newProject.innerHTML = `
                <label class="form-label">Title:</label>
                <input type="text" name="projects[${projectIndex}][title]" class="form-control mb-2" required>

                <label class="form-label">Description:</label>
                <textarea name="projects[${projectIndex}][description]" class="form-control mb-2" required></textarea>

                <label class="form-label">Link:</label>
                <input type="url" name="projects[${projectIndex}][link]" class="form-control mb-2">

                <button type="button" class="btn btn-danger btn-sm remove-project-btn">Remove Project</button>
            `;

            projectsContainer.appendChild(newProject);
            projectIndex++;
        });

        projectsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-project-btn')) {
                e.target.closest('.project-block').remove();
            }
        });
    }
});
