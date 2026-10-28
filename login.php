<?php
// Set secure session cookie parameters before session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
// Session idle timeout (20 minutes)
$timeout = 20 * 60; // 20 minutes in seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Last request was more than 20 minutes ago
    session_unset();     // Unset $_SESSION variable for the run-time
    session_destroy();   // Destroy session data in storage
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time
// Handle redirect page
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/styles/login.css">
    <style>
        .alert {
            padding: 15px;
            margin: 15px auto;
            width: 80%;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="main">	
        <input type="checkbox" id="chk" checked>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            $_SESSION['faculty_id'] = (string) $faculty['_id'];
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
            echo '<div class="alert alert-warning">Your session has expired due to inactivity. Please log in again.</div>';
        }
        ?>
        
        <div class="signup">
        <form id="signupForm" action="src/controller/create_account.php" method="post">
            <label for="chk" aria-label="Switch to signup"><i class="fas fa-user-plus"></i> Sign Up</label>
            
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" id="email" name="email" placeholder="Email" required>
            <div id="emailError" style="color: red; font-size: 0.9em; display: none; margin-left: 10px"></div>
            
            <input type="password" id="password" name="password" placeholder="Password" required>
            <div id="passwordStrength" style="margin-top:1px; font-size:0.9em; margin-left: 35px"></div>
            <select id="role" name="role" required>
                <option value="" disabled selected>Select your role</option>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
            </select>
            <button type="submit"><i class="fas fa-user-plus"></i> Register</button>
        </form>
    </div>

        <div class="login">
            <form action="src/controller/login_user.php" method="post">
                <label for="chk" aria-label="Switch to login"><i class="fas fa-sign-in-alt"></i> Login</label>
                <input type="text" name="username" placeholder="Username or ID" required>
                <input type="password" name="Password" placeholder="Password" required>
                <input type="hidden" name="redirect" value="/index.php">
                <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
                <button type="button" onclick="toggleToSignup()"><i class="fas fa-user-plus"></i> Don't have an account?</button>
            </form>
        </div>
    </div>
    
    <!-- OTP Modal -->
    <div id="otpModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
        <div style="background:#fff; padding:20px; border-radius:8px; width:320px; max-width:90%; text-align:center;">
            <h3>Verify OTP</h3>
            <p>Enter the 6-digit code sent to your email.</p>
            <input id="otpInput" type="text" maxlength="6" placeholder="Enter OTP" style="width:100%; padding:10px; margin:10px 0;" />
            <div id="otpError" style="color:#c00; min-height:20px; font-size:0.9em;"></div>
            <button id="otpVerifyBtn" style="padding:10px 16px; margin-right:8px;">Verify</button>
            <button id="otpCancelBtn" style="padding:10px 16px; background:#eee;">Cancel</button>
        </div>
    </div>
    
    <script>
        function toggleToSignup() {
            // Uncheck the checkbox to show signup form
            document.getElementById('chk').checked = false;
        }
        // Client-side validation for signup form
        const form = document.getElementById('signupForm');
        const emailInput = document.getElementById('email');
        const roleSelect = document.getElementById('role');
        const emailError = document.getElementById('emailError');

        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');

        // Email validation and AJAX submit for signup
        form.addEventListener('submit', async function(event) {
            const role = roleSelect.value;
            const email = emailInput.value.trim();

            const studentPattern = /^[a-zA-Z0-9._%+-]+@bscse\.uiu\.ac\.bd$/;
            const facultyPattern = /^[a-zA-Z0-9._%+-]+@cse\.uiu\.ac\.bd$/;

            let errorMessage = "";

            if (role === 'student' && !studentPattern.test(email)) {
                errorMessage = "Invalid student email. It should end with @bscse.uiu.ac.bd";
            } else if (role === 'faculty' && !facultyPattern.test(email)) {
                errorMessage = "Invalid faculty email. It should end with @cse.uiu.ac.bd";
            }

            if (errorMessage) {
                emailError.textContent = errorMessage;
                emailError.style.display = "block";
                event.preventDefault();
                return;
            } else {
                emailError.style.display = "none";
            }

            // Submit via AJAX to get OTP without leaving page
            event.preventDefault();
            const formData = new FormData(form);
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                let data = {};
                try { data = await res.json(); } catch (_) {}
                // Always show modal so user sees OTP prompt; show any error inside modal
                showOtpModal();
                if (!res.ok || !data.success) {
                    const msg = (data && data.message) ? data.message : 'Failed to send OTP. If you are on local, email may be disabled.';
                    document.getElementById('otpError').textContent = msg;
                }
            } catch (e) {
                showOtpModal();
                document.getElementById('otpError').textContent = 'Network error while sending OTP. You can still try entering the code if received.';
            }
        });

        // Password strength meter
        passwordInput.addEventListener('input', function() {
            const value = passwordInput.value;
            let strength = 0;

            if (value.length >= 6) strength++;
            if (/[A-Z]/.test(value)) strength++;
            if (/[a-z]/.test(value)) strength++;
            if (/[0-9]/.test(value)) strength++;
            if (/[^A-Za-z0-9]/.test(value)) strength++; // special character

            let strengthText = "";
            let color = "";

            switch(strength) {
                case 0:
                case 1:
                case 2:
                    strengthText = "Weak";
                    color = "red";
                    break;
                case 3:
                case 4:
                    strengthText = "Medium";
                    color = "orange";
                    break;
                case 5:
                    strengthText = "Strong";
                    color = "lightgreen";
                    break;
            }

            passwordStrength.textContent = `Password Strength: ${strengthText}`;
            passwordStrength.style.color = color;
        });

        // OTP Modal logic
        const otpModal = document.getElementById('otpModal');
        const otpInput = document.getElementById('otpInput');
        const otpError = document.getElementById('otpError');
        const otpVerifyBtn = document.getElementById('otpVerifyBtn');
        const otpCancelBtn = document.getElementById('otpCancelBtn');

        function showOtpModal() {
            otpInput.value = '';
            otpError.textContent = '';
            otpModal.style.display = 'flex';
            otpInput.focus();
        }
        function hideOtpModal() { otpModal.style.display = 'none'; }

        otpCancelBtn.addEventListener('click', hideOtpModal);
        otpVerifyBtn.addEventListener('click', async function() {
            const otp = otpInput.value.trim();
            if (otp.length !== 6) {
                otpError.textContent = 'Please enter the 6-digit code.';
                return;
            }
            try {
                const res = await fetch('src/controller/verify_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'otp=' + encodeURIComponent(otp)
                });
                let data = {};
                try { data = await res.json(); } catch (_) {}
                if (res.ok && data && data.success) {
                    hideOtpModal();
                    alert('Account verified. Please log in.');
                    document.getElementById('chk').checked = true; // show login
                } else {
                    otpError.textContent = (data && data.message) ? data.message : 'Invalid OTP.';
                }
            } catch (e) {
                otpError.textContent = 'Network error.';
            }
        });
    </script>
</body>
</html>
