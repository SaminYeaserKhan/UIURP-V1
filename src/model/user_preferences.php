<?php
require __DIR__ . '/../../vendor/autoload.php';

class UserPreferences {
    private $db;
    private $collection;
    
    public function __construct() {
        try {
            $mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
            $this->db = $mongoClient->uiurp;
            $this->collection = $this->db->user_preferences;
        } catch (Exception $e) {
            error_log("MongoDB connection failed in UserPreferences: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    /**
     * Track user interaction
     */
    public function trackInteraction($userId, $interactionType, $itemId, $itemType, $metadata = []) {
        if (!$this->db) return false;
        
        try {
            // Debug logging
            error_log("DEBUG: Tracking interaction - User: $userId, Type: $interactionType, Item: $itemId, ItemType: $itemType");
            error_log("DEBUG: Metadata: " . json_encode($metadata));
            
            $interaction = [
                'user_id' => $userId,
                'interaction_type' => $interactionType, // 'view', 'click', 'search', 'like', 'comment'
                'item_id' => $itemId,
                'item_type' => $itemType, // 'project', 'event', 'forum_post', 'faculty', 'page'
                'metadata' => $metadata,
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'session_id' => session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ];
            
            $this->collection->insertOne($interaction);
            
            // Update user profile preferences
            $this->updateUserProfile($userId, $interactionType, $itemType, $metadata);
            
            return true;
        } catch (Exception $e) {
            error_log("Error tracking interaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user profile with aggregated preferences
     */
    private function updateUserProfile($userId, $interactionType, $itemType, $metadata) {
        try {
            error_log("DEBUG: Updating user profile - User: $userId, InteractionType: $interactionType, ItemType: $itemType");
            error_log("DEBUG: Profile metadata: " . json_encode($metadata));
            
            $profileCollection = $this->db->user_preference_profiles;
            
            // Get or create user profile
            $profile = $profileCollection->findOne(['user_id' => $userId]);
            
            if (!$profile) {
                $profile = [
                    'user_id' => $userId,
                    'interests' => [],
                    'preferred_categories' => [],
                    'activity_score' => 0,
                    'last_updated' => new MongoDB\BSON\UTCDateTime(),
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ];
                error_log("DEBUG: Created new profile for user $userId");
            }
            
            // Update activity score
            $weightGain = $this->getInteractionWeight($interactionType);
            $profile['activity_score'] = ($profile['activity_score'] ?? 0) + $weightGain;
            error_log("DEBUG: Activity score updated by $weightGain, new total: " . $profile['activity_score']);
            
            // Initialize or fix interests and keywords structures
            $interests = $profile['interests'] ?? [];
            $keywords = $profile['keywords'] ?? [];
            
            // Convert BSON documents to arrays if needed
            if ($interests instanceof MongoDB\Model\BSONDocument) {
                $interests = iterator_to_array($interests);
            }
            if ($keywords instanceof MongoDB\Model\BSONDocument) {
                $keywords = iterator_to_array($keywords);
            }
            
            // If interests is an indexed array (corrupted), reset it
            if (is_array($interests) && !empty($interests) && array_keys($interests) === range(0, count($interests) - 1)) {
                error_log("DEBUG: Resetting corrupted interests array");
                $interests = [];
            }
            
            // If keywords is an indexed array (corrupted), reset it  
            if (is_array($keywords) && !empty($keywords) && array_keys($keywords) === range(0, count($keywords) - 1)) {
                error_log("DEBUG: Resetting corrupted keywords array");
                $keywords = [];
            }
            
            // Ensure they are associative arrays
            if (!is_array($interests)) $interests = [];
            if (!is_array($keywords)) $keywords = [];
            
            error_log("DEBUG: Initial interests count: " . count($interests));
            
            // Update interests based on tags (for events, forum posts, projects)
            if (isset($metadata['tags']) && is_array($metadata['tags'])) {
                error_log("DEBUG: Processing tags: " . json_encode($metadata['tags']));
                foreach ($metadata['tags'] as $tag) {
                    if (is_string($tag) && !empty(trim($tag))) {
                        $normalizedTag = trim($tag);
                        if (!isset($interests[$normalizedTag])) {
                            $interests[$normalizedTag] = 0;
                        }
                        $interests[$normalizedTag] += $weightGain;
                        error_log("DEBUG: Added/updated interest '$normalizedTag' with weight $weightGain");
                    }
                }
            }
            
            // Update interests based on specialty (for faculty)
            if (isset($metadata['specialty']) && !empty($metadata['specialty'])) {
                $specialty = trim($metadata['specialty']);
                error_log("DEBUG: Processing specialty: $specialty");
                if (!isset($interests[$specialty])) {
                    $interests[$specialty] = 0;
                }
                $interests[$specialty] += $weightGain;
                error_log("DEBUG: Added/updated specialty interest '$specialty' with weight $weightGain");
            }
            
            // Update interests based on research interests (for faculty)
            if (isset($metadata['researchInterests']) && is_array($metadata['researchInterests'])) {
                error_log("DEBUG: Processing researchInterests: " . json_encode($metadata['researchInterests']));
                foreach ($metadata['researchInterests'] as $interest) {
                    if (is_string($interest) && !empty(trim($interest))) {
                        $normalizedInterest = trim($interest);
                        if (!isset($interests[$normalizedInterest])) {
                            $interests[$normalizedInterest] = 0;
                        }
                        $interests[$normalizedInterest] += $weightGain;
                        error_log("DEBUG: Added/updated research interest '$normalizedInterest' with weight $weightGain");
                    }
                }
            }
            
            // Update interests based on event type (for events)
            if (isset($metadata['eventType']) && !empty($metadata['eventType'])) {
                $eventType = trim($metadata['eventType']);
                error_log("DEBUG: Processing eventType: $eventType");
                if (!isset($interests[$eventType])) {
                    $interests[$eventType] = 0;
                }
                $interests[$eventType] += $weightGain;
                error_log("DEBUG: Added/updated eventType interest '$eventType' with weight $weightGain");
            }
            
            // Update interests based on title (for all content types)
            if (isset($metadata['title']) && !empty($metadata['title'])) {
                $title = trim($metadata['title']);
                error_log("DEBUG: Processing title for keywords: $title");
                // Extract keywords from title (simple word extraction)
                $titleWords = preg_split('/\s+/', strtolower($title));
                foreach ($titleWords as $word) {
                    $word = trim(preg_replace('/[^\w\s]/', '', $word)); // Remove punctuation
                    if (strlen($word) > 3) { // Only words longer than 3 characters
                        if (!isset($keywords[$word])) {
                            $keywords[$word] = 0;
                        }
                        $keywords[$word] += 1;
                        
                        // Also add as interest with lower weight
                        if (!isset($interests[$word])) {
                            $interests[$word] = 0;
                        }
                        $interests[$word] += 0.3;
                    }
                }
            }
            
            // Update preferred categories
            if (!isset($profile['preferred_categories'])) {
                $profile['preferred_categories'] = [];
            }
            if (!isset($profile['preferred_categories'][$itemType])) {
                $profile['preferred_categories'][$itemType] = 0;
            }
            $profile['preferred_categories'][$itemType] += $weightGain;
            error_log("DEBUG: Updated category preference for '$itemType' with weight $weightGain");
            
            // Update keywords from explicit keywords metadata
            if (isset($metadata['keywords']) && is_array($metadata['keywords'])) {
                error_log("DEBUG: Processing explicit keywords: " . json_encode($metadata['keywords']));
                foreach ($metadata['keywords'] as $keyword) {
                    if (is_string($keyword) && !empty(trim($keyword))) {
                        $normalizedKeyword = trim(strtolower($keyword));
                        if (!isset($keywords[$normalizedKeyword])) {
                            $keywords[$normalizedKeyword] = 0;
                        }
                        $keywords[$normalizedKeyword] += 1;
                        
                        // Also add as interest with lower weight
                        if (!isset($interests[$normalizedKeyword])) {
                            $interests[$normalizedKeyword] = 0;
                        }
                        $interests[$normalizedKeyword] += 0.5;
                        error_log("DEBUG: Added/updated keyword '$normalizedKeyword' as interest");
                    }
                }
            }
            
            // Save the updated interests and keywords back to profile
            $profile['interests'] = $interests;
            $profile['keywords'] = $keywords;
            
            $profile['last_updated'] = new MongoDB\BSON\UTCDateTime();
            
            // Debug: Log interests after processing
            error_log("DEBUG: Final interests count: " . count($profile['interests'] ?? []));
            error_log("DEBUG: Final interests (top 5): " . json_encode(array_slice($profile['interests'] ?? [], 0, 5, true)));
            error_log("DEBUG: Final categories: " . json_encode($profile['preferred_categories'] ?? []));
            
            // Upsert the profile
            $result = $profileCollection->replaceOne(
                ['user_id' => $userId],
                $profile,
                ['upsert' => true]
            );
            
            error_log("DEBUG: Profile update result - Modified: " . $result->getModifiedCount() . ", Upserted: " . ($result->getUpsertedId() ? 'Yes' : 'No'));
            
        } catch (Exception $e) {
            error_log("ERROR: Exception in updateUserProfile: " . $e->getMessage());
            error_log("ERROR: Stack trace: " . $e->getTraceAsString());
            error_log("ERROR: User ID: $userId, InteractionType: $interactionType, ItemType: $itemType");
            error_log("ERROR: Metadata: " . json_encode($metadata));
        }
    }
    
    /**
     * Get interaction weight for scoring
     */
    private function getInteractionWeight($interactionType) {
        $weights = [
            'view' => 1,
            'click' => 2,
            'search' => 3,
            'like' => 4,
            'comment' => 5,
            'share' => 3,
            'bookmark' => 4,
            'download' => 5
        ];
        
        return $weights[$interactionType] ?? 1;
    }
    
    /**
     * Get user preference profile
     */
    public function getUserProfile($userId) {
        if (!$this->db) return null;
        
        try {
            $profileCollection = $this->db->user_preference_profiles;
            return $profileCollection->findOne(['user_id' => $userId]);
        } catch (Exception $e) {
            error_log("Error getting user profile: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user interactions history
     */
    public function getUserInteractions($userId, $limit = 100, $itemType = null) {
        if (!$this->db) return [];
        
        try {
            $query = ['user_id' => $userId];
            if ($itemType) {
                $query['item_type'] = $itemType;
            }
            
            $cursor = $this->collection->find(
                $query,
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit
                ]
            );
            
            return iterator_to_array($cursor);
        } catch (Exception $e) {
            error_log("Error getting user interactions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get similar users based on preferences
     */
    public function getSimilarUsers($userId, $limit = 10) {
        if (!$this->db) return [];
        
        try {
            $userProfile = $this->getUserProfile($userId);
            if (!$userProfile) return [];
            
            $profileCollection = $this->db->user_preference_profiles;
            
            // Find users with similar interests
            $pipeline = [
                [
                    '$match' => [
                        'user_id' => ['$ne' => $userId],
                        'interests' => ['$exists' => true]
                    ]
                ],
                [
                    '$addFields' => [
                        'similarity_score' => [
                            '$let' => [
                                'vars' => [
                                    'userInterests' => $userProfile['interests'] ?? []
                                ],
                                'in' => [
                                    '$size' => [
                                        '$setIntersection' => [
                                            ['$objectToArray' => '$interests'],
                                            ['$objectToArray' => '$$userInterests']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    '$match' => [
                        'similarity_score' => ['$gt' => 0]
                    ]
                ],
                [
                    '$sort' => ['similarity_score' => -1]
                ],
                [
                    '$limit' => $limit
                ]
            ];
            
            return iterator_to_array($profileCollection->aggregate($pipeline));
        } catch (Exception $e) {
            error_log("Error getting similar users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Track search query
     */
    public function trackSearch($userId, $query, $results_count = 0, $category = null) {
        $metadata = [
            'query' => $query,
            'results_count' => $results_count,
            'category' => $category,
            'keywords' => explode(' ', strtolower($query))
        ];
        
        return $this->trackInteraction($userId, 'search', $query, 'search', $metadata);
    }
    
    /**
     * Get popular items based on all user interactions
     */
    public function getPopularItems($itemType, $limit = 10, $timeframe = 'week') {
        if (!$this->db) return [];
        
        try {
            $timeFilter = $this->getTimeFrameFilter($timeframe);
            
            $pipeline = [
                [
                    '$match' => [
                        'item_type' => $itemType,
                        'timestamp' => $timeFilter
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$item_id',
                        'interaction_count' => ['$sum' => 1],
                        'unique_users' => ['$addToSet' => '$user_id'],
                        'last_interaction' => ['$max' => '$timestamp']
                    ]
                ],
                [
                    '$addFields' => [
                        'unique_user_count' => ['$size' => '$unique_users'],
                        'popularity_score' => [
                            '$add' => [
                                '$interaction_count',
                                ['$multiply' => [['$size' => '$unique_users'], 2]]
                            ]
                        ]
                    ]
                ],
                [
                    '$sort' => ['popularity_score' => -1]
                ],
                [
                    '$limit' => $limit
                ]
            ];
            
            return iterator_to_array($this->collection->aggregate($pipeline));
        } catch (Exception $e) {
            error_log("Error getting popular items: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get time frame filter for MongoDB queries
     */
    private function getTimeFrameFilter($timeframe) {
        $now = new DateTime();
        
        switch ($timeframe) {
            case 'day':
                $since = $now->sub(new DateInterval('P1D'));
                break;
            case 'week':
                $since = $now->sub(new DateInterval('P1W'));
                break;
            case 'month':
                $since = $now->sub(new DateInterval('P1M'));
                break;
            case 'year':
                $since = $now->sub(new DateInterval('P1Y'));
                break;
            default:
                $since = $now->sub(new DateInterval('P1W'));
        }
        
        return ['$gte' => new MongoDB\BSON\UTCDateTime($since->getTimestamp() * 1000)];
    }
    
    /**
     * Clean old tracking data (optional maintenance function)
     */
    public function cleanOldData($days = 90) {
        if (!$this->db) return false;
        
        try {
            $cutoffDate = new DateTime();
            $cutoffDate->sub(new DateInterval("P{$days}D"));
            
            $result = $this->collection->deleteMany([
                'timestamp' => [
                    '$lt' => new MongoDB\BSON\UTCDateTime($cutoffDate->getTimestamp() * 1000)
                ]
            ]);
            
            return $result->getDeletedCount();
        } catch (Exception $e) {
            error_log("Error cleaning old data: " . $e->getMessage());
            return false;
        }
    }
}

// API endpoint handling
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    
    // Debug logging
    error_log("DEBUG: API endpoint called with action: " . ($_POST['action'] ?? 'none'));
    error_log("DEBUG: POST data: " . json_encode($_POST));
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $error = ['error' => 'User not authenticated'];
        error_log("DEBUG: Authentication failed - no user_id in session");
        echo json_encode($error);
        exit;
    }
    
    $preferences = new UserPreferences();
    $userId = $_SESSION['user_id'];
    
    error_log("DEBUG: Processing request for user: $userId");
    
    switch ($_POST['action']) {
        case 'track':
            // Parse and validate metadata
            $rawMetadata = $_POST['metadata'] ?? '{}';
            $metadata = json_decode($rawMetadata, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("DEBUG: JSON decode error: " . json_last_error_msg());
                error_log("DEBUG: Raw metadata: " . $rawMetadata);
                $metadata = [];
            }
            
            error_log("DEBUG: Tracking interaction with metadata: " . json_encode($metadata));
            
            $result = $preferences->trackInteraction(
                $userId,
                $_POST['interaction_type'] ?? 'view',
                $_POST['item_id'] ?? '',
                $_POST['item_type'] ?? '',
                $metadata
            );
            
            $response = ['success' => $result];
            if (!$result) {
                $response['error'] = 'Failed to track interaction';
                error_log("DEBUG: Failed to track interaction");
            } else {
                error_log("DEBUG: Successfully tracked interaction");
            }
            
            echo json_encode($response);
            break;
            
        case 'track_search':
            $result = $preferences->trackSearch(
                $userId,
                $_POST['query'] ?? '',
                intval($_POST['results_count'] ?? 0),
                $_POST['category'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'get_profile':
            $profile = $preferences->getUserProfile($userId);
            error_log("DEBUG: Returning user profile: " . json_encode($profile));
            echo json_encode($profile ?? []);
            break;
            
        default:
            http_response_code(400);
            $error = ['error' => 'Invalid action: ' . ($_POST['action'] ?? 'none')];
            error_log("DEBUG: Invalid action received");
            echo json_encode($error);
    }
} else if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("DEBUG: POST request received but no action parameter");
    error_log("DEBUG: Available POST keys: " . implode(', ', array_keys($_POST)));
}
?> 