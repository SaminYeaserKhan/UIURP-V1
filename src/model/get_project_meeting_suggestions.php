<?php
// Suppress PHP warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);

// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

// Helper functions (copied from debug_schedules.php)
function timeToMinutes($time) {
    $parts = explode(':', $time);
    return intval($parts[0]) * 60 + intval($parts[1]);
}

function minutesToTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

function timeRangesOverlap($start1, $end1, $start2, $end2) {
    $start1Min = timeToMinutes($start1);
    $end1Min = timeToMinutes($end1);
    $start2Min = timeToMinutes($start2);
    $end2Min = timeToMinutes($end2);
    
    return ($start1Min < $end2Min) && ($start2Min < $end1Min);
}

function findFreeTimeSlots($teamSchedules, $day) {
    $workingHours = [
        'start' => timeToMinutes('08:00'),
        'end' => timeToMinutes('23:30')  // Extended to 11:30 PM for late evening meetings
    ];
    
    $busySlots = [];
    
    // Collect all busy time slots for the specific day
    foreach ($teamSchedules as $memberName => $schedules) {
        foreach ($schedules as $schedule) {
            if ($schedule['day'] === $day) {
                $busySlots[] = [
                    'start' => timeToMinutes($schedule['startTime']),
                    'end' => timeToMinutes($schedule['endTime']),
                    'member' => $memberName,
                    'title' => $schedule['title']
                ];
            }
        }
    }
    
    // Sort busy slots by start time
    usort($busySlots, function($a, $b) {
        return $a['start'] - $b['start'];
    });
    
    // Find free slots
    $freeSlots = [];
    $currentTime = $workingHours['start'];
    
    foreach ($busySlots as $busySlot) {
        if ($currentTime < $busySlot['start']) {
            // There's a free slot before this busy period
            $duration = $busySlot['start'] - $currentTime;
            if ($duration >= 30) { // At least 30 minutes free
                $freeSlots[] = [
                    'start' => minutesToTime($currentTime),
                    'end' => minutesToTime($busySlot['start']),
                    'duration' => $duration,
                    'conflicts' => []
                ];
            }
        }
        $currentTime = max($currentTime, $busySlot['end']);
    }
    
    // Check for free time after the last busy slot
    if ($currentTime < $workingHours['end']) {
        $duration = $workingHours['end'] - $currentTime;
        if ($duration >= 30) { // At least 30 minutes free
            $freeSlots[] = [
                'start' => minutesToTime($currentTime),
                'end' => minutesToTime($workingHours['end']),
                'duration' => $duration,
                'conflicts' => []
            ];
        }
    }
    
    return $freeSlots;
}

function getDayOfWeek($dateString) {
    $date = new DateTime($dateString);
    $dayNum = $date->format('w'); // 0 = Sunday, 1 = Monday, etc.
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return $days[$dayNum];
}

