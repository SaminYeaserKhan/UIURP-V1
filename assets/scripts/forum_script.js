document.addEventListener('DOMContentLoaded', function() {
    fetch('/../../src/model/fetch_forum.php')
        .then(response => response.json())
        .then(data => {
            const postsContainer = document.getElementById('postsContainer');
            if (data.message) {
                postsContainer.innerHTML = `<p>${data.message}</p>`;
            } else {
                data.forEach(forum => {
                    const postDiv = document.createElement('div');
                    postDiv.className = 'forum-post';
                    postDiv.innerHTML = `
                        <p><strong>Posted by:</strong> ${forum.user_name || 'Unknown'}</p>
                        <h3>${forum.title}</h3>
                        <p>${forum.content}</p>
                        <p><strong>Tags:</strong> ${forum.tags ? forum.tags.join(', ') : 'None'}</p> <!-- ✅ NEW: tags -->
                        <p><strong>Posted on:</strong> ${postDate}</p> <!-- ✅ NEW: timestamp -->
                        <p>Views: ${forum.views}</p>
                        <p>Upvotes: ${forum.upvotes}</p>
                        <p>Downvotes: ${forum.downvotes || 0}</p> <!-- ✅ Optional downvotes -->
                        <div class="comments">
                            <h4>Comments:</h4>
                            ${Array.isArray(forum.comments) && forum.comments.length > 0 
                                ? forum.comments.map(comment => `<p>${comment}</p>`).join('')
                                : '<p>No comments yet.</p>'} <!-- ✅ Safe check for comments -->
                        </div>
                    `;

                    const upvoteButton = document.createElement('button');
                    upvoteButton.textContent = 'Upvote';
                    upvoteButton.addEventListener('click', function() {
                        alert('Post upvoted!');
                        // Add code to update upvotes in the database
                    });

                    const downvoteButton = document.createElement('button');
                    downvoteButton.textContent = 'Downvote';
                    downvoteButton.addEventListener('click', function() {
                        alert('Post downvoted!');
                        // Add code to update downvotes in the database
                    });

                    const voteButtons = document.createElement('div');
                    voteButtons.appendChild(upvoteButton);
                    voteButtons.appendChild(downvoteButton);
                    postDiv.appendChild(voteButtons);
                    postsContainer.appendChild(postDiv);
                });
            }
        })
        .catch(error => console.error('Error fetching forums:', error));

    document.getElementById('submitPost').addEventListener('click', function() {
        const title = document.getElementById('postTitle').value;
        const content = document.getElementById('postContent').value;

        fetch('/../../src/controller/submit_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ title, content })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Post submitted successfully!');
                window.location.href = 'view_posts.php'; // Redirect to the page that displays the new post
            } else {
                alert('Error submitting post: ' + data.message);
            }
        })
        .catch(error => console.error('Error submitting post:', error));
    });
});