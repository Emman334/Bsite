<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bloDB";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit-content'])) {
    $type = $_POST['type'] ?? '';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $icon = $_POST['icon'] ?? '';

    // Handle author image upload
    $author_image = '';
    if (isset($_FILES['author_image']) && $_FILES['author_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $filename = basename($_FILES['author_image']['name']);
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES['author_image']['tmp_name'], $target_file)) {
            $author_image = $target_file;
        }
    }


    // Handle audio upload for podcasts
    $audio_url = '';
    if ($type === 'podcast' && isset($_FILES['audio_file'])) {
        $target_dir = "audio/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["audio_file"]["name"]);
        if (move_uploaded_file($_FILES["audio_file"]["tmp_name"], $target_file)) {
            $audio_url = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO content (type, title, content, tags, icon, author_image, audio_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $type, $title, $content, $tags, $icon, $author_image, $audio_url);

    if ($stmt->execute()) {
        $success_message = "Content published successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Handle like/dislike actions
if (isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'like') {
        $conn->query("UPDATE content SET likes = likes + 1 WHERE id = $id");
    } elseif ($action === 'dislike') {
        $conn->query("UPDATE content SET dislikes = dislikes + 1 WHERE id = $id");
    }
}

// Fetch content from database
$articles = [];
$podcasts = [];

$result = $conn->query("SELECT * FROM content WHERE type='article' ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
}

$result = $conn->query("SELECT * FROM content WHERE type='podcast' ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $podcasts[] = $row;
    }
}

// Get top content for homepage
$top_articles = $conn->query("SELECT * FROM content WHERE type='article' ORDER BY likes DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$top_podcasts = $conn->query("SELECT * FROM content WHERE type='podcast' ORDER BY likes DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXUS | Future Tech Blog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --neon-primary: #0ff;
            --neon-secondary: #f0f;
            --neon-accent: #ff00aa;
            --dark-bg: #0a0a15;
            --card-glass: rgba(20, 20, 40, 0.7);
            --text-primary: #fff;
            --text-secondary: #aaa;
            --transition-speed: 0.4s;
            --border-radius: 15px;
            --hologram-intensity: 0.8;
            --particle-density: 50;
            --header-height: 80px;
            --mobile-breakpoint: 768px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark-bg);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(40, 10, 80, 0.3) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(10, 40, 80, 0.3) 0%, transparent 40%);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
            padding-top: var(--header-height);
        }
