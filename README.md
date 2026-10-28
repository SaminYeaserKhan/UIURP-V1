# UIU Research Portal (UIURP)

A web platform that connects students and faculty at United International University around
research: browse and search research projects, find faculty by their research interests, and
discuss ideas in a forum. Built with PHP and MongoDB.

**1st Runner-Up, Software Engineering Lab Project Showcase, UIU (2025).**

<!-- Demo video: on github.com, edit this file and drag your .mp4 onto this line. -->

<!-- Screenshots: add them to docs/screenshots/ and list them in this table.
| Research projects | Project page | Faculty profile | Forum |
|---|---|---|---|
| ![](docs/screenshots/research.png) | ![](docs/screenshots/project.png) | ![](docs/screenshots/faculty.png) | ![](docs/screenshots/forum.png) |
-->

## What it does

- **Research projects:** listings with title, abstract, field, members, supervisor, timeline,
  keywords, cover image and media, plus a details page for each project
- **Project management:** create, edit and delete projects, upload images and files, and
  switch a project between public and private
- **Search:** keyword search across project titles, abstracts, fields and keywords
- **Faculty and student profiles:** research interests, publications and contact details
- **Discussion forum** for questions and research ideas
- Separate sign-up and login for students and faculty

## Team

Built by a team of four for the Software Engineering Lab course: Najmol Hasan, Syed Nafis,
Sazzad Abedin and [Samin Yeaser Khan](https://github.com/SaminYeaserKhan).

Samin built the research-project management module: creating, editing and deleting projects
with cover-image and media uploads, members, supervisor and timeline, public/private
visibility, the project details page, and search across projects. The commit history shows
each member's work.

## Built with

PHP · MongoDB (MongoDB PHP library) · HTML, CSS and JavaScript · Bootstrap

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

3. Point it at your database. Copy the example config and put your own connection string in
   it:
   ```bash
   cp config/database.example.php config/database.php
   ```
   `config/database.php` is ignored by git. You can set a `MONGODB_URI` environment variable
   instead.

4. Start PHP's built-in server and open http://localhost:8000:
   ```bash
   php -S localhost:8000
   ```

**Sample data:** open http://localhost:8000/src/model/import_json_projects.php once to load
sample projects. The other files in `assets/json/` can be imported with MongoDB Compass
(Add data → Import JSON file); for example, `uiurp_users.json` goes into the `login_info`
collection.
