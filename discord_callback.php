<?php
session_start();
require 'vendor/autoload.php';
require 'db.php';

// Verify state
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}

// Check for authorization code
if (isset($_GET['code'])) {
    try {
        $client = new \GuzzleHttp\Client();
        
        // Exchange authorization code for access token
        $response = $client->post('https://discord.com/api/oauth2/token', [
            'form_params' => [
                'client_id' => 'your discord_id',
                'client_secret' => 'your discord_client_secret',
                'grant_type' => '',
                'code' => $_GET['code'],
                'redirect_uri' => 'https://Your_URL/discord_callback.php'
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        $tokens = json_decode($response->getBody(), true);

        // Get user details from Discord
        $userResponse = $client->get('https://discord.com/api/users/@me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokens['access_token']
            ]
        ]);

        $user = json_decode($userResponse->getBody(), true);

        // First check if user exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE discord = ?");
        $checkStmt->bind_param("s", $user['id']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existingUser = $result->fetch_assoc();
        $checkStmt->close();

        if ($existingUser) {
            // Update existing user
            $stmt = $conn->prepare("UPDATE users SET 
                username = ?,
                email = ?,
                access_token = ?,
                refresh_token = ?,
                token_expires = ?
                WHERE discord = ?");

            $tokenExpires = time() + $tokens['expires_in'];
            
            $stmt->bind_param(
                "ssssss",
                $user['username'],
                $user['email'],
                $tokens['access_token'],
                $tokens['refresh_token'],
                $tokenExpires,
                $user['id']
            );

            $stmt->execute();
            $userId = $existingUser['id'];
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users 
                (username, discord, email, password, role, access_token, refresh_token, token_expires) 
                VALUES (?, ?, ?, '', 'user', ?, ?, ?)");

            $tokenExpires = time() + $tokens['expires_in'];
            
            $stmt->bind_param(
                "ssssss",
                $user['username'],
                $user['id'],
                $user['email'],
                $tokens['access_token'],
                $tokens['refresh_token'],
                $tokenExpires
            );

            $stmt->execute();
            $userId = $conn->insert_id;
        }

        $stmt->close();

        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['discord_user'] = true;

        // Redirect to index
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        exit('Failed to get user details: ' . $e->getMessage());
    }
}
?>
