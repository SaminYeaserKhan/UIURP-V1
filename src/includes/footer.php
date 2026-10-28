<?php
// Footer component for UIU Research Portal
?>

<link rel="stylesheet" href="src/includes/footer-styles.css">

<footer class="enhanced-footer neo-footer footer" id="footer-section">
    <div class="container footer-content">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h3 class="footer-title">UIU Research Portal</h3>
                <p class="footer-description">
                    Connecting innovative minds and groundbreaking research, fostering collaboration across disciplines.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <i class="bi bi-twitter-x"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="LinkedIn">
                        <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="YouTube">
                        <i class="bi bi-youtube"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <h4 class="footer-section-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="Research_page.php">Research Projects</a></li>
                    <li><a href="Faculty_Page.php">Faculty Profiles</a></li>
                    <li><a href="Research_page.php">Learning Resources</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <h4 class="footer-section-title">Research Areas</h4>
                <ul class="footer-links">
                    <li><a href="#ai">Artificial Intelligence</a></li>
                    <li><a href="#cs">Computer Science</a></li>
                    <li><a href="#engineering">Engineering</a></li>
                    <li><a href="#business">Business</a></li>
                    <li><a href="#social">Social Sciences</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h4 class="footer-section-title">Resources</h4>
                <ul class="footer-links">
                    <li><a href="#guidelines">Guidelines</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#support">Support</a></li>
                    <li><a href="#privacy">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="copyright-text">
                &copy; 2025 UIU Research Portal. All rights reserved. Built with passion for research excellence.
            </p>
        </div>
    </div>
</footer>

<?php
// Output global preference tracking scripts
if (function_exists('outputPreferenceTrackingScript')) {
    outputPreferenceTrackingScript();
}
?>