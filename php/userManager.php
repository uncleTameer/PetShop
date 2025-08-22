<?php
require_once 'dbConnect.php';
require_once 'config.php';

/**
 * User Management Class
 * Handles all user-related operations including security, loyalty points, addresses, and 2FA
 */
class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    /**
     * Create a new user account
     */
    public function createUser($userData) {
        try {
            // Validate required fields
            if (empty($userData['email']) || empty($userData['password']) || empty($userData['fullName'])) {
                return ['success' => false, 'message' => 'Missing required fields'];
            }
            
            // Check if email already exists
            $existingUser = $this->db->users->findOne(['email' => $userData['email']]);
            if ($existingUser) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Prepare user document
            $user = [
                'fullName' => sanitizeInput($userData['fullName']),
                'email' => sanitizeInput($userData['email']),
                'password' => $hashedPassword,
                'role' => $userData['role'] ?? 'user',
                'profilePicture' => null,
                'addresses' => [],
                'preferences' => [
                    'defaultCategoryId' => null,
                    'emailNotifications' => true,
                    'lowStockThreshold' => DEFAULT_LOW_STOCK_THRESHOLD
                ],
                'loyaltyPoints' => 0,
                'security' => [
                    'twoFactorEnabled' => false,
                    'twoFactorSecret' => null,
                    'lastPasswordChange' => time()
                ],
                'lockout' => [
                    'loginAttempts' => 0,
                    'locked' => false,
                    'lockoutTime' => null
                ],
                'audit' => [
                    'createdAt' => time(),
                    'createdIp' => getClientIP(),
                    'createdUA' => getUserAgent(),
                    'lastLoginAt' => null,
                    'lastLoginIp' => null,
                    'lastLoginUA' => null
                ]
            ];
            
            // Insert user
            $result = $this->db->users->insertOne($user);
            
            if ($result->getInsertedCount() === 1) {
                return [
                    'success' => true,
                    'userId' => (string)$result->getInsertedId(),
                    'message' => 'User created successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create user'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'An error occurred while creating user'];
        }
    }
    
    /**
     * Authenticate user login
     */
    public function authenticateUser($email, $password, $ip, $userAgent) {
        try {
            $user = $this->db->users->findOne(['email' => $email]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check if account is locked
            if (!empty($user['lockout']['locked']) && $user['lockout']['locked'] === true) {
                $lockoutTime = $user['lockout']['lockoutTime'] ?? 0;
                $currentTime = time();
                
                if ($currentTime - $lockoutTime < LOCKOUT_DURATION) {
                    $remainingTime = ceil((LOCKOUT_DURATION - ($currentTime - $lockoutTime)) / 60);
                    return [
                        'success' => false,
                        'message' => "Account is locked. Please try again in {$remainingTime} minutes."
                    ];
                } else {
                    // Unlock account after timeout
                    $this->unlockAccount($user['_id']);
                }
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->incrementLoginAttempts($user['_id']);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Reset login attempts and update audit info
            $this->resetLoginAttempts($user['_id']);
            $this->updateLoginAudit($user['_id'], $ip, $userAgent);
            
            // Create session
            $this->createUserSession($user['_id'], $ip, $userAgent);
            
            return [
                'success' => true,
                'user' => [
                    'id' => (string)$user['_id'],
                    'name' => $user['fullName'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'profilePicture' => $user['profilePicture'] ?? null,
                    'loyaltyPoints' => $user['loyaltyPoints'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Authentication failed'];
        }
    }
    
    /**
     * Increment failed login attempts
     */
    private function incrementLoginAttempts($userId) {
        try {
            $user = $this->db->users->findOne(['_id' => $userId]);
            $loginAttempts = ($user['lockout']['loginAttempts'] ?? 0) + 1;
            
            if ($loginAttempts >= MAX_LOGIN_ATTEMPTS) {
                // Lock account
                $this->db->users->updateOne(
                    ['_id' => $userId],
                    [
                        '$set' => [
                            'lockout.locked' => true,
                            'lockout.lockoutTime' => time(),
                            'lockout.loginAttempts' => $loginAttempts
                        ]
                    ]
                );
                
                // Send notification to admins
                $this->notifyAdminLockout($user);
                
            } else {
                // Update login attempts
                $this->db->users->updateOne(
                    ['_id' => $userId],
                    ['$set' => ['lockout.loginAttempts' => $loginAttempts]]
                );
            }
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error incrementing login attempts: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Reset login attempts
     */
    private function resetLoginAttempts($userId) {
        try {
            $this->db->users->updateOne(
                ['_id' => $userId],
                [
                    '$unset' => [
                        'lockout.loginAttempts' => '',
                        'lockout.lockoutTime' => '',
                        'lockout.locked' => ''
                    ]
                ]
            );
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error resetting login attempts: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Unlock account
     */
    private function unlockAccount($userId) {
        try {
            $this->db->users->updateOne(
                ['_id' => $userId],
                [
                    '$unset' => [
                        'lockout.locked' => '',
                        'lockout.lockoutTime' => '',
                        'lockout.loginAttempts' => ''
                    ]
                ]
            );
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error unlocking account: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Update login audit information
     */
    private function updateLoginAudit($userId, $ip, $userAgent) {
        try {
            $this->db->users->updateOne(
                ['_id' => $userId],
                [
                    '$set' => [
                        'audit.lastLoginAt' => time(),
                        'audit.lastLoginIp' => $ip,
                        'audit.lastLoginUA' => $userAgent
                    ]
                ]
            );
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error updating login audit: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create user session
     */
    private function createUserSession($userId, $ip, $userAgent) {
        try {
            $sessionId = session_id();
            
            $this->db->sessions->insertOne([
                'userId' => $userId,
                'sessionId' => $sessionId,
                'ip' => $ip,
                'userAgent' => $userAgent,
                'lastSeen' => time(),
                'isActive' => true,
                'createdAt' => time()
            ]);
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error creating user session: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Notify admin about account lockout
     */
    private function notifyAdminLockout($user) {
        try {
            $admins = $this->db->users->find(['role' => 'admin'])->toArray();
            
            foreach ($admins as $admin) {
                $notification = [
                    'type' => 'login_lockout',
                    'userId' => $admin['_id'],
                    'title' => 'Account Lockout Alert',
                    'message' => "User {$user['fullName']} ({$user['email']}) has been locked out due to multiple failed login attempts.",
                    'isRead' => false,
                    'createdAt' => time()
                ];
                
                $this->db->notifications->insertOne($notification);
            }
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error notifying admin about lockout: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Add address to user
     */
    public function addAddress($userId, $addressData) {
        try {
            $address = [
                'label' => sanitizeInput($addressData['label']),
                'name' => sanitizeInput($addressData['name']),
                'phone' => sanitizeInput($addressData['phone']),
                'line1' => sanitizeInput($addressData['line1']),
                'line2' => sanitizeInput($addressData['line2'] ?? ''),
                'city' => sanitizeInput($addressData['city']),
                'zip' => sanitizeInput($addressData['zip']),
                'country' => sanitizeInput($addressData['country'] ?? DEFAULT_COUNTRY),
                'isDefault' => $addressData['isDefault'] ?? false
            ];
            
            // If this is the default address, unset others
            if ($address['isDefault']) {
                $this->db->users->updateMany(
                    ['_id' => $userId],
                    ['$set' => ['addresses.$[].isDefault' => false]]
                );
            }
            
            $result = $this->db->users->updateOne(
                ['_id' => $userId],
                ['$push' => ['addresses' => $address]]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Address added successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to add address'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to add address'];
        }
    }
    
    /**
     * Update user preferences
     */
    public function updatePreferences($userId, $preferences) {
        try {
            $updateData = [];
            
            if (isset($preferences['defaultCategoryId'])) {
                $updateData['preferences.defaultCategoryId'] = $preferences['defaultCategoryId'];
            }
            
            if (isset($preferences['emailNotifications'])) {
                $updateData['preferences.emailNotifications'] = (bool)$preferences['emailNotifications'];
            }
            
            if (isset($preferences['lowStockThreshold'])) {
                $updateData['preferences.lowStockThreshold'] = (int)$preferences['lowStockThreshold'];
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No preferences to update'];
            }
            
            $result = $this->db->users->updateOne(
                ['_id' => $userId],
                ['$set' => $updateData]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Preferences updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update preferences'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to update preferences'];
        }
    }
    
    /**
     * Get user loyalty points
     */
    public function getLoyaltyPoints($userId) {
        try {
            $user = $this->db->users->findOne(['_id' => $userId], ['projection' => ['loyaltyPoints' => 1]]);
            return $user['loyaltyPoints'] ?? 0;
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting loyalty points: " . $e->getMessage());
            }
            return 0;
        }
    }
    
    /**
     * Add loyalty points to user
     */
    public function addLoyaltyPoints($userId, $points) {
        try {
            $result = $this->db->users->updateOne(
                ['_id' => $userId],
                ['$inc' => ['loyaltyPoints' => $points]]
            );
            
            return $result->getModifiedCount() === 1;
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error adding loyalty points: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Use loyalty points
     */
    public function useLoyaltyPoints($userId, $points) {
        try {
            $user = $this->db->users->findOne(['_id' => $userId], ['projection' => ['loyaltyPoints' => 1]]);
            $currentPoints = $user['loyaltyPoints'] ?? 0;
            
            if ($currentPoints < $points) {
                return ['success' => false, 'message' => 'Insufficient loyalty points'];
            }
            
            $result = $this->db->users->updateOne(
                ['_id' => $userId],
                ['$inc' => ['loyaltyPoints' => -$points]]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Loyalty points used successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to use loyalty points'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to use loyalty points'];
        }
    }
    
    /**
     * Get user sessions
     */
    public function getUserSessions($userId) {
        try {
            $sessions = $this->db->sessions->find(
                ['userId' => $userId, 'isActive' => true],
                ['sort' => ['lastSeen' => -1]]
            )->toArray();
            
            return $sessions;
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting user sessions: " . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Force logout from other devices
     */
    public function forceLogoutOtherDevices($userId, $currentSessionId) {
        try {
            $result = $this->db->sessions->updateMany(
                [
                    'userId' => $userId,
                    'sessionId' => ['$ne' => $currentSessionId],
                    'isActive' => true
                ],
                ['$set' => ['isActive' => false]]
            );
            
            return $result->getModifiedCount();
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error forcing logout from other devices: " . $e->getMessage());
            }
            return 0;
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $user = $this->db->users->findOne(['_id' => $userId]);
            if ($user) {
                unset($user['password']); // Don't return password
                return $user;
            }
            return null;
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Error getting user by ID: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $profileData) {
        try {
            $updateData = [];
            
            if (isset($profileData['fullName'])) {
                $updateData['fullName'] = sanitizeInput($profileData['fullName']);
            }
            
            if (isset($profileData['profilePicture'])) {
                $updateData['profilePicture'] = $profileData['profilePicture'];
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No profile data to update'];
            }
            
            $result = $this->db->users->updateOne(
                ['_id' => $userId],
                ['$set' => $updateData]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => true, 'message' => 'Profile updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update profile'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->db->users->findOne(['_id' => $userId]);
            
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'New password must be at least 6 characters'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $result = $this->db->users->updateOne(
                ['_id' => $userId],
                [
                    '$set' => [
                        'password' => $hashedPassword,
                        'security.lastPasswordChange' => time()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() === 1) {
                return ['success' => false, 'message' => 'Password changed successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to change password'];
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
}
?>