try {
    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Only GET method allowed');
    }
    
    $projectId = isset($_GET['projectId']) ? $_GET['projectId'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    
    if (!$projectId) {
        throw new Exception('Project ID is required');
    }
    
    if (!$date) {
        throw new Exception('Date is required');
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format. Expected YYYY-MM-DD');
    }
    
    // Get project details
    $projectsCollection = $db->projectsV2;
    $project = $projectsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($projectId)]);
    
    if (!$project) {
        // Try old projects collection
        $projectsCollection = $db->projects;
        $project = $projectsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($projectId)]);
    }
    
    if (!$project) {
        throw new Exception('Project not found');
    }
    
    $teamSchedules = [];
    $teamMembers = [];
    $scheduleCollection = $db->schedule;
    
    // Collect schedules for all team members
    if (isset($project['members']) && (is_array($project['members']) || $project['members'] instanceof \Traversable)) {
        foreach ($project['members'] as $index => $member) {
            $memberName = $member['name'] ?? "Member " . ($index + 1);
            $teamMembers[] = [
                'name' => $memberName,
                'role' => $member['role'] ?? 'Member',
                'type' => 'student'
            ];
            
            if (isset($member['userId'])) {
                $userId = $member['userId'];
                
                // Handle different userId formats
                if (is_object($userId)) {
                    if ($userId instanceof MongoDB\BSON\ObjectId) {
                        // Already an ObjectId
                        $userObjectId = $userId;
                    } elseif ($userId instanceof MongoDB\Model\BSONDocument) {
                        // BSON Document - extract the ObjectId
                        if (isset($userId['$oid'])) {
                            $userObjectId = new MongoDB\BSON\ObjectId($userId['$oid']);
                        } else {
                            // Try to find ObjectId in the document
                            foreach ($userId as $key => $value) {
                                if ($value instanceof MongoDB\BSON\ObjectId) {
                                    $userObjectId = $value;
                                    break;
                                }
                            }
                            if (!isset($userObjectId)) {
                                // Try converting the BSON document to array and check for $oid
                                $userIdArray = $userId->toArray();
                                if (isset($userIdArray['$oid'])) {
                                    $userObjectId = new MongoDB\BSON\ObjectId($userIdArray['$oid']);
                                } else {
                                    throw new Exception("Could not extract ObjectId from BSON document for member: " . $memberName);
                                }
                            }
                        }
                    } elseif (isset($userId->oid)) {
                        $userObjectId = new MongoDB\BSON\ObjectId($userId->oid);
                    } else {
                        throw new Exception("Unsupported userId object type: " . get_class($userId) . " for member: " . $memberName);
                    }
                } elseif (is_array($userId) && isset($userId['$oid'])) {
                    $userObjectId = new MongoDB\BSON\ObjectId($userId['$oid']);
                } else {
                    // Assume it's a string
                    $userObjectId = new MongoDB\BSON\ObjectId($userId);
                }
                
                try {
                    $schedules = $scheduleCollection->find(['userId' => $userObjectId])->toArray();
                    
                    $teamSchedules[$memberName] = [];
                    
                    foreach ($schedules as $schedule) {
                        $teamSchedules[$memberName][] = [
                            'title' => $schedule['title'] ?? 'N/A',
                            'day' => $schedule['day'] ?? 'N/A',
                            'startTime' => $schedule['startTime'] ?? 'N/A',
                            'endTime' => $schedule['endTime'] ?? 'N/A',
                            'type' => $schedule['type'] ?? 'N/A'
                        ];
                    }
                } catch (Exception $e) {
                    // Invalid ObjectId, skip this member
                    error_log("Error fetching schedule for member $memberName: " . $e->getMessage());
                    $teamSchedules[$memberName] = [];
                }
            } else {
                $teamSchedules[$memberName] = [];
            }
        }
    }
    
    // Collect supervisor schedule
    if (isset($project['supervisor'])) {
        $supervisorName = $project['supervisor']['name'] ?? 'Supervisor';
        $teamMembers[] = [
            'name' => $supervisorName,
            'role' => 'Supervisor',
            'type' => 'supervisor'
        ];
        
        if (isset($project['supervisor']['userId'])) {
            $userId = $project['supervisor']['userId'];
            
            // Handle different userId formats  
            if (is_object($userId)) {
                if ($userId instanceof MongoDB\BSON\ObjectId) {
                    // Already an ObjectId
                    $userObjectId = $userId;
                } elseif ($userId instanceof MongoDB\Model\BSONDocument) {
                    // BSON Document - extract the ObjectId
                    if (isset($userId['$oid'])) {
                        $userObjectId = new MongoDB\BSON\ObjectId($userId['$oid']);
                    } else {
                        // Try to find ObjectId in the document
                        foreach ($userId as $key => $value) {
                            if ($value instanceof MongoDB\BSON\ObjectId) {
                                $userObjectId = $value;
                                break;
                            }
                        }
                        if (!isset($userObjectId)) {
                            // Try converting the BSON document to array and check for $oid
                            $userIdArray = $userId->toArray();
                            if (isset($userIdArray['$oid'])) {
                                $userObjectId = new MongoDB\BSON\ObjectId($userIdArray['$oid']);
                            } else {
                                throw new Exception("Could not extract ObjectId from supervisor BSON document");
                            }
                        }
                    }
                } elseif (isset($userId->oid)) {
                    $userObjectId = new MongoDB\BSON\ObjectId($userId->oid);
                } else {
                    throw new Exception("Unsupported supervisor userId object type: " . get_class($userId));
                }
            } elseif (is_array($userId) && isset($userId['$oid'])) {
                $userObjectId = new MongoDB\BSON\ObjectId($userId['$oid']);
            } else {
                // Assume it's a string
                $userObjectId = new MongoDB\BSON\ObjectId($userId);
            }
            
            try {
                $schedules = $scheduleCollection->find(['userId' => $userObjectId])->toArray();
                
                $teamSchedules[$supervisorName] = [];
                
                foreach ($schedules as $schedule) {
                    $teamSchedules[$supervisorName][] = [
                        'title' => $schedule['title'] ?? 'N/A',
                        'day' => $schedule['day'] ?? 'N/A',
                        'startTime' => $schedule['startTime'] ?? 'N/A',
                        'endTime' => $schedule['endTime'] ?? 'N/A',
                        'type' => $schedule['type'] ?? 'N/A'
                    ];
                }
            } catch (Exception $e) {
                // Invalid ObjectId, skip supervisor
                error_log("Error fetching schedule for supervisor $supervisorName: " . $e->getMessage());
                $teamSchedules[$supervisorName] = [];
            }
        } else {
            $teamSchedules[$supervisorName] = [];
        }
    }
    
    // Get day of week for the requested date
    $dayOfWeek = getDayOfWeek($date);
    
    // Find free time slots for the specific date
    $freeSlots = findFreeTimeSlots($teamSchedules, $dayOfWeek);
    
    // Debug: Log the free slots found
    error_log("Debug: Free slots found for $dayOfWeek:");
    foreach ($freeSlots as $i => $slot) {
        error_log("  Slot $i: {$slot['start']} - {$slot['end']} (duration: {$slot['duration']} min)");
    }
    
    // Get conflicts for each member for this day
    $memberConflicts = [];
    foreach ($teamSchedules as $memberName => $schedules) {
        $memberConflicts[$memberName] = [];
        foreach ($schedules as $schedule) {
            if ($schedule['day'] === $dayOfWeek) {
                $memberConflicts[$memberName][] = [
                    'title' => $schedule['title'],
                    'startTime' => $schedule['startTime'],
                    'endTime' => $schedule['endTime'],
                    'type' => $schedule['type']
                ];
            }
        }
    }
    
    // Prepare suggestions with better time distribution
    $suggestions = [];
    $durations = [30, 45, 60, 90, 120, 180]; // 30min, 45min, 1h, 1.5h, 2h, 3h
    
    // Sort free slots by start time (earliest first) to ensure time distribution
    usort($freeSlots, function($a, $b) {
        return timeToMinutes($a['start']) - timeToMinutes($b['start']);
    });
    
    // Strategy 1: Target specific popular meeting times throughout the day
    $targetTimes = [
        timeToMinutes('08:00'), // 8 AM
        timeToMinutes('09:00'), // 9 AM
        timeToMinutes('10:00'), // 10 AM
        timeToMinutes('11:00'), // 11 AM
        timeToMinutes('12:00'), // 12 PM (Noon)
        timeToMinutes('13:00'), // 1 PM
        timeToMinutes('14:00'), // 2 PM
        timeToMinutes('15:00'), // 3 PM
        timeToMinutes('16:00'), // 4 PM
        timeToMinutes('17:00'), // 5 PM
        timeToMinutes('18:00'), // 6 PM
        timeToMinutes('19:00'), // 7 PM
        timeToMinutes('20:00'), // 8 PM
        timeToMinutes('21:00'), // 9 PM
        timeToMinutes('22:00'), // 10 PM
        timeToMinutes('22:30'), // 10:30 PM
    ];
    
         $targetSuggestionsCount = 0;
     foreach ($targetTimes as $targetMinutes) {
         $targetTimeStr = minutesToTime($targetMinutes);
         foreach ($durations as $meetingDuration) {
             $targetEndMinutes = $targetMinutes + $meetingDuration;
             $targetEndStr = minutesToTime($targetEndMinutes);
             
             // Check if this target time fits in any free slot
             $foundSlot = false;
             foreach ($freeSlots as $slot) {
                 $slotStartMinutes = timeToMinutes($slot['start']);
                 $slotEndMinutes = timeToMinutes($slot['end']);
                 
                 if ($targetMinutes >= $slotStartMinutes && $targetEndMinutes <= $slotEndMinutes) {
                     $suggestions[] = [
                         'startTime' => minutesToTime($targetMinutes),
                         'endTime' => minutesToTime($targetEndMinutes),
                         'duration' => $meetingDuration,
                         'availableDuration' => $slot['duration'],
                         'durationText' => ($meetingDuration >= 60 ? floor($meetingDuration / 60) . 'h ' : '') . 
                                        ($meetingDuration % 60 > 0 ? ($meetingDuration % 60) . 'min' : ''),
                         'quality' => $meetingDuration <= 45 ? 'short' : ($meetingDuration <= 120 ? 'medium' : 'long'),
                         'slotStart' => $slot['start'],
                         'slotEnd' => $slot['end']
                     ];
                     $targetSuggestionsCount++;
                     $foundSlot = true;
                     error_log("  ✓ Target $targetTimeStr-$targetEndStr fits in slot {$slot['start']}-{$slot['end']}");
                     break; // Found a slot for this time, move to next duration
                 }
             }
             if (!$foundSlot) {
                 error_log("  ✗ Target $targetTimeStr-$targetEndStr doesn't fit in any free slot");
                 if ($targetMinutes >= timeToMinutes('22:00')) {
                     error_log("    Late evening slot check: target=$targetMinutes, duration=$meetingDuration, end=$targetEndMinutes");
                     foreach ($freeSlots as $slot) {
                         $slotStartMin = timeToMinutes($slot['start']);
                         $slotEndMin = timeToMinutes($slot['end']);
                         error_log("    Slot: {$slot['start']}-{$slot['end']} ($slotStartMin-$slotEndMin)");
                         error_log("    Check: target $targetMinutes >= $slotStartMin? " . ($targetMinutes >= $slotStartMin ? 'YES' : 'NO'));
                         error_log("    Check: end $targetEndMinutes <= $slotEndMin? " . ($targetEndMinutes <= $slotEndMin ? 'YES' : 'NO'));
                     }
                 }
             }
         }
     }
          error_log("Debug: Strategy 1 generated $targetSuggestionsCount targeted suggestions");
     
     // Strategy 1.5: Special focus on late evening with shorter durations
     $lateEveningTimes = [timeToMinutes('21:30'), timeToMinutes('22:00'), timeToMinutes('22:15'), timeToMinutes('22:30')];
     $shortDurations = [30, 45, 60]; // Only shorter meetings for late evening
     
     foreach ($lateEveningTimes as $lateTime) {
         foreach ($shortDurations as $duration) {
             $endTime = $lateTime + $duration;
             foreach ($freeSlots as $slot) {
                 $slotStart = timeToMinutes($slot['start']);
                 $slotEnd = timeToMinutes($slot['end']);
                 
                 if ($lateTime >= $slotStart && $endTime <= $slotEnd) {
                     $suggestions[] = [
                         'startTime' => minutesToTime($lateTime),
                         'endTime' => minutesToTime($endTime),
                         'duration' => $duration,
                         'availableDuration' => $slot['duration'],
                         'durationText' => ($duration >= 60 ? floor($duration / 60) . 'h ' : '') . 
                                        ($duration % 60 > 0 ? ($duration % 60) . 'min' : ''),
                         'quality' => 'short',
                         'slotStart' => $slot['start'],
                         'slotEnd' => $slot['end']
                     ];
                     error_log("  ✓ Late evening: " . minutesToTime($lateTime) . "-" . minutesToTime($endTime) . " fits in {$slot['start']}-{$slot['end']}");
                     break;
                 }
             }
         }
     }
     
     // Strategy 2: Generate suggestions distributed throughout the day (fallback)
    foreach ($freeSlots as $slot) {
        $slotStartMinutes = timeToMinutes($slot['start']);
        $slotDuration = $slot['duration'];
        
        // For each slot, generate suggestions at different start times within the slot
        // This ensures we get suggestions throughout the day, not just at the beginning of big slots
        
        foreach ($durations as $meetingDuration) {
            if ($slotDuration >= $meetingDuration) {
                // Generate suggestions at multiple start times within this slot
                $maxPossibleStarts = max(1, floor(($slotDuration - $meetingDuration) / 60) + 1); // Every hour
                $maxStarts = min($maxPossibleStarts, 12); // Increased to 12 start times per slot per duration for wider coverage
                
                for ($i = 0; $i < $maxStarts; $i++) {
                    $startOffset = $i * 180; // 3 hours apart for even wider distribution
                    if ($startOffset + $meetingDuration <= $slotDuration) {
                        $suggestionStartMinutes = $slotStartMinutes + $startOffset;
                        $suggestionEndMinutes = $suggestionStartMinutes + $meetingDuration;
                        
                        $suggestions[] = [
                            'startTime' => minutesToTime($suggestionStartMinutes),
                            'endTime' => minutesToTime($suggestionEndMinutes),
                            'duration' => $meetingDuration,
                            'availableDuration' => $slotDuration,
                            'durationText' => ($meetingDuration >= 60 ? floor($meetingDuration / 60) . 'h ' : '') . 
                                           ($meetingDuration % 60 > 0 ? ($meetingDuration % 60) . 'min' : ''),
                            'quality' => $meetingDuration <= 45 ? 'short' : ($meetingDuration <= 120 ? 'medium' : 'long'),
                            'slotStart' => $slot['start'], // For debugging
                            'slotEnd' => $slot['end']
                        ];
                    }
                }
            }
        }
    }
    
    // Remove duplicates and sort by start time
    $uniqueSuggestions = [];
    foreach ($suggestions as $suggestion) {
        $key = $suggestion['startTime'] . '_' . $suggestion['duration'];
        if (!isset($uniqueSuggestions[$key])) {
            $uniqueSuggestions[$key] = $suggestion;
        }
    }
    
    $suggestions = array_values($uniqueSuggestions);
    
    // Sort by start time to show suggestions in chronological order
    usort($suggestions, function($a, $b) {
        return timeToMinutes($a['startTime']) - timeToMinutes($b['startTime']);
    });
    
    // Ensure balanced AM/PM distribution instead of just taking first 20
    $totalBeforeLimit = count($suggestions);
    
    // Separate AM and PM suggestions
    $amSuggestions = [];
    $pmSuggestions = [];
    
    foreach ($suggestions as $suggestion) {
        $startHour = (int)substr($suggestion['startTime'], 0, 2);
        if ($startHour < 12) {
            $amSuggestions[] = $suggestion;
        } else {
            $pmSuggestions[] = $suggestion;
        }
    }
    
    // Take up to 8 AM and 17 PM suggestions to show more evening options
    $finalSuggestions = [];
    $finalSuggestions = array_merge(
        array_slice($amSuggestions, 0, 8),   // First 8 AM suggestions
        array_slice($pmSuggestions, 0, 17)   // First 17 PM suggestions (more evening focus)
    );
    
    // If we don't have enough PM suggestions, fill with AM
    if (count($finalSuggestions) < 25) {
        $remaining = 25 - count($finalSuggestions);
        if (count($amSuggestions) > 8) {
            $finalSuggestions = array_merge($finalSuggestions, array_slice($amSuggestions, 8, $remaining));
        }
    }
    
    // Limit to final count
    $finalSuggestions = array_slice($finalSuggestions, 0, 25);
    
    // Sort the final suggestions by start time again
    usort($finalSuggestions, function($a, $b) {
        return timeToMinutes($a['startTime']) - timeToMinutes($b['startTime']);
    });
    
    $suggestions = $finalSuggestions;
    error_log("Debug: Total suggestions before limit: $totalBeforeLimit, after limit: " . count($suggestions));
    error_log("Debug: AM suggestions available: " . count($amSuggestions) . ", PM suggestions available: " . count($pmSuggestions));
    
    // Count late evening suggestions (22:00 and later)
    $lateEveningSuggestions = array_filter($suggestions, function($s) {
        return timeToMinutes($s['startTime']) >= timeToMinutes('22:00');
    });
    error_log("Debug: Late evening suggestions (22:00+): " . count($lateEveningSuggestions));
    if (count($lateEveningSuggestions) > 0) {
        foreach ($lateEveningSuggestions as $s) {
            error_log("  - {$s['startTime']} - {$s['endTime']}");
        }
    }
    
    echo json_encode([
        'success' => true,
        'project' => [
            'title' => $project['title'] ?? 'Unknown Project',
            'id' => $projectId
        ],
        'date' => $date,
        'dayOfWeek' => $dayOfWeek,
        'teamMembers' => $teamMembers,
        'suggestions' => $suggestions,
        'memberConflicts' => $memberConflicts,
        'teamSchedules' => $teamSchedules,
        'debug' => [
            'freeSlots' => $freeSlots,
            'totalSuggestionsGenerated' => count($suggestions),
            'workingHours' => ['start' => '08:00', 'end' => '23:30'],
            'targetSuggestionsCount' => $targetSuggestionsCount ?? 0,
            'totalBeforeLimit' => $totalBeforeLimit ?? 0,
            'amAvailable' => count($amSuggestions ?? []),
            'pmAvailable' => count($pmSuggestions ?? [])
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 