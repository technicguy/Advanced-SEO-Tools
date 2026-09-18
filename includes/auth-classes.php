<?php
// includes/Auth.php - Core authentication class

class Auth {
    private $conn;
    private $sessionDuration = 86400; // 24 hours in seconds
    
    /**
     * Constructor
     *
     * @param mysqli $conn Database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            
            session_start();
        }
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } else if (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            $this->regenerateSession();
        }
    }
    
    /**
     * Regenerate the session ID and update the timestamp
     */
    private function regenerateSession() {
        // Regenerate the session ID
        session_regenerate_id(true);
        
        // Update the timestamp
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Attempt to login a user
     *
     * @param string $username Username or email
     * @param string $password Plain text password
     * @param bool $remember Whether to remember the user
     * @return array Result of login attempt
     */
    public function login($username, $password, $remember = false) {
        // Determine if username is an email
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        // Prepare the query accordingly
        if ($isEmail) {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE email = ?");
        } else {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE username = ?");
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Your account is ' . $user['status'] . '. Please contact support.'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        // Update password hash if needed
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHash, $user['id']);
            $updateStmt->execute();
        }
        
        // Set up session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Create session record in database
        $this->createSession($user['id']);
        
        // Handle remember me
        if ($remember) {
            $this->setRememberMeCookie($user['id']);
        }
        
        // Update last login time
        $updateLoginStmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateLoginStmt->bind_param("i", $user['id']);
        $updateLoginStmt->execute();
        
        // Log the activity
        $this->logActivity($user['id'], 'login', 'User logged in');
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
    
    /**
     * Create a session record in the database
     *
     * @param int $userId User ID
     * @return bool Success status
     */
    private function createSession($userId) {
        // Generate a unique session ID
        $sessionId = bin2hex(random_bytes(32));
        
        // Get IP address and user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        // Calculate expiry
        $expiresAt = date('Y-m-d H:i:s', time() + $this->sessionDuration);
        
        // Insert into database
        $stmt = $this->conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $sessionId, $ipAddress, $userAgent, $expiresAt);
        
        if ($stmt->execute()) {
            // Store the session ID in the PHP session
            $_SESSION['db_session_id'] = $sessionId;
            return true;
        }
        
        return false;
    }
    
    /**
     * Set a remember me cookie
     *
     * @param int $userId User ID
     * @return bool Success status
     */
    private function setRememberMeCookie($userId) {
        // Generate a unique token
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        
        // Hash the validator
        $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
        
        // Set cookie expiry to 30 days
        $expires = time() + (30 * 24 * 60 * 60);
        
        // Delete any existing tokens for this user
        $stmt = $this->conn->prepare("DELETE FROM auth_tokens WHERE user_id = ? AND type = 'remember'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Insert the new token
        $expiresDb = date('Y-m-d H:i:s', $expires);
        $stmt = $this->conn->prepare("INSERT INTO auth_tokens (user_id, type, selector, token, expires_at) VALUES (?, 'remember', ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $selector, $hashedValidator, $expiresDb);
        
        if ($stmt->execute()) {
            // Set the cookie
            $cookieValue = $selector . ':' . $validator;
            setcookie('remember_me', $cookieValue, $expires, '/', '', true, true);
            return true;
        }
        
        return false;
    }
    
    /**
     * Log out the current user
     *
     * @return bool Success status
     */
    public function logout() {
        // Log the activity before clearing session
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
            
            // Remove the session from the database
            if (isset($_SESSION['db_session_id'])) {
                $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE session_id = ?");
                $stmt->bind_param("s", $_SESSION['db_session_id']);
                $stmt->execute();
            }
        }
        
        // Clear cookie
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/', '', true, true);
        }
        
        // Clear session data
        $_SESSION = [];
        
        // If it's desired to kill the session, also delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        return true;
    }
    
