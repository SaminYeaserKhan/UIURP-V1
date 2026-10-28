document.addEventListener('DOMContentLoaded', () => {
    let resourceIndex = window.initialResourceCount || 0;

    const addBtn = document.getElementById('addResourceBtn');
    const container = document.getElementById('resourcesContainer');

    if (addBtn && container) {
        addBtn.addEventListener('click', function () {
            const newResource = document.createElement('div');
            newResource.classList.add('mb-3', 'p-3', 'resource-block');

            newResource.innerHTML = `
                <label class="form-label">Topic:</label>
                <input type="text" name="resources[${resourceIndex}][topic]" class="form-control mb-2" required>

                <label class="form-label">Link:</label>
                <input type="text" name="resources[${resourceIndex}][link]" class="form-control mb-2">

                <button type="button" class="btn btn-danger btn-sm remove-resource-btn">Remove Resource</button>
            `;

            container.appendChild(newResource);
            resourceIndex++;
        });

        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-resource-btn')) {
                e.target.closest('.resource-block').remove();
            }
        });
    }
});
