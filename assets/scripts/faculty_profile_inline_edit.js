// faculty_profile_inline_edit.js - Inline editing for faculty profile page

document.addEventListener('DOMContentLoaded', function () {
    // Only run if we're on a faculty profile page
    const facultyId = new URLSearchParams(window.location.search).get('id');
    if (!facultyId) return;

    // Inline editing functionality
    function createEditForm(section, currentData) {
        let html = '';
        if (section === 'research-fields') {
            html = `
            <form id="edit-research-fields-form">
                <label>Research Fields:</label>
                <div id="rf-chips" class="mb-2">
                    ${currentData.map(field => `<span class='badge bg-primary me-1 mb-1 rf-chip'>${field}<button type='button' class='btn-close btn-close-white btn-sm ms-1 rf-remove' aria-label='Remove'></button></span>`).join('')}
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="rf-input" placeholder="Add new field...">
                    <button type="button" class="btn btn-outline-primary" id="rf-add">Add</button>
                </div>
                <button type="submit" class="btn btn-success btn-sm">Save</button>
                <button type="button" class="btn btn-secondary btn-sm cancel-edit">Cancel</button>
            </form>`;
        } else if (section === 'prerequisites') {
            html = `
            <form id="edit-prerequisites-form">
                <label>Prerequisites:</label>
                <div id="pr-chips" class="mb-2">
                    ${currentData.map(field => `<span class='badge bg-info me-1 mb-1 pr-chip'>${field}<button type='button' class='btn-close btn-close-white btn-sm ms-1 pr-remove' aria-label='Remove'></button></span>`).join('')}
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="pr-input" placeholder="Add new prerequisite...">
                    <button type="button" class="btn btn-outline-info" id="pr-add">Add</button>
                </div>
                <button type="submit" class="btn btn-success btn-sm">Save</button>
                <button type="button" class="btn btn-secondary btn-sm cancel-edit">Cancel</button>
            </form>`;
        } else if (section === 'resources') {
            html = `
            <form id="edit-resources-form">
                <label>Learning Resources:</label>
                <div id="lr-list" class="mb-2">
                    ${currentData.map((r, i) => `
                        <div class="card p-2 mb-2 lr-item" data-index="${i}">
                            <div class="row g-2 align-items-center">
                                <div class="col-5">
                                    <input type="text" class="form-control" placeholder="Topic" value="${r.topic || ''}" name="topic">
                                </div>
                                <div class="col-5">
                                    <input type="url" class="form-control" placeholder="Link" value="${r.link || ''}" name="link">
                                </div>
                                <div class="col-2 text-end">
                                    <button type="button" class="btn btn-danger btn-sm lr-remove">Remove</button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="lr-add">+ Add Resource</button>
                <br>
                <button type="submit" class="btn btn-success btn-sm">Save</button>
                <button type="button" class="btn btn-secondary btn-sm cancel-edit">Cancel</button>
            </form>`;
        } else if (section === 'publications') {
            html = `
            <form id="edit-publications-form">
                <label>Publications:</label>
                <div id="pub-list" class="mb-2">
                    ${currentData.map((p, i) => `
                        <div class="card p-2 mb-2 pub-item" data-index="${i}">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-3 col-12 mb-1 mb-md-0">
                                    <input type="text" class="form-control" placeholder="Title" value="${p.title || ''}" name="title">
                                </div>
                                <div class="col-md-5 col-12 mb-1 mb-md-0">
                                    <input type="text" class="form-control" placeholder="Description" value="${p.description || ''}" name="description">
                                </div>
                                <div class="col-md-3 col-10 mb-1 mb-md-0">
                                    <input type="url" class="form-control" placeholder="Link" value="${p.link || ''}" name="link">
                                </div>
                                <div class="col-md-1 col-2 text-end">
                                    <button type="button" class="btn btn-danger btn-sm pub-remove">Remove</button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="pub-add">+ Add Publication</button>
                <br>
                <button type="submit" class="btn btn-success btn-sm">Save</button>
                <button type="button" class="btn btn-secondary btn-sm cancel-edit">Cancel</button>
            </form>`;
        }
        return html;
    }

    const sectionMap = {
        'research-fields': {
            view: document.getElementById('research-fields-view'),
            edit: document.getElementById('research-fields-edit'),
            getData: () => Array.from(document.querySelectorAll('#research-fields-view h5')).map(e => e.textContent)
        },
        'prerequisites': {
            view: document.getElementById('prerequisites-view'),
            edit: document.getElementById('prerequisites-edit'),
            getData: () => Array.from(document.querySelectorAll('#prerequisites-view h5')).map(e => e.textContent)
        },
        'resources': {
            view: document.getElementById('resources-view'),
            edit: document.getElementById('resources-edit'),
            getData: () => Array.from(document.querySelectorAll('#resources-view .resource-card')).map(card => {
                const topic = card.querySelector('h5').textContent.replace(/^[^a-zA-Z0-9]*/, '').trim();
                const link = card.getAttribute('href');
                return {topic, link};
            })
        },
        'publications': {
            view: document.getElementById('publications-view'),
            edit: document.getElementById('publications-edit'),
            getData: () => Array.from(document.querySelectorAll('#publications-view .card')).map(card => {
                return {
                    title: card.querySelector('.card-title').textContent,
                    description: card.querySelector('.card-text').textContent,
                    link: card.parentElement.getAttribute('href')
                };
            })
        }
    };

    // Add event listeners to edit buttons
    document.querySelectorAll('.inline-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const section = btn.getAttribute('data-section');
            const {view, edit, getData} = sectionMap[section];
            
            if (!view || !edit) {
                console.error('View or edit container not found for section:', section);
                return;
            }
            
            view.style.display = 'none';
            edit.innerHTML = createEditForm(section, getData());
            edit.style.display = '';

            // --- Research Fields Chips Logic ---
            if (section === 'research-fields') {
                const chipsContainer = edit.querySelector('#rf-chips');
                const input = edit.querySelector('#rf-input');
                const addBtn = edit.querySelector('#rf-add');

                // Add chip
                addBtn.onclick = function() {
                    const val = input.value.trim();
                    if (val && ![...chipsContainer.querySelectorAll('.rf-chip')].some(chip => chip.childNodes[0].nodeValue === val)) {
                        const span = document.createElement('span');
                        span.className = 'badge bg-primary me-1 mb-1 rf-chip';
                        span.innerHTML = `${val}<button type='button' class='btn-close btn-close-white btn-sm ms-1 rf-remove' aria-label='Remove'></button>`;
                        chipsContainer.appendChild(span);
                        input.value = '';
                    }
                };
                // Enter key adds chip
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addBtn.click();
                    }
                });
                // Remove chip
                chipsContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('rf-remove')) {
                        e.target.parentElement.remove();
                    }
                });
            }

            // --- Prerequisites Chips Logic ---
            if (section === 'prerequisites') {
                const chipsContainer = edit.querySelector('#pr-chips');
                const input = edit.querySelector('#pr-input');
                const addBtn = edit.querySelector('#pr-add');

                addBtn.onclick = function() {
                    const val = input.value.trim();
                    if (val && ![...chipsContainer.querySelectorAll('.pr-chip')].some(chip => chip.childNodes[0].nodeValue === val)) {
                        const span = document.createElement('span');
                        span.className = 'badge bg-info me-1 mb-1 pr-chip';
                        span.innerHTML = `${val}<button type='button' class='btn-close btn-close-white btn-sm ms-1 pr-remove' aria-label='Remove'></button>`;
                        chipsContainer.appendChild(span);
                        input.value = '';
                    }
                };
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addBtn.click();
                    }
                });
                chipsContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('pr-remove')) {
                        e.target.parentElement.remove();
                    }
                });
            }

            // --- Learning Resources Dynamic List Logic ---
            if (section === 'resources') {
                const list = edit.querySelector('#lr-list');
                const addBtn = edit.querySelector('#lr-add');
                addBtn.onclick = function() {
                    const div = document.createElement('div');
                    div.className = 'card p-2 mb-2 lr-item';
                    div.innerHTML = `
                        <div class="row g-2 align-items-center">
                            <div class="col-5">
                                <input type="text" class="form-control" placeholder="Topic" name="topic">
                            </div>
                            <div class="col-5">
                                <input type="url" class="form-control" placeholder="Link" name="link">
                            </div>
                            <div class="col-2 text-end">
                                <button type="button" class="btn btn-danger btn-sm lr-remove">Remove</button>
                            </div>
                        </div>`;
                    list.appendChild(div);
                };
                list.addEventListener('click', function(e) {
                    if (e.target.classList.contains('lr-remove')) {
                        e.target.closest('.lr-item').remove();
                    }
                });
            }

            // --- Publications Dynamic List Logic ---
            if (section === 'publications') {
                const list = edit.querySelector('#pub-list');
                const addBtn = edit.querySelector('#pub-add');
                addBtn.onclick = function() {
                    const div = document.createElement('div');
                    div.className = 'card p-2 mb-2 pub-item';
                    div.innerHTML = `
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3 col-12 mb-1 mb-md-0">
                                <input type="text" class="form-control" placeholder="Title" name="title">
                            </div>
                            <div class="col-md-5 col-12 mb-1 mb-md-0">
                                <input type="text" class="form-control" placeholder="Description" name="description">
                            </div>
                            <div class="col-md-3 col-10 mb-1 mb-md-0">
                                <input type="url" class="form-control" placeholder="Link" name="link">
                            </div>
                            <div class="col-md-1 col-2 text-end">
                                <button type="button" class="btn btn-danger btn-sm pub-remove">Remove</button>
                            </div>
                        </div>`;
                    list.appendChild(div);
                };
                list.addEventListener('click', function(e) {
                    if (e.target.classList.contains('pub-remove')) {
                        e.target.closest('.pub-item').remove();
                    }
                });
            }

            edit.querySelector('.cancel-edit').onclick = function() {
                edit.style.display = 'none';
                view.style.display = '';
            };

            edit.querySelector('form').onsubmit = function(e) {
                e.preventDefault();
                let formData = new FormData();
                formData.append('id', facultyId);
                if (section === 'research-fields') {
                    const chips = Array.from(edit.querySelectorAll('.rf-chip')).map(chip => chip.childNodes[0].nodeValue.trim());
                    formData.append('interested_fields', chips.join(','));
                } else if (section === 'prerequisites') {
                    const chips = Array.from(edit.querySelectorAll('.pr-chip')).map(chip => chip.childNodes[0].nodeValue.trim());
                    formData.append('prerequisites', chips.join(','));
                } else if (section === 'resources') {
                    const items = Array.from(edit.querySelectorAll('.lr-item')).map(item => {
                        return {
                            topic: item.querySelector('input[name="topic"]').value.trim(),
                            link: item.querySelector('input[name="link"]').value.trim()
                        };
                    }).filter(r => r.topic || r.link);
                    formData.append('resources', JSON.stringify(items));
                } else if (section === 'publications') {
                    const items = Array.from(edit.querySelectorAll('.pub-item')).map(item => {
                        return {
                            title: item.querySelector('input[name="title"]').value.trim(),
                            description: item.querySelector('input[name="description"]').value.trim(),
                            link: item.querySelector('input[name="link"]').value.trim()
                        };
                    }).filter(p => p.title || p.description || p.link);
                    formData.append('projects', JSON.stringify(items));
                }
                fetch('update_faculty.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Update failed: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Update error:', error);
                    alert('Update failed! Please try again.');
                });
            };
        });
    });
}); 