# NEXUS | Future Tech Blog

## Project Description
NEXUS is a futuristic technology-focused blogging platform that showcases articles and podcasts on emerging tech topics like Artificial Intelligence, Quantum Computing, Space Exploration, and more. It features an engaging neon-themed UI, multimedia content support, interactive likes/dislikes, and customization options. Users can contribute new articles or podcasts with author images and audio uploads directly through the web interface.

## Features
- Manage and display technology articles and podcasts
- Upload author images for articles and audio files for podcasts
- Like and dislike functionality for content ranking
- Dynamic client-side navigation between Home, Articles, Podcasts, Create, About, and Contact pages
- Theme toggle between dark and light modes
- Voice control for simple page navigation
- Customization panel with color and visual effect adjustments
- Engaging neon/glassmorphism design with particle background effects

## Technologies Used
- PHP (backend logic and database interaction)
- MySQL (database management)
- HTML5, CSS3 (frontend UI design and styling)
- JavaScript (frontend interactivity and effects)
- Font Awesome (iconography)
- particles.js (animated background particles)

## Installation and Setup

1. Install a web server with PHP and MySQL support, for example [XAMPP](https://www.apachefriends.org/index.html).
2. Place the project files in the web server's document root folder (e.g., `htdocs` for XAMPP).
3. Import the database:
   - Start your MySQL server (e.g., via XAMPP control panel).
   - Open phpMyAdmin or use the MySQL command line.
   - Run the SQL script in `setup.sql` to create the `bloDB` database and necessary tables with sample data.
   ```sql
   source /path/to/setup.sql;
   ```
4. Ensure the `uploads/` and `audio/` directories are writable by the web server for file uploads.
5. Adjust database connection details in `index.php` if your MySQL credentials differ:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "bloDB";
   ```
6. Access the application via your browser (e.g., http://localhost/NEXUS or http://localhost if placed directly).

## Usage
- Navigate through the site using the header menu for latest articles, podcasts, about, and contact pages.
- Submit new content via the "Create" page by selecting content type, entering title, content, tags, selecting an icon, and optionally uploading author image and podcast audio.
- Use the like and dislike buttons on articles and podcasts to interact and influence ranking.
- Use the theme toggle and customization panel to personalize the site's look.
- Voice control can be used for quick navigation commands.

## Folder Structure
- `index.php` - Main PHP file serving frontend and handling backend logic.
- `setup.sql` - SQL script to setup MySQL database and insert sample data.
- `uploads/` - Directory where uploaded author images are stored.
- `audio/` - Directory where uploaded podcast audio files are stored.
- `.DS_Store` - macOS system file (not relevant to project).

## Notes
- Make sure file upload directories have correct permissions.
- For production deployment, security considerations like input validation, authentication, and HTTPS should be implemented.
- The design and features aspire to showcase futuristic UI/UX and smooth interactivity with technology themes.

---


