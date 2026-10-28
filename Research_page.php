<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/styles/home.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #7209b7;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
        }
        
        body {
            background-color: #f0f2f5;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        /* Background particles */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            opacity: 0;
            pointer-events: none;
            background: radial-gradient(circle at 30% 40%, rgba(76, 201, 240, 0.05), transparent 30%),
                        radial-gradient(circle at 70% 70%, rgba(114, 9, 183, 0.05), transparent 35%),
                        radial-gradient(circle at 80% 10%, rgba(247, 37, 133, 0.05), transparent 25%);
            transition: opacity 1.5s ease-in-out;
        }
        
        /* Pulsing glow effect for particles */
        @keyframes pulse-glow {
            0% { filter: blur(0px); }
            50% { filter: blur(2px); }
            100% { filter: blur(0px); }
        }
        
        #particles-js canvas {
            /* Removing the blur animation */
        }
        
        body.loaded #particles-js {
            opacity: 0.7;
        }
        
        /* Ensure content appears above particles */
        .search-container,
        .container,
        section,
        footer {
            position: relative;
            z-index: 1;
        }
        
        .search-container {
            background: linear-gradient(125deg, #4361ee, #3a0ca3, #7209b7, #f72585);
            background-size: 300% 300%;
            animation: gradientBG 12s ease infinite;
            min-height: 60vh;
            display: flex;
            align-items: center;
            position: relative;
            border-radius: 0 0 30% 70% / 30%;
            margin-bottom: 80px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 80px 0;
            overflow: hidden;
            transition: border-radius 0.5s ease-out;
        }
        
        .search-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            mix-blend-mode: overlay;
        }
        
        .search-container::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 100px;
            background: linear-gradient(to bottom, transparent, rgba(240, 242, 245, 0.8));
            z-index: 1;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .search-container h1 {
            color: white;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            letter-spacing: -1px;
            position: relative;
        }
        
        .search-container h1::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 2px;
        }
        
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .floating-element {
            position: absolute;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s linear infinite;
        }
        
        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            width: 120px;
            height: 120px;
            animation-duration: 25s;
        }
        
        .floating-element:nth-child(2) {
            top: 70%;
            left: 80%;
            width: 100px;
            height: 100px;
            animation-duration: 20s;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            top: 40%;
            left: 40%;
            width: 60px;
            height: 60px;
            animation-duration: 18s;
            animation-delay: 4s;
        }
        
        .floating-element:nth-child(4) {
            top: 80%;
            left: 20%;
            width: 90px;
            height: 90px;
            animation-duration: 22s;
            animation-delay: 6s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) translateX(0) rotate(0deg);
                opacity: 0.8;
            }
            50% {
                transform: translateY(-100px) translateX(100px) rotate(180deg);
                opacity: 0.4;
            }
            100% {
                transform: translateY(0) translateX(0) rotate(360deg);
                opacity: 0.8;
            }
        }
        
        .search-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 8px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
            z-index: 10;
        }
        
        .search-box:hover, .search-box:focus-within {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .search-box input {
            background: transparent;
            border: none;
            color: white;
            padding: 15px 25px;
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }
        
        .search-box input:focus {
            outline: none;
            box-shadow: none;
            background: transparent;
            color: white;
        }
        
        .search-box button {
            background: white;
            color: var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .search-box button:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        }
        
        .toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 30px;
            padding: 10px 24px;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            backdrop-filter: blur(5px);
            position: relative;
            overflow: hidden;
            z-index: 10;
        }
        
        .toggle-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s;
        }
        
        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .toggle-btn:hover::before {
            left: 100%;
        }
        
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 60px;
            font-weight: 700;
            color: var(--dark-color);
            font-size: 2.5rem;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }
        
        .section-title::after {
            content: attr(data-subtitle);
            position: absolute;
            bottom: -45px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.95rem;
            color: var(--secondary-color);
            font-weight: 400;
            width: 100%;
            text-align: center;
        }
        
        .card-container {
            perspective: 1000px;
            margin-bottom: 30px;
        }
        
        .project-card {
            border-radius: 16px;
            overflow: hidden;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .project-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .project-card .card-img {
            height: 200px;
            position: relative;
            overflow: hidden;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .project-card img {
            transition: all 0.3s ease;
            height: 100%;
            object-fit: cover;
            width: 100%;
            max-height: 200px;
        }
        
        .project-card:hover img {
            transform: scale(1.05);
        }
        
        .project-card .card-body {
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        
        .project-card .card-title {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--dark-color);
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .project-card:hover .card-title {
            color: var(--primary-color);
        }
        
        .project-card .card-text {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .project-info {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: #212529;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .project-info i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1rem;
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: absolute;
            top: -15px;
            left: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 3;
        }
        
        .badge-public {
            background: linear-gradient(135deg, #4cc9f0, #56cfe1);
            color: white;
        }
        
        .badge-private {
            background: linear-gradient(135deg, #f72585, #ff758f);
            color: white;
        }
        
        .no-results {
            padding: 60px;
            text-align: center;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }
        
        .no-results i {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 25px;
            opacity: 0.7;
        }
        
        .no-results p {
            font-size: 1.2rem;
            color: #6c757d;
            max-width: 70%;
            margin: 0 auto;
        }
        
        footer {
            background: linear-gradient(135deg, #212529, #141b24);
            padding: 60px 0 40px;
            margin-top: 100px;
            position: relative;
            overflow: hidden;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 7px;
            background: linear-gradient(90deg, #4361ee, #3a0ca3, #7209b7, #f72585);
        }
        
        footer p {
            margin-bottom: 0;
            opacity: 0.8;
        }
        
        .footer-btn {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
            padding: 8px 20px;
            border-radius: 30px;
        }
        
        .footer-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        /* Loader animation */
        .loader {
            display: none;
            justify-content: center;
            margin: 60px 0;
        }
        
        .loader .dot {
            width: 20px;
            height: 20px;
            margin: 0 8px;
            border-radius: 50%;
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            animation: loader-bounce 1.5s infinite ease-in-out both;
            box-shadow: 0 5px 15px rgba(66, 99, 235, 0.3);
        }
        
        .loader .dot:nth-child(1) {
            animation-delay: -0.3s;
        }
        
        .loader .dot:nth-child(2) {
            animation-delay: -0.15s;
        }
        
        @keyframes loader-bounce {
            0%, 80%, 100% {
                transform: scale(0);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* Scroll animations */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }
        
        .scroll-indicator .mouse {
            width: 30px;
            height: 50px;
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            position: relative;
        }
        
        .scroll-indicator .mouse::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background: white;
            border-radius: 50%;
            animation: scroll 2s infinite;
        }
        
        @keyframes scroll {
            0% {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .search-container {
                min-height: 50vh;
                border-radius: 0 0 20% 50% / 20%;
            }
            
            .search-container h1 {
                font-size: 2.8rem;
            }
            
            .section-title {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .search-container {
                min-height: 45vh;
                border-radius: 0 0 15% 30% / 15%;
            }
            
            .search-container h1 {
                font-size: 2.2rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .project-card .card-img {
                height: 220px;
            }
        }
        
        /* Special accent particles */
        .floating-accent {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(76, 201, 240, 0.15) 0%, rgba(76, 201, 240, 0) 70%);
            border-radius: 50%;
            filter: blur(20px);
            opacity: 0.7;
            animation: float-accent 25s infinite linear;
            pointer-events: none;
            z-index: 0;
        }
        
        .floating-accent:nth-child(1) {
            top: 20%;
            left: 10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(114, 9, 183, 0.12) 0%, rgba(114, 9, 183, 0) 70%);
            animation-duration: 30s;
        }
        
        .floating-accent:nth-child(2) {
            top: 70%;
            left: 80%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(247, 37, 133, 0.12) 0%, rgba(247, 37, 133, 0) 70%);
            animation-duration: 25s;
            animation-delay: 5s;
        }
        
        .floating-accent:nth-child(3) {
            top: 40%;
            left: 60%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(67, 97, 238, 0.12) 0%, rgba(67, 97, 238, 0) 70%);
            animation-duration: 28s;
            animation-delay: 2s;
        }
        
        @keyframes float-accent {
            0% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(-50px, 50px) rotate(90deg); }
            50% { transform: translate(0, 100px) rotate(180deg); }
            75% { transform: translate(50px, 50px) rotate(270deg); }
            100% { transform: translate(0, 0) rotate(360deg); }
        }
        
        /* Burst effect for click animation */
        .particle-burst {
            position: absolute;
            pointer-events: none;
            border-radius: 50%;
            z-index: 2;
            transform: translate(-50%, -50%);
            animation: burst-anim 1s forwards ease-out;
        }
        
        @keyframes burst-anim {
            0% {
                width: 0;
                height: 0;
                opacity: 0.7;
                background: radial-gradient(circle, rgba(114, 9, 183, 0.8) 0%, rgba(114, 9, 183, 0) 70%);
            }
            100% {
                width: 300px;
                height: 300px;
                opacity: 0;
                background: radial-gradient(circle, rgba(114, 9, 183, 0) 0%, rgba(114, 9, 183, 0) 70%);
            }
        }
    </style>
</head>
<body>
  <?php include 'src/includes/navbar.php'; ?>

  <!-- Background Particles -->
  <div id="particles-js"></div>
  
  <!-- Special accent elements -->
  <div class="floating-accent"></div>
  <div class="floating-accent"></div>
  <div class="floating-accent"></div>

  <div class="search-container text-center">
    <div class="floating-elements">
      <div class="floating-element"></div>
      <div class="floating-element"></div>
      <div class="floating-element"></div>
      <div class="floating-element"></div>
    </div>
    <div class="container position-relative" style="z-index: 5;">
      <h1 data-aos="fade-down" data-aos-duration="1000">Discover Groundbreaking Research</h1>
      <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
          <div class="search-box input-group" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="300">
            <input type="text" class="form-control" placeholder="Search for projects, keywords, or authors..." id="search-bar">
            <button class="btn" id="search-bttn">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
        <button class="toggle-btn" id="toggle-projects">
          <i class="fas fa-filter me-2"></i>Show All Projects
        </button>
      </div>
    </div>
  </div>

  <section class="container my-5">
    <div class="loader" id="loader">
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>
    
    <div class="row g-4" id="projectsList">
        <!-- Projects will be dynamically added here -->
    </div>
  </section>

  <footer class="text-light">
    <div class="container text-center">
      <div data-aos="fade-up">
        <h4 class="mb-4">UIU Research Portal</h4>
        <p class="mb-4 opacity-75">Connecting innovative minds and groundbreaking research</p>
        <p class="mt-5 pt-3">&copy; 2025 UIU Research Portal. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
      // Initialize particles.js with more optimized settings
      particlesJS('particles-js', {
          "particles": {
              "number": {
                  "value": 80,
                  "density": {
                      "enable": true,
                      "value_area": 1000
                  }
              },
              "color": {
                  "value": ["#4361ee", "#3a0ca3", "#7209b7", "#4cc9f0", "#f72585"]
              },
              "shape": {
                  "type": ["circle", "triangle", "polygon"],
                  "stroke": {
                      "width": 0,
                      "color": "#000000"
                  },
                  "polygon": {
                      "nb_sides": 5
                  }
              },
              "opacity": {
                  "value": 0.3,
                  "random": true,
                  "anim": {
                      "enable": true,
                      "speed": 0.8,
                      "opacity_min": 0.1,
                      "sync": false
                  }
              },
              "size": {
                  "value": 12,
                  "random": true,
                  "anim": {
                      "enable": true,
                      "speed": 2,
                      "size_min": 3,
                      "sync": false
                  }
              },
              "line_linked": {
                  "enable": true,
                  "distance": 180,
                  "color": "#7209b7",
                  "opacity": 0.25,
                  "width": 1.5
              },
              "move": {
                  "enable": true,
                  "speed": 1.8,
                  "direction": "none",
                  "random": true,
                  "straight": false,
                  "out_mode": "bounce",
                  "bounce": true,
                  "attract": {
                      "enable": true,
                      "rotateX": 500,
                      "rotateY": 1000
                  }
              }
          },
          "interactivity": {
              "detect_on": "window",
              "events": {
                  "onhover": {
                      "enable": true,
                      "mode": "bubble"
                  },
                  "onclick": {
                      "enable": true,
                      "mode": "push"
                  },
                  "resize": true
              },
              "modes": {
                  "grab": {
                      "distance": 140,
                      "line_linked": {
                          "opacity": 0.8
                      }
                  },
                  "bubble": {
                      "distance": 150,
                      "size": 16,
                      "duration": 1.5,
                      "opacity": 0.8,
                      "speed": 3
                  },
                  "repulse": {
                      "distance": 150,
                      "duration": 0.4
                  },
                  "push": {
                      "particles_nb": 6
                  },
                  "remove": {
                      "particles_nb": 2
                  }
              }
          },
          "retina_detect": true
      });
      
      // Function to reinitialize particles if they stop
      function reinitializeParticlesIfNeeded() {
          if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
              const pJS = window.pJSDom[0].pJS;
              if (!pJS.particles.move.enable || pJS.particles.array.length === 0) {
                  console.log("Reinitializing particles...");
                  pJS.particles.move.enable = true;
                  
                  // First try to refresh existing particles
                  try {
                      pJS.fn.particlesRefresh();
                  } catch (error) {
                      console.error("Error refreshing particles:", error);
                      
                      // If refreshing fails, destroy and recreate
                      try {
                          window.pJSDom[0].pJS.fn.vendors.destroypJS();
                          window.pJSDom = [];
                          particlesJS('particles-js', /* same config as above */);
                      } catch (err) {
                          console.error("Failed to reinitialize particles:", err);
                      }
                  }
              }
          } else {
              // If pJSDom is missing, reinitialize
              particlesJS('particles-js', /* same config as above */);
          }
      }
      
      // Add scroll effect to particles for depth
      let lastScrollY = window.scrollY;
      window.addEventListener('scroll', function() {
          const canvas = document.querySelector('#particles-js canvas');
          if (canvas) {
              const scrollDifference = window.scrollY - lastScrollY;
              lastScrollY = window.scrollY;
              
              // Apply enhanced parallax effect to particles
              const particles = window.pJSDom[0].pJS.particles;
              particles.array.forEach(particle => {
                  particle.y -= scrollDifference * 0.05;
                  
                  // Add slight horizontal movement for more dynamic effect
                  if (Math.random() > 0.5) {
                      particle.x += Math.random() * 0.2 - 0.1;
                  }
              });
          }
      });
      
      // Create periodic wave effects through particles
      setInterval(() => {
          if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
              const particles = window.pJSDom[0].pJS.particles;
              
              // Only proceed if particles are moving
              if (!particles.move.enable) {
                  reinitializeParticlesIfNeeded();
                  return;
              }
              
              const centerX = window.innerWidth / 2;
              const centerY = window.innerHeight / 2;
              
              particles.array.forEach(particle => {
                  // Calculate distance from center
                  const dx = particle.x - centerX;
                  const dy = particle.y - centerY;
                  const distance = Math.sqrt(dx * dx + dy * dy);
                  
                  // Create a wave effect
                  const direction = distance > 0 ? (dx / distance) : 0;
                  
                  // Push particles slightly outward in a wave
                  particle.x += direction * 2;
                  
                  // Reset particles that go too far
                  if (particle.x > window.innerWidth) {
                      particle.x = 0;
                  } else if (particle.x < 0) {
                      particle.x = window.innerWidth;
                  }
              });
          } else {
              reinitializeParticlesIfNeeded();
          }
      }, 5000);
      
      // Ensure particles always stay active, check every 2 seconds
      setInterval(() => {
          reinitializeParticlesIfNeeded();
      }, 2000);
      
      // Make canvas and particles container un-clickable to prevent stopping animation
      const particlesContainer = document.getElementById('particles-js');
      if (particlesContainer) {
          particlesContainer.addEventListener('click', function(e) {
              e.stopPropagation();
              e.preventDefault();
              
              // Create burst effect without stopping the animation
              const burst = document.createElement('div');
              burst.classList.add('particle-burst');
              burst.style.left = e.pageX + 'px';
              burst.style.top = e.pageY + 'px';
              document.body.appendChild(burst);
              
              setTimeout(() => {
                  burst.remove();
              }, 1000);
              
              return false;
          }, true);
      }
      
      // Add global click handler for adding particles but prevent it from stopping animation
      document.addEventListener('click', function(e) {
          // Don't create particles for clicks on interactive elements
          if (e.target.closest('a, button, input, .search-box, .toggle-btn')) {
              return;
          }
          
          if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
              const pJS = window.pJSDom[0].pJS;
              
              // Use a safer method: create a new particle directly through pJS API
              try {
                  for (let i = 0; i < 8; i++) {
                      const posX = e.clientX + ((Math.random() - 0.5) * 20);
                      const posY = e.clientY + ((Math.random() - 0.5) * 20);
                      
                      // Use the particle creation method from particles.js
                      pJS.fn.modes.pushParticles(1, {x: posX, y: posY});
                  }
              } catch (error) {
                  console.error("Error adding particles on click:", error);
                  // If there's an error, ensure animation is still running
                  setTimeout(reinitializeParticlesIfNeeded, 200);
              }
          }
          
          // Don't stop propagation - just make sure particles keep running
          setTimeout(reinitializeParticlesIfNeeded, 500);
      });
      
      // Handle window focus/blur events to ensure particles keep moving
      window.addEventListener('blur', function() {
          // When window loses focus, set a timer to keep checking
          window.particleCheckInterval = setInterval(reinitializeParticlesIfNeeded, 1000);
      });
      
      window.addEventListener('focus', function() {
          // When window regains focus, clear the intensive check interval
          if (window.particleCheckInterval) {
              clearInterval(window.particleCheckInterval);
          }
          
          // Do a single check and reinitialize if needed
          reinitializeParticlesIfNeeded();
      });
      
      // Add fade-in effect for particles
      setTimeout(() => {
          document.body.classList.add('loaded');
      }, 300);
      
      // Initialize AOS library with enhanced settings for snappier animations
      AOS.init({
          duration: 800, // Faster duration for snappier feel
          once: false,
          mirror: true,
          offset: 40, // Trigger a bit earlier
          easing: 'cubic-bezier(0.19, 1, 0.22, 1)', // Enhanced easing for snappier animations
          anchorPlacement: 'top-bottom', // Trigger when top of element reaches bottom of viewport
          disableMutationObserver: false,
          throttleDelay: 50 // More frequent updates
      });
      
      // Refresh AOS animations on window resize for better responsiveness
      window.addEventListener('resize', () => {
          AOS.refresh();
      });
      
      // Enhanced scroll animations with hardware acceleration
      let lastScrollPosition = 0;
      let ticking = false;
      
      function updateSearchContainerShape(scrollPos) {
          const searchContainer = document.querySelector('.search-container');
          const maxScroll = 150; // Reduced for faster transition
          
          // Calculate how much we've scrolled as a percentage (0 to 1)
          const scrollProgress = Math.min(1, scrollPos / maxScroll);
          
          // Use cubic bezier easing for smoother, snappier transition
          const eased = easeOutQuart(scrollProgress);
          
          // Interpolate between the start and end values for smoother transition
          const startRadiusX = 30;
          const endRadiusX = 15;
          const startRadiusY = 70;
          const endRadiusY = 35;
          const startRadiusZ = 30;
          const endRadiusZ = 15;
          
          const currentRadiusX = startRadiusX - (startRadiusX - endRadiusX) * eased;
          const currentRadiusY = startRadiusY - (startRadiusY - endRadiusY) * eased;
          const currentRadiusZ = startRadiusZ - (startRadiusZ - endRadiusZ) * eased;
          
          // Apply hardware acceleration with transform instead of border-radius when possible
          searchContainer.style.borderRadius = `0 0 ${currentRadiusX}% ${currentRadiusY}% / ${currentRadiusZ}%`;
          searchContainer.style.transform = `translateZ(0)`; // Force GPU acceleration
          
          ticking = false;
      }
      
      // Custom easing function for smoother animations
      function easeOutQuart(x) {
          return 1 - Math.pow(1 - x, 4);
      }
      
      // Optimized scroll handler with passive event listener for better performance
      window.addEventListener('scroll', function() {
          lastScrollPosition = window.scrollY;
          
          if (!ticking) {
              window.requestAnimationFrame(function() {
                  updateSearchContainerShape(lastScrollPosition);
              });
              ticking = true;
          }
      }, { passive: true });
      
      const projectsList = document.getElementById('projectsList');
      const searchBar = document.getElementById('search-bar');
      const searchButton = document.getElementById('search-bttn');
      const projectsTab = document.getElementById('projects-tab');
      const toggleButton = document.getElementById('toggle-projects');
      const loader = document.getElementById('loader');
      
      let showAllProjects = false;

      // Toggle between showing all projects and only public projects
      toggleButton.addEventListener('click', function() {
          showAllProjects = !showAllProjects;
          
          if (showAllProjects) {
              toggleButton.innerHTML = '<i class="fas fa-filter me-2"></i>Show Public Projects Only';
              loadProjects('src/model/fetch_all_projects.php?limit=15');
          } else {
              toggleButton.innerHTML = '<i class="fas fa-filter me-2"></i>Show All Projects';
              loadProjects('src/model/fetch_projects.php?limit=15');
          }
      });
      
      function showLoader() {
          loader.style.display = 'flex';
          projectsList.innerHTML = '';
      }
      
      function hideLoader() {
          loader.style.display = 'none';
      }
      
      function loadProjects(url) {
          showLoader();
          fetch(url)
              .then(response => response.json())
              .then(data => {
                  // Debug date formats
                  debugDateFormats(data);
                  displayProjects(data);
                  hideLoader();
                  
                  // Refresh animations
                  setTimeout(() => {
                      AOS.refresh();
                  }, 300);
              })
              .catch(error => {
                  console.error('Error loading projects:', error);
                  projectsList.innerHTML = `
                    <div class="col-12">
                      <div class="no-results" data-aos="fade-up">
                        <i class="fas fa-exclamation-circle"></i>
                        <p class="text-center text-danger">Error loading projects. Please try again later.</p>
                      </div>
                    </div>`;
                  hideLoader();
              });
      }

      // Load the first 15 projects when the "Projects" tab is clicked
      if (projectsTab) {
          projectsTab.addEventListener('click', function(event) {
              event.preventDefault();
              if (showAllProjects) {
                  loadProjects('src/model/fetch_all_projects.php?limit=15');
              } else {
                  loadProjects('src/model/fetch_projects.php?limit=15');
              }
          });
      }

      // Handle search input with animation
      searchBar.addEventListener('keypress', function(event) {
          if (event.key === 'Enter') {
              const searchString = searchBar.value.trim();
              if (searchString) {
                  // Add a pulse animation to the search button
                  searchButton.classList.add('animate__animated', 'animate__pulse');
                  setTimeout(() => {
                      searchButton.classList.remove('animate__animated', 'animate__pulse');
                  }, 1000);
                  handleSearch(searchString);
              }
          }
      });

      // Handle search button click
      searchButton.addEventListener('click', function() {
          const searchString = searchBar.value.trim();
          if (searchString) {
              handleSearch(searchString);
          }
      });

      function handleSearch(searchString) {
          const searchEndpoint = showAllProjects ? 
              'src/model/search_all_projects.php' : 
              'src/model/search_projects.php';
              
          showLoader();
          fetch(searchEndpoint, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json'
              },
              body: JSON.stringify({ searchString })
          })
          .then(response => response.json())
          .then(data => {
              displayProjects(data);
              hideLoader();
              
              // Scroll to results
              const resultsSection = document.querySelector('.section-title');
              if (resultsSection) {
                  resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
              }
              
              // Update the section title to show search results
              const sectionTitle = document.querySelector('.section-title');
              sectionTitle.setAttribute('data-subtitle', `Search results for "${searchString}"`);
              
              // Refresh animations
              setTimeout(() => {
                  AOS.refresh();
              }, 300);
          })
          .catch(error => {
              console.error('Error searching projects:', error);
              projectsList.innerHTML = `
                <div class="col-12">
                  <div class="no-results" data-aos="fade-up">
                    <i class="fas fa-exclamation-circle"></i>
                    <p class="text-center text-danger">Error searching projects. Please try again later.</p>
                  </div>
                </div>`;
              hideLoader();
          });
      }

      function formatDate(dateInput) {
          if (!dateInput) return 'Not specified';
          
          try {
              let dateValue;
              
              // Handle different date formats
              if (typeof dateInput === 'object') {
                  // MongoDB date object with $date property
                  if (dateInput.$date) {
                      if (typeof dateInput.$date === 'string') {
                          dateValue = dateInput.$date;
                      } else if (typeof dateInput.$date === 'object' && dateInput.$date.$numberLong) {
                          // Handle MongoDB long format
                          dateValue = parseInt(dateInput.$date.$numberLong);
                      } else {
                          dateValue = dateInput.$date;
                      }
                  } else {
                      dateValue = dateInput;
                  }
              } else if (typeof dateInput === 'string') {
                  // Direct string date
                  dateValue = dateInput;
              } else if (typeof dateInput === 'number') {
                  // Timestamp
                  dateValue = dateInput;
              }
              
              // Create date object
              const date = new Date(dateValue);
              
              // Validate date
              if (isNaN(date.getTime())) {
                  console.warn('Invalid date input:', dateInput);
                  return 'Date not available';
              }
              
              // Format the date
              return date.toLocaleDateString('en-US', { 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
              });
              
          } catch (error) {
              console.error('Error formatting date:', error, 'Input:', dateInput);
              return 'Date not available';
          }
      }

      // Utility function to format image paths consistently
      function formatImagePath(path) {
          if (!path) {
              // Instead of a static fallback image, use one of the research pictures randomly
              const researchImages = [
                  'assets/resources/research_picture/pub_1.jpg',
                  'assets/resources/research_picture/pub_2.jpg',
                  'assets/resources/research_picture/pub_3.jpg',
                  'assets/resources/research_picture/pub_4.jpg',
                  'assets/resources/research_picture/pub_5.jpg',
                  'assets/resources/research_picture/pub_6.jpg',
                  'assets/resources/research_picture/pub_7.jpg',
                  'assets/resources/research_picture/pub_8.jpeg',
                  'assets/resources/research_picture/pub_9.jpeg',
                  'assets/resources/research_picture/pub_10.jpeg'
              ];
              // Pick a random image from the array
              return researchImages[Math.floor(Math.random() * researchImages.length)];
          }
          
          // If it's an absolute URL, return as is
          if (path.startsWith('http')) {
              return path;
          }
          
          // Make sure path starts with a slash if it's a relative path
          if (!path.startsWith('/')) {
              path = '/' + path;
          }
          
          // Fix any double slashes except for http://
          return path.replace(/([^:])\/\//g, '$1/');
      }

      function displayProjects(projects) {
          projectsList.innerHTML = '';
          if (projects && projects.length > 0) {
              const uniqueProjects = [];
              const projectIds = new Set();
              
              // Array of research images to cycle through
              const researchImages = [
                  'assets/resources/research_picture/pub_1.jpg',
                  'assets/resources/research_picture/pub_2.jpg',
                  'assets/resources/research_picture/pub_3.jpg',
                  'assets/resources/research_picture/pub_4.jpg',
                  'assets/resources/research_picture/pub_5.jpg',
                  'assets/resources/research_picture/pub_6.jpg',
                  'assets/resources/research_picture/pub_7.jpg',
                  'assets/resources/research_picture/pub_8.jpeg',
                  'assets/resources/research_picture/pub_9.jpeg',
                  'assets/resources/research_picture/pub_10.jpeg'
              ];

              projects.forEach(project => {
                  if (!projectIds.has(project._id.$oid)) {
                      projectIds.add(project._id.$oid);
                      uniqueProjects.push(project);
                  }
              });

              uniqueProjects.forEach((project, index) => {
                  // Format date using the helper function
                  const formattedDate = formatDate(project.createdAt);
                  
                  // Get members/authors list with names
                  let authorsList = 'No authors listed';
                  if (project.members && project.members.length > 0) {
                      authorsList = project.members
                          .map(member => member.name || (member.role ? `${member.role}` : 'Author'))
                          .join(', ');
                  }

                  // Get supervisor name
                  let supervisorName = 'Not specified';
                  if (project.supervisor) {
                      if (typeof project.supervisor === 'string') {
                          supervisorName = project.supervisor;
                      } else if (typeof project.supervisor === 'object') {
                          supervisorName = project.supervisor.name || project.supervisor.userId || 'Not specified';
                      }
                  }
                  
                  // Create project card with animation
                  const projectCard = document.createElement('div');
                  projectCard.className = 'col-lg-4 col-md-6 card-container';
                  projectCard.setAttribute('data-aos', 'fade-up');
                  projectCard.setAttribute('data-aos-delay', (index % 3) * 100);
                  projectCard.setAttribute('data-aos-duration', 800 + (index % 3) * 100);
                  
                  // Determine badge class based on privacy
                  const badgeClass = typeof project.privacy !== 'undefined' ? 
                      (project.privacy === 0 ? 'badge-public' : 'badge-private') : 
                      'badge-secondary';
                  
                  // Determine badge text based on privacy
                  const badgeText = typeof project.privacy !== 'undefined' ? 
                      (project.privacy === 0 ? 'Public' : 'Private') : 
                      'Unknown';
                      
                  // Get a random image from the research_picture directory
                  const randomImageIndex = Math.floor(Math.random() * researchImages.length);
                  let imageSrc = researchImages[randomImageIndex];
                  let imageAlt = project.title || 'Research Project';
                  
                  // Only use project image data if explicitly available
                  if (project.coverImage && project.coverImage.url && project.coverImage.url.trim() !== '') {
                      imageSrc = formatImagePath(project.coverImage.url);
                      
                      // Use the alt text if available
                      if (project.coverImage.alt && project.coverImage.alt.trim() !== '') {
                          imageAlt = project.coverImage.alt;
                      }
                  }
                  
                  // Get another random image for fallback
                  const fallbackImageIndex = Math.floor(Math.random() * researchImages.length);
                  
                  projectCard.innerHTML = `
                    <div class="project-card">
                        <a href="Project_details.php?id=${project._id.$oid}" class="text-decoration-none">
                            <div class="card-img">
                                <img src="${imageSrc}" alt="${imageAlt}" class="img-fluid" onerror="this.onerror=null; this.src='${researchImages[fallbackImageIndex]}';">
                            </div>
                            <div class="card-body">
                                <span class="badge ${badgeClass}">${badgeText}</span>
                                <h5 class="card-title">${project.title}</h5>
                                <p class="card-text">${project.abstract ? (project.abstract.length > 100 ? project.abstract.substring(0, 100) + '...' : project.abstract) : (project.description ? (project.description.length > 100 ? project.description.substring(0, 100) + '...' : project.description) : 'No description available')}</p>
                                <div class="project-info">
                                    <i class="far fa-calendar-alt"></i>
                                    <span>${formattedDate}</span>
                                </div>
                                <div class="project-info">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <span>Supervisor: ${supervisorName}</span>
                                </div>
                                <div class="project-info">
                                    <i class="fas fa-users"></i>
                                    <span>${authorsList}</span>
                                </div>
                                <div class="project-info">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>${project.field || 'Research'}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                  `;
                  
                  projectsList.appendChild(projectCard);
              });
              
          } else {
              projectsList.innerHTML = `
                <div class="col-12">
                    <div class="no-results" data-aos="fade-up">
                        <i class="fas fa-search me-2"></i>
                        <p class="text-center">No matching projects found. Try different keywords or browse all available projects.</p>
                    </div>
                </div>`;
          }
      }

      // Load the first 15 projects on page load
      loadProjects('src/model/fetch_projects.php?limit=15');

      // Debug function to inspect date formats
      function debugDateFormats(projects) {
          if (projects && projects.length > 0 && projects[0].createdAt) {
              console.log('First project date format:', {
                  createdAt: projects[0].createdAt,
                  type: typeof projects[0].createdAt,
                  hasDateProperty: projects[0].createdAt && projects[0].createdAt.$date ? true : false,
                  datePropertyType: projects[0].createdAt && projects[0].createdAt.$date ? typeof projects[0].createdAt.$date : 'N/A'
              });
          } else {
              console.log('No projects with dates to debug');
          }
      }
  });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>