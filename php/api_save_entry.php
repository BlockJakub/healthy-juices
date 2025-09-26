<?php

/**
 * File: api_save_entry.php
 * Author: Healthy Blog Team
 * Created: 2025-09-25
 * Description: Validates and persists a daily health entry; computes risk & health score.
 * Notes: Performs input bounding, CSRF validation, and upsert on user_entries.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
secure_session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauth']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
if (!$raw || strlen($raw) > 20000) {
    http_response_code(400);
    echo json_encode(['error' => 'payload_too_large_or_empty']);
    exit;
}
try {
    $data = json_decode($raw, true) ?: [];
    $csrf = $data['csrf'] ?? '';
    if (!validate_csrf_token($csrf)) {
        http_response_code(403);
        echo json_encode(['error' => 'csrf']);
        exit;
    }

    // helper validators
    $is_valid_date = function ($d) {
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    };
    $clean_str = function ($s, $max = 200) {
        $s = trim((string)$s);
        if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
        return $s;
    };

    // expected fields and sanitization
    // Allow saving historical dates (not future). If invalid or future, fallback to today.
    $incomingDate = (isset($data['date']) && $is_valid_date($data['date'])) ? $data['date'] : date('Y-m-d');
    $today = date('Y-m-d');
    $date = ($incomingDate <= $today) ? $incomingDate : $today;
    $water = isset($data['water']) ? floatval($data['water']) : 0.0;
    if ($water < 0) $water = 0.0;
    if ($water > 24) $water = 24.0;
    $sleep = isset($data['sleep']) ? floatval($data['sleep']) : 0.0;
    if ($sleep < 0) $sleep = 0.0;
    if ($sleep > 24) $sleep = 24.0;
    $steps = isset($data['steps']) ? intval($data['steps']) : 0;
    if ($steps < 0) $steps = 0;
    if ($steps > 300000) $steps = 300000;
    $meals = isset($data['meals']) ? intval($data['meals']) : 0;
    if ($meals < 0) $meals = 0;
    if ($meals > 4) $meals = 4;

    $allowed_patterns = ['never', 'every_day', 'when_possible', 'occasionally', 'one_per_year'];
    $smokingPattern = isset($data['smokingPattern']) && in_array($data['smokingPattern'], $allowed_patterns, true) ? $data['smokingPattern'] : 'never';
    $smoked24 = (isset($data['smoked24']) && $data['smoked24'] === 'yes') ? 'yes' : 'no';
    $cigarettes = isset($data['cigarettes']) ? intval($data['cigarettes']) : 0;
    if ($cigarettes < 0) $cigarettes = 0;
    if ($cigarettes > 500) $cigarettes = 500;
    $feelWeak = (isset($data['feelWeak']) && $data['feelWeak'] === 'yes') ? 'yes' : 'no';
    $alcoholUnits = isset($data['alcoholUnits']) ? floatval($data['alcoholUnits']) : 0.0;
    if ($alcoholUnits < 0) $alcoholUnits = 0.0;
    if ($alcoholUnits > 100) $alcoholUnits = 100.0;
    $drugsYes = (isset($data['drugsYes']) && $data['drugsYes'] === 'yes') ? 'yes' : 'no';
    $drugType = isset($data['drugType']) ? $clean_str($data['drugType'], 100) : '';
    // Extended lifestyle fields
    $nutritionKcal = isset($data['nutritionKcal']) ? max(0, min(20000, (int)$data['nutritionKcal'])) : 0;
    $vitamins = [];
    if (!empty($data['vitamins']) && is_array($data['vitamins'])) {
        foreach ($data['vitamins'] as $v) {
            $v = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$v));
            if ($v !== '' && strlen($v) <= 6) $vitamins[] = $v;
            if (count($vitamins) >= 12) break;
        }
    }
    $trainingType = isset($data['trainingType']) ? $clean_str($data['trainingType'], 40) : 'none';
    $trainingMinutes = isset($data['trainingMinutes']) ? max(0, min(1440, (int)$data['trainingMinutes'])) : 0;
    $breathingType = isset($data['breathingType']) ? $clean_str($data['breathingType'], 40) : 'none';
    $breathingMinutes = isset($data['breathingMinutes']) ? max(0, min(600, (int)$data['breathingMinutes'])) : 0;
    $coldMethod = isset($data['coldMethod']) ? $clean_str($data['coldMethod'], 40) : 'none';
    $coldMinutes = isset($data['coldMinutes']) ? max(0, min(300, (int)$data['coldMinutes'])) : 0;
    $juiceType = isset($data['juiceType']) ? $clean_str($data['juiceType'], 40) : 'none';
    $juiceFrequency = isset($data['juiceFrequency']) ? $clean_str($data['juiceFrequency'], 40) : 'none';
    $sleepQuality = isset($data['sleepQuality']) ? $clean_str($data['sleepQuality'], 20) : 'undisturbed';
    $consentTimestamp = isset($data['consentTimestamp']) ? $clean_str($data['consentTimestamp'], 50) : null;

    // compute risk and healthScore server-side (same heuristics as client)
    $risk = 0.0;
    $risk += min($cigarettes / 10, 10);
    $risk += min($alcoholUnits / 5, 6);
    if ($drugsYes === 'yes') $risk += 8;
    if ($sleep < 6) $risk += 4;
    if ($water < 1.0) $risk += 3;
    if ($steps < 3000) $risk += 3;
    if ($feelWeak === 'yes') $risk += 2;

    // New health score model (0-100) grounded in requested categorical scale
    // Components (max weights): Hydration 20, Sleep 20, Steps 20, Meals 10, Negative Factors 30
    $healthScore = 0;
    // Hydration: 0-4L mapped to 0-20 (cap at 4L beneficial threshold)
    $healthScore += min($water / 4.0, 1) * 20;
    // Sleep: 7-8h optimal -> 20, linear penalty outside 5-9 range
    $sleepScore = 0;
    if ($sleep > 0) {
        // ideal window 7-8
        if ($sleep >= 7 && $sleep <= 8) $sleepScore = 20;
        else {
            // degrade linearly down to 0 at 4h or 12h
            if ($sleep < 7) {
                $sleepScore = max(0, 20 * (($sleep - 4) / (7 - 4))); // 4->0,7->20
            } else { // >8
                $sleepScore = max(0, 20 * ((12 - $sleep) / (12 - 8))); // 8->20,12->0
            }
        }
    }
    $healthScore += $sleepScore;
    // Steps: 0-10000 mapped to 0-20 (cap at 10k)
    $healthScore += min($steps / 10000, 1) * 20;
    // Meals (assume balanced meals up to 3 contributes) 0-3 -> 0-10, 4 still capped
    $healthScore += min($meals, 3) / 3 * 10;
    // Negative factors bucket (max deduction 30 scaled): cigarettes, alcohol, drugs, low water, feel weak
    $neg = 0;
    $neg += min($cigarettes, 30); // heavy weight per cigarette day
    $neg += $alcoholUnits * 2; // each unit penalizes 2
    if ($drugsYes === 'yes') $neg += 15;
    if ($feelWeak === 'yes') $neg += 5;
    if ($water < 1) $neg += 5; // dehydration penalty
    // Scale negative bucket to max 30
    $negScaled = min($neg / 50, 1) * 30; // 50 raw neg points saturates penalty
    $healthScore = max(0, min(100, round($healthScore - $negScaled)));

    // Positive adjustments from extended lifestyle (small weights to avoid overpowering core components)
    // Training: up to +5 (60 min -> max)
    if ($trainingMinutes > 0) {
        $healthScore += min(1, $trainingMinutes / 60) * 5;
    }
    // Breathing practice up to +3 (15 min)
    if ($breathingMinutes > 0) {
        $healthScore += min(1, $breathingMinutes / 15) * 3;
    }
    // Cold exposure up to +2 (5 min)
    if ($coldMinutes > 0) {
        $healthScore += min(1, $coldMinutes / 5) * 2;
    }
    // Juice frequency modest boost (daily 1, multiple daily 2) if not excessive sugar
    if ($juiceFrequency === 'daily') $healthScore += 1;
    elseif ($juiceFrequency === 'multiple_daily') $healthScore += 2;
    // Sleep quality penalty
    if ($sleepQuality === 'disturbed') $healthScore -= 3;
    $healthScore = max(0, min(100, round($healthScore)));

    // build sanitized payload
    $payload = [
        'date' => $date,
        'water' => $water,
        'sleep' => $sleep,
        'steps' => $steps,
        'meals' => $meals,
        'smokingPattern' => $smokingPattern,
        'smoked24' => $smoked24,
        'cigarettes' => $cigarettes,
        'feelWeak' => $feelWeak,
        'alcoholUnits' => $alcoholUnits,
        'drugsYes' => $drugsYes,
        'drugType' => $drugType,
        'consentTimestamp' => $consentTimestamp,
        'risk' => $risk,
        'healthScore' => $healthScore,
        // Extended lifestyle tracking
        'nutritionKcal' => $nutritionKcal,
        'vitamins' => $vitamins,
        'trainingType' => $trainingType,
        'trainingMinutes' => $trainingMinutes,
        'breathingType' => $breathingType,
        'breathingMinutes' => $breathingMinutes,
        'coldMethod' => $coldMethod,
        'coldMinutes' => $coldMinutes,
        'juiceType' => $juiceType,
        'juiceFrequency' => $juiceFrequency,
        'sleepQuality' => $sleepQuality,
        'saved_by' => $userId
    ];
    $json = json_encode($payload, JSON_THROW_ON_ERROR);
    $pdo = getPDO();
    // Lightweight upsert using payload only (normalized attempt commented out due to schema mismatch naming)
    $updated = false;
    $created = false;
    try {
        $st = $pdo->prepare('INSERT INTO user_entries (user_id, entry_date, payload, created_at) VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE payload=VALUES(payload), created_at=NOW()');
        $st->execute([$userId, $date, $json]);
        // Check if row existed before by selecting row count after update for that date
        // MySQL ROW_COUNT returns 1 for insert, 2 for update in this pattern (InnoDB behavior)
        $rc = $st->rowCount();
        if ($rc === 1) $created = true; // new insert
        elseif ($rc === 2) $updated = true; // existing row updated
    } catch (PDOException $e) {
        error_log('Upsert failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'server']);
        exit;
    }
    echo json_encode([
        'ok' => true,
        'created' => $created,
        'updated' => $updated,
        'date' => $date,
        'date_formatted' => (new DateTime($date))->format('d/m/Y') . ' 00:00:00'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server']);
}
