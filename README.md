# UIU Research Portal (UIURP)

A research collaboration platform for students and faculty at United International University.
Find projects and supervisors that match your interests, then run the project in one place:
a group chat, meetings, a shared timeline, a literature matrix that summarises papers with AI,
and a forum. Built with PHP and MongoDB.

**1st Runner-Up, Software Engineering Lab Project Showcase, UIU (2025).**

<!-- Demo video: on github.com, edit this file and drag your .mp4 onto this line. -->

<!-- Screenshots: add them to docs/screenshots/ and list them in this table.
| Research projects | Project workspace | Literature matrix | Admin dashboard |
|---|---|---|---|
| ![](docs/screenshots/research.png) | ![](docs/screenshots/workspace.png) | ![](docs/screenshots/matrix.png) | ![](docs/screenshots/admin.png) |
-->

## What it does

**Find research**
- Browse and search projects by title, abstract, field and keywords
- Recommendations based on the projects you view, click and search for
- Student and faculty profiles with research interests, skills and prerequisites

**Run a project**
- Create projects with members, a supervisor, cover image, media and files, and keep them public or private
- A timeline with milestones and task assignments
- A group chat per project with file attachments and unread counts; messages are stored
  encrypted with a separate 256-bit key for each project
- Meeting scheduling, members' weekly schedules, and automatic meeting reminders in the chat
- File management for each project's documents

**Read and discuss**
- Literature matrix: upload research papers as PDFs and Gemini summarises each one by the
  categories you choose, side by side
- A forum with attachments, in-page previews of PDF, Word and PowerPoint files, and comments
- An events calendar and bulletin board

**Accounts and security**
- Sign-up with email verification by one-time code; passwords are stored hashed
- Accounts lock for 15 minutes after repeated failed logins
- Idle sessions sign out automatically
- An admin dashboard for managing users and admin roles

## Team

Built by a team of four for the Software Engineering Lab course, then extended with security
features: Najmol Hasan, Syed Nafis, Sazzad Abedin and
[Samin Yeaser Khan](https://github.com/SaminYeaserKhan).

Samin built the research-project management module, the project timeline, meeting scheduling
and member schedules, the calendar and bulletin board, project file management, chat message
encryption, the admin dashboard, account lockout and the preference tracking behind
recommendations. The full development history, with every member's commits, is in the team
repository: [syed-nafis/uiurp](https://github.com/syed-nafis/uiurp). This repository holds
the final version from its `security_mayen` branch.

## Built with

PHP · MongoDB (MongoDB PHP library) · JavaScript · Bootstrap · Google Gemini API ·
PDF.js · Etherpad · Selenium (UI tests)

## Run it locally

You need PHP 8 with the `mongodb` extension, [Composer](https://getcomposer.org), and a
MongoDB database (a free MongoDB Atlas cluster or a local MongoDB server).

1. Install the PHP MongoDB extension if you don't have it:
   ```bash
   pecl install mongodb
   ```
   Then add `extension=mongodb` to your `php.ini`. On Windows with XAMPP, download the
   matching `php_mongodb.dll` from [PECL](https://pecl.php.net/package/mongodb) instead.

2. Get the code and its libraries:
   ```bash
   git clone https://github.com/SaminYeaserKhan/UIURP-V1.git
   cd UIURP-V1
   composer install
   ```

3. Add your settings. Copy the example file and fill in your database connection string, and
   a [Gemini API key](https://aistudio.google.com/apikey) if you want AI summaries in the
   literature matrix:
   ```bash
   cp config/secrets.example.php config/secrets.php
   ```
   `config/secrets.php` is ignored by git. Each setting can be an environment variable instead
   (`MONGODB_URI`, `GEMINI_API_KEY`).

4. Start PHP's built-in server and open http://localhost:8000:
   ```bash
   php -S localhost:8000
   ```

5. Create the first admin by opening http://localhost:8000/init_admin.php and choosing a user.
   Once an admin exists, only admins can open that page.

**Sample data:** open http://localhost:8000/src/model/import_json_projects.php once to load
sample projects. The other files in `assets/json/` can be imported with MongoDB Compass
(Add data → Import JSON file); for example, `uiurp_users.json` goes into the `login_info`
collection.

**Email codes:** sign-up sends its one-time code with PHP's `mail()`, so the server needs a
working mail setup.

**Shared documents (optional):** `etherpad/etherpad-lite` is a Git submodule. Run
`git submodule update --init`, start Etherpad, and put its API key in
`etherpad/etherpad-lite/APIKEY.txt`.

## UI tests

`assets/scripts/` holds Selenium tests for login, navigation, faculty pages and the forum.
They expect the site at http://localhost:8000/uiurp/ and the sample accounts from
`assets/json/`.

## More documentation

- [`PROJECT_DEEP_DIVE.txt`](PROJECT_DEEP_DIVE.txt): architecture, collections and every main flow
- [`ADMIN_SYSTEM_README.md`](ADMIN_SYSTEM_README.md): the admin system
- [`CHAT_ENCRYPTION_SUMMARY.md`](CHAT_ENCRYPTION_SUMMARY.md): how chat messages are encrypted
- [`TIMELINE_ASSIGNMENT_IMPLEMENTATION.md`](TIMELINE_ASSIGNMENT_IMPLEMENTATION.md): timeline assignments
- [`WHERE_ARE_FILES_STORED.md`](WHERE_ARE_FILES_STORED.md): where uploaded files go
