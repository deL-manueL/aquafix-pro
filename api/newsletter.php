<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Please enter a valid email address'], 400);
}

try {
    $added = save_newsletter($email);
    if (!$added) {
        json_response(['error' => 'You\'re already subscribed!'], 200);
    }
    json_response(['success' => true, 'message' => 'You\'re subscribed! Thank you.']);
} catch (Exception $e) {
    json_response(['error' => 'Something went wrong. Please try again.'], 500);
}
