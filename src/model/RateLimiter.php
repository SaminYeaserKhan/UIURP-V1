<?php
/**
 * Rate Limiter
 * 
 * Prevents upload spam and DoS attacks by limiting
 * the number of uploads per user per time period
 */

namespace UIURP\Model;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class RateLimiter {
    
    private $db;
    
    // Rate limits
    const MAX_UPLOADS_PER_HOUR = 50;
    const MAX_UPLOADS_PER_DAY = 200;
    const MAX_TOTAL_SIZE_PER_DAY = 524288000; // 500MB per day
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check if user can upload (hasn't exceeded rate limits)
     * 
     * @param string $userId User ID
     * @param int $fileSize File size in bytes
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function checkUploadAllowed($userId, $fileSize = 0) {
        $response = [
            'allowed' => true,
            'message' => ''
        ];
        
        try {
            $now = time();
            $oneHourAgo = new UTCDateTime(($now - 3600) * 1000);
            $oneDayAgo = new UTCDateTime(($now - 86400) * 1000);
            
            // Check hourly limit
            $uploadsLastHour = $this->db->upload_rate_log->countDocuments([
                'userId' => new ObjectId($userId),
                'timestamp' => ['$gte' => $oneHourAgo]
            ]);
            
            if ($uploadsLastHour >= self::MAX_UPLOADS_PER_HOUR) {
                $response['allowed'] = false;
                $response['message'] = 'Upload limit exceeded. Maximum ' . self::MAX_UPLOADS_PER_HOUR . ' uploads per hour. Please try again later.';
                return $response;
            }
            
            // Check daily limit
            $uploadsLastDay = $this->db->upload_rate_log->countDocuments([
                'userId' => new ObjectId($userId),
                'timestamp' => ['$gte' => $oneDayAgo]
            ]);
            
            if ($uploadsLastDay >= self::MAX_UPLOADS_PER_DAY) {
                $response['allowed'] = false;
                $response['message'] = 'Daily upload limit exceeded. Maximum ' . self::MAX_UPLOADS_PER_DAY . ' uploads per day. Please try again tomorrow.';
                return $response;
            }
            
            // Check total size limit per day
            $uploadStats = $this->db->upload_rate_log->aggregate([
                [
                    '$match' => [
                        'userId' => new ObjectId($userId),
                        'timestamp' => ['$gte' => $oneDayAgo]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'totalSize' => ['$sum' => '$fileSize']
                    ]
                ]
            ])->toArray();
            
            $totalSizeToday = isset($uploadStats[0]) ? $uploadStats[0]['totalSize'] : 0;
            
            if (($totalSizeToday + $fileSize) > self::MAX_TOTAL_SIZE_PER_DAY) {
                $response['allowed'] = false;
                $maxSizeMB = round(self::MAX_TOTAL_SIZE_PER_DAY / (1024 * 1024), 2);
                $usedMB = round($totalSizeToday / (1024 * 1024), 2);
                $response['message'] = "Daily upload size limit exceeded. You've uploaded {$usedMB}MB today. Maximum {$maxSizeMB}MB per day.";
                return $response;
            }
            
        } catch (\Exception $e) {
            error_log('Rate limiter error: ' . $e->getMessage());
            // If rate limiter fails, allow upload (fail open for better UX)
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Log an upload for rate limiting
     * 
     * @param string $userId User ID
     * @param int $fileSize File size in bytes
     * @param string $fileName File name
     */
    public function logUpload($userId, $fileSize, $fileName) {
        try {
            $this->db->upload_rate_log->insertOne([
                'userId' => new ObjectId($userId),
                'fileSize' => $fileSize,
                'fileName' => $fileName,
                'timestamp' => new UTCDateTime(),
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            error_log('Failed to log upload for rate limiting: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user's upload statistics
     * 
     * @param string $userId User ID
     * @return array Statistics
     */
    public function getUserStats($userId) {
        try {
            $now = time();
            $oneHourAgo = new UTCDateTime(($now - 3600) * 1000);
            $oneDayAgo = new UTCDateTime(($now - 86400) * 1000);
            
            $uploadsLastHour = $this->db->upload_rate_log->countDocuments([
                'userId' => new ObjectId($userId),
                'timestamp' => ['$gte' => $oneHourAgo]
            ]);
            
            $uploadsLastDay = $this->db->upload_rate_log->countDocuments([
                'userId' => new ObjectId($userId),
                'timestamp' => ['$gte' => $oneDayAgo]
            ]);
            
            $uploadStats = $this->db->upload_rate_log->aggregate([
                [
                    '$match' => [
                        'userId' => new ObjectId($userId),
                        'timestamp' => ['$gte' => $oneDayAgo]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'totalSize' => ['$sum' => '$fileSize']
                    ]
                ]
            ])->toArray();
            
            $totalSizeToday = isset($uploadStats[0]) ? $uploadStats[0]['totalSize'] : 0;
            
            return [
                'uploadsLastHour' => $uploadsLastHour,
                'uploadsLastDay' => $uploadsLastDay,
                'totalSizeToday' => $totalSizeToday,
                'remainingUploadsHour' => max(0, self::MAX_UPLOADS_PER_HOUR - $uploadsLastHour),
                'remainingUploadsDay' => max(0, self::MAX_UPLOADS_PER_DAY - $uploadsLastDay),
                'remainingSizeToday' => max(0, self::MAX_TOTAL_SIZE_PER_DAY - $totalSizeToday)
            ];
        } catch (\Exception $e) {
            error_log('Failed to get user stats: ' . $e->getMessage());
            return [
                'uploadsLastHour' => 0,
                'uploadsLastDay' => 0,
                'totalSizeToday' => 0,
                'remainingUploadsHour' => self::MAX_UPLOADS_PER_HOUR,
                'remainingUploadsDay' => self::MAX_UPLOADS_PER_DAY,
                'remainingSizeToday' => self::MAX_TOTAL_SIZE_PER_DAY
            ];
        }
    }
}
