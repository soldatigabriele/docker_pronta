<?php

require_once 'vendor/autoload.php';

// Simple API test script
$baseUrl = 'https://pronta.test/api';

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "Testing iOS Reminders API...\n\n";

// Test 1: Register a user
echo "1. Testing user registration...\n";
$registerData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
];

$response = makeRequest('POST', "$baseUrl/auth/register", $registerData);
echo "Status: {$response['status']}\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

if ($response['status'] === 201 && isset($response['body']['data']['token'])) {
    $token = $response['body']['data']['token'];
    echo "✅ Registration successful! Token: " . substr($token, 0, 20) . "...\n\n";
    
    // Test 2: Create a list
    echo "2. Testing list creation...\n";
    $listData = [
        'name' => 'Test Grocery List',
        'description' => 'My test grocery list',
        'color' => '#007AFF',
        'icon' => 'cart.fill'
    ];
    
    $response = makeRequest('POST', "$baseUrl/lists", $listData, $token);
    echo "Status: {$response['status']}\n";
    echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response['status'] === 201 && isset($response['body']['data']['id'])) {
        $listId = $response['body']['data']['id'];
        echo "✅ List created successfully! ID: $listId\n\n";
        
        // Test 3: Add an item to the list
        echo "3. Testing item creation...\n";
        $itemData = [
            'title' => 'Buy milk',
            'description' => '2% organic milk',
            'tags' => ['dairy', 'organic'],
            'category' => 'groceries'
        ];
        
        $response = makeRequest('POST', "$baseUrl/lists/$listId/items", $itemData, $token);
        echo "Status: {$response['status']}\n";
        echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";
        
        if ($response['status'] === 201 && isset($response['body']['data']['id'])) {
            $itemId = $response['body']['data']['id'];
            echo "✅ Item created successfully! ID: $itemId\n\n";
            
            // Test 4: Toggle item completion
            echo "4. Testing item completion toggle...\n";
            $response = makeRequest('PATCH', "$baseUrl/lists/$listId/items/$itemId/toggle-complete", null, $token);
            echo "Status: {$response['status']}\n";
            echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";
            
            if ($response['status'] === 200) {
                echo "✅ Item completion toggled successfully!\n\n";
            }
        }
        
        // Test 5: Get all lists
        echo "5. Testing get all lists...\n";
        $response = makeRequest('GET', "$baseUrl/lists", null, $token);
        echo "Status: {$response['status']}\n";
        echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";
        
        if ($response['status'] === 200) {
            echo "✅ Lists retrieved successfully!\n\n";
        }
    }
    
    // Test 6: Test autocomplete
    echo "6. Testing autocomplete...\n";
    $response = makeRequest('GET', "$baseUrl/items/autocomplete?q=mi", null, $token);
    echo "Status: {$response['status']}\n";
    echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response['status'] === 200) {
        echo "✅ Autocomplete working!\n\n";
    }
    
} else {
    echo "❌ Registration failed!\n";
}

echo "API testing completed!\n"; 