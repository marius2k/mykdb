<?php
/**
 * Test script for search analytics API
 */
require_once __DIR__ . '/../config/bootstrap.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate a logged-in user (change this to match your actual user ID)
$_SESSION['user']['id'] = 2; // Change to your user ID

// Test data
$testData = [
    'action_type' => 'search_query',
    'query' => 'test',
    'result_count' => 5,
    'search_id' => 'test_' . time(),
    'session_id' => session_id()
];

echo "Testing search analytics API...\n";
echo "Test data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, APP_URL . 'public/api/bkd_search_analytics.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    if ($responseData['success']) {
        echo "✓ SUCCESS: Search analytics recorded successfully!\n";
        
        // Verify in database
        $db = new Database();
        $lastSearch = $db->fetchSingle("SELECT * FROM search_queries ORDER BY id DESC LIMIT 1");
        
        if ($lastSearch) {
            echo "\nLast search query in database:\n";
            echo "  ID: " . $lastSearch['id'] . "\n";
            echo "  Query: " . $lastSearch['query'] . "\n";
            echo "  Result Count: " . $lastSearch['result_count'] . "\n";
            echo "  Created At: " . $lastSearch['created_at'] . "\n";
        }
    } else {
        echo "✗ FAILED: " . ($responseData['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✗ FAILED: HTTP error code $httpCode\n";
    echo "Response body: $response\n";
}