    /**
     * Register a new user
     *
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Plain text password
     * @param string $role User role (default: user)
     * @return array Result of registration attempt
     */
    public function register($username, $email, $password, $role = 'user') {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'All fields are required'
            ];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }
        
        // Username should be alphanumeric
        if (!ctype_alnum(str_replace(['_', '-'], '', $username))) {
            return [
                'success' => false,
                'message' => 'Username can only contain letters, numbers, underscores, and hyphens'
            ];
        }
        
        // Check if username already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Username already exists'
            ];
        }
        
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the user
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            
            // Create default user settings
            $this->createDefaultSettings($userId);
            
            // Log the activity
            $this->logActivity($userId, 'register', 'User registered');
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $stmt->error
            ];
        }
    }
    
    /**
     * Create default settings for a new user
     *
     * @param int $userId User ID
     * @return bool Success status
     */
    private function createDefaultSettings($userId) {
        // Generate a unique API key
        $apiKey = md5($userId . time() . rand());
        
        // Default dashboard layout
        $defaultLayout = json_encode([
            'widgets' => ['recent_analyses', 'performance_overview', 'seo_scores', 'quick_tools']
        ]);
        
        // Insert default settings
        $stmt = $this->conn->prepare("INSERT INTO user_settings (user_id, theme, dashboard_layout, api_key) VALUES (?, 'light', ?, ?)");
        $stmt->bind_param("iss", $userId, $defaultLayout, $apiKey);
        
        return $stmt->execute();
    }
    
    /**
     * Check if a user is logged in
     *
     * @return bool True if logged in, false otherwise
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get the current user's ID
     *
     * @return int|null User ID or null if not logged in
     */
    public function getUserId() {
        return $this->isLoggedIn() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Get the current user's role
     *
     * @return string|null User role or null if not logged in
     */
    public function getUserRole() {
        return $this->isLoggedIn() ? $_SESSION['role'] : null;
    }
    
    /**
     * Check if the current user is an admin
     *
     * @return bool True if admin, false otherwise
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Get user information by ID
     *
     * @param int $userId User ID
     * @return array|null User data or null if not found
     */
    public function getUserById($userId) {
        $stmt = $this->conn->prepare("SELECT id, username, email, role, status, last_login, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Update a user's password
     *
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return array Result of password update attempt
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        // Get the user's current password
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        $user = $result->fetch_assoc();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            // Log the activity
            $this->logActivity($userId, 'password_change', 'User changed password');
            
            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update password: ' . $stmt->error
            ];
        }
    }
    
    /**
     * Create a password reset token
     *
     * @param string $email User's email
     * @return array Result of token creation attempt
     */
    public function createPasswordResetToken($email) {
        // Check if email exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Email not found'
            ];
        }
        
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        
        // Delete any existing tokens for this user
        $stmt = $this->conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Generate a new token
        $token = bin2hex(random_bytes(32));
        
        // Set expiry to 1 hour from now
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        
        // Insert the token
        $stmt = $this->conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $token, $expiresAt);
        
        if ($stmt->execute()) {
            // Log the activity
            $this->logActivity($userId, 'password_reset_requested', 'Password reset requested');
            
            return [
                'success' => true,
                'token' => $token,
                'email' => $email
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create reset token: ' . $stmt->error
            ];
        }
    }
    
    /**
     * Reset a password using a token
     *
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array Result of password reset attempt
     */
    public function resetPassword($token, $newPassword) {
        // Check if token exists and is valid
        $stmt = $this->conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Invalid or expired token'
            ];
        }
        
        $tokenData = $result->fetch_assoc();
        
        // Check if token has expired
        if (strtotime($tokenData['expires_at']) < time()) {
            return [
                'success' => false,
                'message' => 'Token has expired'
            ];
        }
        
        $userId = $tokenData['user_id'];
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            // Delete the token
            $stmt = $this->conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            // Log the activity
            $this->logActivity($userId, 'password_reset', 'Password reset successful');
            
            return [
                'success' => true,
                'message' => 'Password reset successful'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to reset password: ' . $stmt->error
            ];
        }
    }
    
    /**
     * Log user activity
     *
     * @param int $userId User ID
     * @param string $activityType Type of activity
     * @param string $description Description of activity
     * @return bool Success status
     */
    public function logActivity($userId, $activityType, $description) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        $stmt = $this->conn->prepare("INSERT INTO user_activity_log (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $activityType, $description, $ipAddress);
        
        return $stmt->execute();
    }
}