#particles-js {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        /* Header Styles - Fixed Responsive Header */
        header {
            background: linear-gradient(135deg, #00172d 0%, #220525 100%);
            border-bottom: 1px solid var(--neon-primary);
            box-shadow: 0 0 20px var(--neon-secondary);
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--header-height);
            z-index: 1000;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            transition: height 0.3s ease;
        }

        .header-container {
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 800;
            text-shadow: 0 0 15px var(--neon-primary);
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: font-size 0.3s ease;
        }

        .nav-container {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        .nav-links li a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: all var(--transition-speed);
            position: relative;
            white-space: nowrap;
        }

        .nav-links li a:hover, 
        .nav-links li a.active {
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 10px var(--neon-primary);
        }

        .nav-links li a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--neon-primary);
            transition: width var(--transition-speed);
        }

        .nav-links li a:hover::after, 
        .nav-links li a.active::after {
            width: 100%;
        }

        .theme-toggle {
            background: transparent;
            border: 1px solid var(--neon-primary);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed);
            white-space: nowrap;
        }

        .theme-toggle:hover {
            background: var(--neon-primary);
            color: var(--dark-bg);
        }

        .voice-control {
            background: transparent;
            border: 1px solid var(--neon-secondary);
            color: var(--text-primary);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all var(--transition-speed);
        }

        .voice-control:hover {
            background: var(--neon-secondary);
            color: var(--dark-bg);
            transform: scale(1.1);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            color: var(--neon-primary);
            font-size: 1.8rem;
            cursor: pointer;
            padding: 0.5rem;
        }

        /* Main Content */
        .page {
            display: none;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            animation: fadeIn 0.5s ease;
        }

        .page.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .page-header h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            margin-bottom: 1rem;
            text-shadow: 0 0 10px var(--neon-primary);
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
            font-size: clamp(0.9rem, 2vw, 1.1rem);
        }

        /* Content Grid - Fully Responsive */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(300px, 100%), 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .card {
            background: var(--card-glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--neon-primary);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transform: perspective(1000px) rotateY(5deg);
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(0, 255, 255, calc(var(--hologram-intensity) * 0.1)) 0%, 
                rgba(255, 0, 255, calc(var(--hologram-intensity) * 0.1)) 100%);
            z-index: -1;
            opacity: 0.5;
        }

        .card:hover {
            transform: perspective(1000px) rotateY(0) translateY(-10px);
            box-shadow: 0 0 30px var(--neon-secondary);
            border-color: var(--neon-accent);
        }

        .card-img {
            width: 100%;
            height: 180px;
            background: linear-gradient(45deg, #330066, #0066cc);
            border-radius: 10px;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-img i {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.2);
        }

        .card h3 {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            margin-bottom: 0.5rem;
            color: var(--neon-primary);
        }

        .card p {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            flex-grow: 1;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .neon-tag {
            display: inline-block;
            padding: 5px 15px;
            border: 1px solid var(--neon-primary);
            border-radius: 20px;
            color: var(--text-primary);
            text-shadow: 0 0 5px var(--neon-primary);
            animation: pulse 2s infinite;
            font-size: clamp(0.7rem, 2vw, 0.8rem);
            margin-top: 1rem;
            align-self: flex-start;
        }

        @keyframes pulse {
            0% { opacity: 0.8; box-shadow: 0 0 5px var(--neon-primary); }
            50% { opacity: 0.4; box-shadow: 0 0 15px var(--neon-secondary); }
            100% { opacity: 0.8; box-shadow: 0 0 5px var(--neon-primary); }
        }

        /* Author Info */
        .author-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .author-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--neon-primary);
            object-fit: cover;
        }

        .author-name {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* Like/Dislike Buttons */
        .reaction-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .like-btn, .dislike-btn {
            background: transparent;
            border: 1px solid var(--neon-primary);
            border-radius: 30px;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .like-btn:hover {
            background: rgba(0, 255, 0, 0.1);
            border-color: #0f0;
        }

        .dislike-btn:hover {
            background: rgba(255, 0, 0, 0.1);
            border-color: #f00;
        }

        .like-btn.active {
            background: rgba(0, 255, 0, 0.2);
            border-color: #0f0;
        }

        .dislike-btn.active {
            background: rgba(255, 0, 0, 0.2);
            border-color: #f00;
        }

        /* AI Recommendations */
        .ai-recommendations {
            background: var(--card-glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--neon-secondary);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .ai-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .ai-header h2 {
            font-size: clamp(1.5rem, 4vw, 1.8rem);
            background: linear-gradient(90deg, var(--neon-secondary), var(--neon-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .ai-header i {
            font-size: clamp(1.5rem, 4vw, 1.8rem);
            color: var(--neon-secondary);
            animation: float 3s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .ai-content {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(250px, 100%), 1fr));
            gap: 1.5rem;
        }

        .ai-card {
            background: rgba(10, 10, 30, 0.5);
            border: 1px solid rgba(255, 0, 255, 0.3);
            border-radius: 10px;
            padding: 1.2rem;
            transition: all var(--transition-speed);
        }

        .ai-card:hover {
            border-color: var(--neon-secondary);
            transform: translateY(-5px);
        }

        .ai-card h4 {
            color: var(--neon-primary);
            margin-bottom: 0.5rem;
            font-size: clamp(1.1rem, 3vw, 1.3rem);
        }

        /* Podcasts Page */
        .podcast-player {
            background: var(--card-glass);
            border: 1px solid var(--neon-secondary);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .player-controls {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .control-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--neon-primary);
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .control-btn:hover {
            background: var(--neon-primary);
            color: var(--dark-bg);
            transform: scale(1.1);
        }

        .play-btn {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }

        .progress-container {
            background: rgba(255, 255, 255, 0.1);
            height: 5px;
            border-radius: 5px;
            margin: 1.5rem 0;
            cursor: pointer;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary));
            border-radius: 5px;
        }

        /* Audio Player */
        #audio-player {
            width: 100%;
            margin: 1.5rem 0;
            background: rgba(10, 10, 30, 0.5);
            border-radius: var(--border-radius);
            border: 1px solid var(--neon-primary);
            padding: 1rem;
        }

        /* Customization Panel */
        .customization-panel {
            position: fixed;
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            background: var(--card-glass);
            border: 1px solid var(--neon-primary);
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 1rem;
            z-index: 100;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .customization-panel.hidden {
            transform: translate(calc(100% - 40px), -50%);
        }

        .panel-toggle {
            position: absolute;
            left: -40px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: var(--card-glass);
            border: 1px solid var(--neon-primary);
            border-right: none;
            border-radius: 10px 0 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .customization-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .option-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .option-group label {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            color: var(--text-secondary);
        }

        .option-group input[type="color"] {
            width: 100%;
            height: 30px;
            border: 1px solid var(--neon-primary);
            border-radius: 5px;
            background: transparent;
            cursor: pointer;
        }

        .option-group input[type="range"] {
            width: 100%;
        }

        /* Footer */
        footer {
            background: linear-gradient(to top, #000010, #0a0a1a);
            padding: 3rem 2rem 2rem;
            text-align: center;
            position: relative;
            margin-top: 3rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(250px, 100%), 1fr));
            gap: 2rem;
            text-align: left;
        }

        .footer-column h3 {
            color: var(--neon-primary);
            margin-bottom: 1.5rem;
            font-size: clamp(1.1rem, 3vw, 1.3rem);
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 0.8rem;
        }

        .footer-column ul li a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color var(--transition-speed);
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        .footer-column ul li a:hover {
            color: var(--neon-primary);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            font-size: 1.2rem;
            transition: all var(--transition-speed);
        }

        .social-links a:hover {
            background: var(--neon-primary);
            color: var(--dark-bg);
            transform: translateY(-5px);
        }

        .copyright {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        /* Content Creation Styles */
        .creation-panel {
            background: var(--card-glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--neon-secondary);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--neon-primary);
            font-weight: 500;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border-radius: var(--border-radius);
            background: rgba(10, 10, 30, 0.5);
            border: 1px solid var(--neon-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary));
            color: var(--dark-bg);
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-weight: bold;
            cursor: pointer;
            transition: all var(--transition-speed);
            font-size: clamp(1rem, 2vw, 1.1rem);
            display: block;
            margin: 0 auto;
            width: 200px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 255, 255, 0.4);
        }

        .icon-selector {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(60px, 100%), 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .icon-option {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1.5rem, 4vw, 1.8rem);
            cursor: pointer;
            transition: all 0.2s;
        }

        .icon-option:hover, .icon-option.selected {
            background: var(--neon-primary);
            color: var(--dark-bg);
            transform: scale(1.1);
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: var(--border-radius);
            text-align: center;
            font-weight: 500;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .success {
            background: rgba(0, 255, 150, 0.2);
            border: 1px solid #00ff96;
            color: #00ff96;
        }

        .error {
            background: rgba(255, 50, 50, 0.2);
            border: 1px solid #ff3232;
            color: #ff3232;
        }

        /* Responsive Design */
        @media (max-width: 1100px) {
            .nav-links {
                gap: 0.8rem;
            }
            
            .theme-toggle {
                padding: 0.5rem;
            }
        }

        @media (max-width: 900px) {
            :root {
                --header-height: 70px;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-container {
                position: fixed;
                top: var(--header-height);
                left: 0;
                width: 100%;
                background: linear-gradient(135deg, #00172d 0%, #220525 100%);
                border-bottom: 1px solid var(--neon-primary);
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem 2rem;
                transform: translateY(-100%);
                transition: transform 0.4s ease;
                z-index: 999;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            }
            
            .nav-container.active {
                transform: translateY(0);
            }
            
            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .nav-links li {
                width: 100%;
            }
            
            .nav-links li a {
                display: block;
                width: 100%;
            }
            
            .theme-toggle, .voice-control {
                margin: 0.5rem 0;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .customization-panel {
                top: auto;
                bottom: 0;
                left: 0;
                right: 0;
                transform: none;
                width: 100%;
                border-radius: 10px 10px 0 0;
                border: 1px solid var(--neon-primary);
                border-bottom: none;
            }
            
            .customization-panel.hidden {
                transform: translateY(calc(100% - 40px));
            }
            
            .panel-toggle {
                left: 50%;
                top: -40px;
                transform: translateX(-50%);
                border: 1px solid var(--neon-primary);
                border-bottom: none;
                border-radius: 10px 10px 0 0;
            }
        }

        @media (max-width: 600px) {
            :root {
                --header-height: 60px;
            }
            
            header {
                padding: 0.5rem 1rem;
            }
            
            .logo {
                font-size: 1.8rem;
            }
            
            .page {
                padding: 0 1rem;
            }
            
            .icon-option {
                width: 50px;
                height: 50px;
            }
            
            .btn-submit {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">NEXUS</div>
            
            <button class="mobile-menu-btn" id="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-container" id="nav-container">
                <ul class="nav-links">
                    <li><a href="#" class="nav-link active" data-page="home">Home</a></li>
                    <li><a href="#" class="nav-link" data-page="articles">Articles</a></li>
                    <li><a href="#" class="nav-link" data-page="podcasts">Podcasts</a></li>
                    <li><a href="#" class="nav-link" data-page="create">Create</a></li>
                    <li><a href="#" class="nav-link" data-page="about">About</a></li>
                    <li><a href="#" class="nav-link" data-page="contact">Contact</a></li>
                </ul>
                <button class="theme-toggle" id="theme-toggle">DARK/LIGHT</button>
                <button class="voice-control" id="voice-btn"><i class="fas fa-microphone"></i></button>
            </div>
        </div>
    </header>
 <!-- Customization Panel -->
    <div class="customization-panel">
        <div class="panel-toggle"><i class="fas fa-sliders-h"></i></div>
        <div class="customization-options">
            <h3>Customize</h3>
            <div class="option-group">
                <label>Primary Color</label>
                <input type="color" id="primary-color" value="#00ffff">
            </div>
            <div class="option-group">
                <label>Secondary Color</label>
                <input type="color" id="secondary-color" value="#ff00ff">
            </div>
            <div class="option-group">
                <label>Hologram Intensity</label>
                <input type="range" id="hologram-intensity" min="0" max="1" step="0.1" value="0.8">
            </div>
            <div class="option-group">
                <label>Particle Density</label>
                <input type="range" id="particle-density" min="10" max="100" value="50">
            </div>
            <button class="theme-toggle" id="reset-settings">Reset</button>
        </div>
    </div>
    <!-- Page Content -->
    <main>
        <!-- Home Page -->
        <section id="home-page" class="page active">
            <div class="page-header">
                <h1>Welcome to the Future</h1>
                <p>Exploring the frontiers of technology, AI, quantum computing, and beyond. Join us as we navigate the next evolution of human innovation.</p>
            </div>
            
            <div class="ai-recommendations">
                <div class="ai-header">
                    <i class="fas fa-star"></i>
                    <h2>Top Articles</h2>
                </div>
                <div class="content-grid">
                    <?php foreach ($top_articles as $article): ?>
                    <div class="card hologram-card">
                        <div class="card-img"><i class="<?= htmlspecialchars($article['icon']) ?>"></i></div>
                        <h3><?= htmlspecialchars($article['title']) ?></h3>
                        <p><?= htmlspecialchars(substr($article['content'], 0, 150)) ?>...</p>
                        <div class="content-meta">
                            <span class="neon-tag"><?= htmlspecialchars(explode(',', $article['tags'])[0]) ?></span>
                            <div class="author-info">
                                <img src="<?= htmlspecialchars($article['author_image'] ?: 'https://via.placeholder.com/40') ?>" alt="Author" class="author-image">
                                <span class="author-name">Author</span>
                            </div>
                        </div>
                        <div class="reaction-buttons">
                            <button class="like-btn" data-id="<?= $article['id'] ?>">
                                <i class="fas fa-thumbs-up"></i> <?= $article['likes'] ?>
                            </button>
                            <button class="dislike-btn" data-id="<?= $article['id'] ?>">
                                <i class="fas fa-thumbs-down"></i> <?= $article['dislikes'] ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="ai-recommendations">
                <div class="ai-header">
                    <i class="fas fa-star"></i>
                    <h2>Top Podcasts</h2>
                </div>
                <div class="content-grid">
                    <?php foreach ($top_podcasts as $podcast): ?>
                    <div class="card hologram-card">
                        <div class="card-img"><i class="<?= htmlspecialchars($podcast['icon']) ?>"></i></div>
                        <h3><?= htmlspecialchars($podcast['title']) ?></h3>
                        <p><?= htmlspecialchars(substr($podcast['content'], 0, 150)) ?>...</p>
                        <div class="content-meta">
                            <span class="neon-tag"><?= htmlspecialchars(explode(',', $podcast['tags'])[0]) ?></span>
                            <div class="author-info">
                                <img src="<?= htmlspecialchars($podcast['author_image'] ?: 'https://via.placeholder.com/40') ?>" alt="Author" class="author-image">
                                <span class="author-name">Author</span>
                            </div>
                        </div>
                        <div class="reaction-buttons">
                            <button class="like-btn" data-id="<?= $podcast['id'] ?>">
                                <i class="fas fa-thumbs-up"></i> <?= $podcast['likes'] ?>
                            </button>
                            <button class="dislike-btn" data-id="<?= $podcast['id'] ?>">
                                <i class="fas fa-thumbs-down"></i> <?= $podcast['dislikes'] ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Articles Page -->
        <section id="articles-page" class="page">
            <div class="page-header">
                <h1>Tech Articles</h1>
                <p>Deep dives into emerging technologies, comprehensive analyses, and thought leadership in the tech space.</p>
            </div>
            
            <div class="content-grid">
                <?php foreach ($articles as $article): ?>
                <div class="card hologram-card">
                    <div class="card-img"><i class="<?= htmlspecialchars($article['icon']) ?>"></i></div>
                    <h3><?= htmlspecialchars($article['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($article['content'], 0, 150)) ?>...</p>
                    <div class="content-meta">
                        <span class="neon-tag"><?= htmlspecialchars(explode(',', $article['tags'])[0]) ?></span>
                        <div class="author-info">
                            <img src="<?= htmlspecialchars($article['author_image'] ?: 'https://via.placeholder.com/40') ?>" alt="Author" class="author-image">
                            <span class="author-name">Author</span>
                        </div>
                    </div>
                    <div class="reaction-buttons">
                        <button class="like-btn" data-id="<?= $article['id'] ?>">
                            <i class="fas fa-thumbs-up"></i> <?= $article['likes'] ?>
                        </button>
                        <button class="dislike-btn" data-id="<?= $article['id'] ?>">
                            <i class="fas fa-thumbs-down"></i> <?= $article['dislikes'] ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Podcasts Page -->
        <section id="podcasts-page" class="page">
            <div class="page-header">
                <h1>Future Casts</h1>
                <p>Audio explorations of tomorrow's technologies, featuring interviews with leading innovators and futurists.</p>
            </div>
            
            <div class="content-grid">
                <?php foreach ($podcasts as $podcast): ?>
                <div class="card hologram-card">
                    <div class="card-img"><i class="<?= htmlspecialchars($podcast['icon']) ?>"></i></div>
                    <h3><?= htmlspecialchars($podcast['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($podcast['content'], 0, 150)) ?>...</p>
                    <div class="content-meta">
                        <span class="neon-tag"><?= htmlspecialchars(explode(',', $podcast['tags'])[0]) ?></span>
                        <div class="author-info">
                            <img src="<?= htmlspecialchars($podcast['author_image'] ?: 'https://via.placeholder.com/40') ?>" alt="Author" class="author-image">
                            <span class="author-name">Author</span>
                        </div>
                    </div>
                    <?php if ($podcast['audio_url']): ?>
                    <div class="podcast-player">
                        <audio id="audio-player-<?= $podcast['id'] ?>" controls class="audio-player">
                            <source src="<?= htmlspecialchars($podcast['audio_url']) ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                    <?php endif; ?>
                    <div class="reaction-buttons">
                        <button class="like-btn" data-id="<?= $podcast['id'] ?>">
                            <i class="fas fa-thumbs-up"></i> <?= $podcast['likes'] ?>
                        </button>
                        <button class="dislike-btn" data-id="<?= $podcast['id'] ?>">
                            <i class="fas fa-thumbs-down"></i> <?= $podcast['dislikes'] ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Create Content Page -->
        <section id="create-page" class="page">
            <div class="page-header">
                <h1>Create Content</h1>
                <p>Share your insights with the Nexus community</p>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="message success"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="message error"><?= $error_message ?></div>
            <?php endif; ?>
            
            <form method="POST" class="creation-panel" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="content-type">Content Type</label>
                    <select id="content-type" name="type" required>
                        <option value="article">Article</option>
                        <option value="podcast">Podcast</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="content-title">Title</label>
                    <input type="text" id="content-title" name="title" placeholder="Enter a compelling title" required>
                </div>
                
                <div class="form-group">
                    <label for="content-description">Content</label>
                    <textarea id="content-description" name="content" placeholder="Write your content here..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="content-tags">Tags (comma separated)</label>
                    <input type="text" id="content-tags" name="tags" placeholder="e.g., #AI, #Quantum, #Space" required>
                </div>
                
                <div class="form-group">
                    <label>Select an Icon</label>
                    <div class="icon-selector" id="icon-selector">
                        <div class="icon-option selected" data-icon="fas fa-microchip"><i class="fas fa-microchip"></i></div>
                        <div class="icon-option" data-icon="fas fa-brain"><i class="fas fa-brain"></i></div>
                        <div class="icon-option" data-icon="fas fa-rocket"><i class="fas fa-rocket"></i></div>
                        <div class="icon-option" data-icon="fas fa-atom"><i class="fas fa-atom"></i></div>
                        <div class="icon-option" data-icon="fas fa-dna"><i class="fas fa-dna"></i></div>
                        <div class="icon-option" data-icon="fas fa-robot"><i class="fas fa-robot"></i></div>
                        <div class="icon-option" data-icon="fas fa-vr-cardboard"><i class="fas fa-vr-cardboard"></i></div>
                        <div class="icon-option" data-icon="fas fa-satellite"><i class="fas fa-satellite"></i></div>
                    </div>
                    <input type="hidden" id="selected-icon" name="icon" value="fas fa-microchip">
                </div>
                
                <div class="form-group">
                    <label for="author-image">Author Image</label>
                    <input type="file" id="author-image" name="author_image" accept="image/*">
                </div>
                
                <div class="form-group" id="audio-upload-container" style="display: none;">
                    <label for="audio-file">Podcast Audio File</label>
                    <input type="file" id="audio-file" name="audio_file" accept="audio/*">
                </div>
                
                <button class="btn-submit" name="submit-content" type="submit">Publish Content</button>
            </form>
            
            <div class="ai-recommendations">
                <div class="ai-header">
                    <i class="fas fa-lightbulb"></i>
                    <h2>Content Creation Tips</h2>
                </div>
                <div class="ai-content">
                    <div class="ai-card">
                        <h4>Engaging Titles</h4>
                        <p>Start with a compelling question or surprising statistic to grab attention.</p>
                    </div>
                    <div class="ai-card">
                        <h4>Clear Structure</h4>
                        <p>Use headings and short paragraphs to make your content scannable.</p>
                    </div>
                    <div class="ai-card">
                        <h4>Relevant Tags</h4>
                        <p>Add 3-5 relevant tags to help readers find your content.</p>
                    </div>
                    <div class="ai-card">
                        <h4>Visual Elements</h4>
                        <p>Choose an icon that represents your content's main theme.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Page -->
        <section id="about-page" class="page">
            <div class="page-header">
                <h1>About Nexus</h1>
                <p>We're a collective of technologists, futurists, and writers exploring the bleeding edge of innovation.</p>
            </div>
            
            <div class="content-grid">
                <div class="card hologram-card">
                    <div class="card-img"><i class="fas fa-history"></i></div>
                    <h3>Our Story</h3>
                    <p>Founded in 2035, Nexus began as a small group of MIT researchers passionate about making complex technologies accessible to everyone.</p>
                </div>
                
                <div class="card hologram-card">
                    <div class="card-img"><i class="fas fa-bullseye"></i></div>
                    <h3>Our Mission</h3>
                    <p>To illuminate the path of technological progress and explore its implications for humanity's future.</p>
                </div>
                
                <div class="card hologram-card">
                    <div class="card-img"><i class="fas fa-users"></i></div>
                    <h3>Our Team</h3>
                    <p>A diverse team of scientists, journalists, and designers from 12 countries working together remotely.</p>
                </div>
            </div>
        </section>

        <!-- Contact Page -->
        <section id="contact-page" class="page">
            <div class="page-header">
                <h1>Connect With Us</h1>
                <p>Have questions, story ideas, or partnership inquiries? Reach out to our team.</p>
            </div>
            
            <div class="content-grid">
                <div class="card hologram-card">
                    <div class="card-img"><i class="fas fa-envelope"></i></div>
                    <h3>General Inquiries</h3>
                    <p>info@nexus-future.tech</p>
                </div>
                
                <div class="card hologram-card">
                    <div class="card-img"><i class="fas fa-newspaper"></i></div>
                    <h3>Editorial Pitch</h3>
                    <p>editors@nexus-future.tech</p>
                </div>
                
                <div class="card hologram-card">
                    <div class="card-img"><i class="fas fa-handshake"></i></div>
                    <h3>Partnerships</h3>
                    <p>partners@nexus-future.tech</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Explore</h3>
                <ul>
                    <li><a href="#">Latest Articles</a></li>
                    <li><a href="#">Podcast Series</a></li>
                    <li><a href="#">Tech Reports</a></li>
                    <li><a href="#">Future Forecasts</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Topics</h3>
                <ul>
                    <li><a href="#">Artificial Intelligence</a></li>
                    <li><a href="#">Space Exploration</a></li>
                    <li><a href="#">Biotechnology</a></li>
                    <li><a href="#">Quantum Computing</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Company</h3>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Advertise</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Connect</h3>
                <p>Follow us for the latest updates</p>
                <div class="social-links">
                    <a href="https://x.com/mpaeboi?s=21"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            <p>© 2077 NEXUS FUTURE TECH. All rights reserved. Designed for the next generation.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", () => {
    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navContainer = document.getElementById('nav-container');

    if (mobileMenuBtn && navContainer) {
        mobileMenuBtn.addEventListener('click', () => {
            navContainer.classList.toggle('active');
            mobileMenuBtn.innerHTML = navContainer.classList.contains('active')
                ? '<i class="fas fa-times"></i>'
                : '<i class="fas fa-bars"></i>';
        });
    }

    // Page Navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetPage = link.getAttribute('data-page');
            if (!targetPage) return;

            if (navContainer.classList.contains('active')) {
                navContainer.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }

            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            document.querySelectorAll('.page').forEach(page => page.classList.remove('active'));
            const targetEl = document.getElementById(`${targetPage}-page`);
            if (targetEl) targetEl.classList.add('active');

            window.scrollTo(0, 0);
        });
    });

    // Theme Toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');

            document.documentElement.style.setProperty('--dark-bg', isLight ? '#f0f0f5' : '#0a0a15');
            document.documentElement.style.setProperty('--text-primary', isLight ? '#000' : '#fff');
            document.documentElement.style.setProperty('--text-secondary', isLight ? '#333' : '#aaa');
            document.documentElement.style.setProperty('--card-glass', isLight ? 'rgba(240, 240, 255, 0.7)' : 'rgba(20, 20, 40, 0.7)');
        });
    }

    // Voice Control
    const voiceBtn = document.getElementById('voice-btn');
    if (voiceBtn) {
        voiceBtn.addEventListener('click', () => {
            alert("Voice control activated. Say 'Home', 'Articles', 'Podcasts', 'About', or 'Contact' to navigate.");
            const commands = ['home', 'articles', 'podcasts', 'about', 'contact'];
            const randomCommand = commands[Math.floor(Math.random() * commands.length)];

            setTimeout(() => {
                alert(`Heard command: "${randomCommand}" - Navigating now.`);
                const link = document.querySelector(`.nav-link[data-page="${randomCommand}"]`);
                if (link) link.click();
            }, 1500);
        });
    }

    // Customization Panel
    const panel = document.querySelector('.customization-panel');
    const panelToggle = document.querySelector('.panel-toggle');
    if (panel && panelToggle) {
        panelToggle.addEventListener('click', () => {
            panel.classList.toggle('hidden');
        });
    }

    // Color Customization
    const primaryColor = document.getElementById('primary-color');
    const secondaryColor = document.getElementById('secondary-color');
    if (primaryColor) {
        primaryColor.addEventListener('input', (e) => {
            document.documentElement.style.setProperty('--neon-primary', e.target.value);
        });
    }
    if (secondaryColor) {
        secondaryColor.addEventListener('input', (e) => {
            document.documentElement.style.setProperty('--neon-secondary', e.target.value);
        });
    }

    // Hologram Intensity
    const hologramInput = document.getElementById('hologram-intensity');
    if (hologramInput) {
        hologramInput.addEventListener('input', (e) => {
            document.documentElement.style.setProperty('--hologram-intensity', e.target.value);
        });
    }

    // Particle Density
    const particleInput = document.getElementById('particle-density');
    if (particleInput) {
        particleInput.addEventListener('input', (e) => {
            document.documentElement.style.setProperty('--particle-density', e.target.value);
        });
    }

    // Reset Settings
    const resetBtn = document.getElementById('reset-settings');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            document.documentElement.style.setProperty('--neon-primary', '#0ff');
            document.documentElement.style.setProperty('--neon-secondary', '#f0f');
            document.documentElement.style.setProperty('--hologram-intensity', '0.8');
            if (primaryColor) primaryColor.value = '#00ffff';
            if (secondaryColor) secondaryColor.value = '#ff00ff';
            if (hologramInput) hologramInput.value = '0.8';

            document.body.classList.remove('light-mode');
        });
    }

    // ParticlesJS
    if (typeof particlesJS !== 'undefined') {
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: parseInt(getComputedStyle(document.documentElement).getPropertyValue('--particle-density')) || 50,
                    density: { enable: true, value_area: 800 }
                },
                color: { value: '#00ffff' },
                shape: {
                    type: 'circle',
                    stroke: { width: 0, color: '#000000' }
                },
                opacity: {
                    value: 0.5,
                    random: true,
                    anim: { enable: true, speed: 1, opacity_min: 0.1, sync: false }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: { enable: true, speed: 2, size_min: 0.3, sync: false }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#00ffff',
                    opacity: 0.2,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 1,
                    direction: 'none',
                    random: true,
                    straight: false,
                    out_mode: 'out',
                    bounce: false
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: { enable: true, mode: 'grab' },
                    onclick: { enable: true, mode: 'push' },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 140,
                        line_linked: { opacity: 0.5 }
                    },
                    push: { particles_nb: 4 }
                }
            },
            retina_detect: true
        });
    }

    // Icon Selection
    document.querySelectorAll('.icon-option').forEach(icon => {
        icon.addEventListener('click', () => {
            document.querySelectorAll('.icon-option').forEach(i => i.classList.remove('selected'));
            icon.classList.add('selected');
            const selectedIconInput = document.getElementById('selected-icon');
            if (selectedIconInput) {
                selectedIconInput.value = icon.getAttribute('data-icon');
            }
        });
    });

    // Content type toggle
    const contentTypeSelect = document.getElementById('content-type');
    const audioContainer = document.getElementById('audio-upload-container');
    if (contentTypeSelect && audioContainer) {
        contentTypeSelect.addEventListener('change', function () {
            audioContainer.style.display = this.value === 'podcast' ? 'block' : 'none';
        });
    }

    // Like/Dislike functionality
    document.querySelectorAll('.like-btn, .dislike-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const action = this.classList.contains('like-btn') ? 'like' : 'dislike';

            const icon = this.querySelector('i');
            const countNode = icon && icon.nextSibling;
            if (countNode && countNode.nodeType === Node.TEXT_NODE) {
                let count = parseInt(countNode.nodeValue.trim()) || 0;
                countNode.nodeValue = ` ${count + 1}`;
            }

            this.classList.add('active');

            fetch(`?action=${action}&id=${encodeURIComponent(id)}`)
                .then(res => res.text())
                .then(() => {
                    setTimeout(() => this.classList.remove('active'), 1000);
                })
                .catch(err => console.error("Error:", err));
        });
    });

    // Audio player logic
    document.querySelectorAll('audio').forEach(audio => {
        audio.addEventListener('play', function () {
            document.querySelectorAll('audio').forEach(other => {
                if (other !== this) other.pause();
            });
        });
    });
});
</script>


</body>
</html>