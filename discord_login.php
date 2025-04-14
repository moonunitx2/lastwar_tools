<?php
session_start();
require 'vendor/autoload.php';
require 'db.php';

if (isset($_SESSION['discord_user']) && isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check for stored token
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT access_token, token_expires, refresh_token FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && $user['access_token'] && time() < $user['token_expires']) {
        $_SESSION['discord_user'] = true;
        header('Location: index.php');
        exit();
    }

    // Token refresh logic
    if ($user && $user['refresh_token']) {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://discord.com/api/oauth2/token', [
                'form_params' => [
                    'client_id' => 'Your_client_Id',
                    'client_secret' => 'Your_client_secret',
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $user['refresh_token'],
                    'redirect_uri' => 'https://your_url/discord_callback.php'
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            if (isset($data['access_token'])) {
                $accessToken = $data['access_token'];
                $tokenExpires = time() + $data['expires_in'];
                $refreshToken = $data['refresh_token'];

                $stmtUpdate = $conn->prepare("UPDATE users SET access_token = ?, token_expires = ?, refresh_token = ? WHERE id = ?");
                $stmtUpdate->bind_param("sisi", $accessToken, $tokenExpires, $refreshToken, $userId);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                $_SESSION['discord_user'] = true;
                header('Location: index.php');
                exit();
            }
        } catch (Exception $e) {
            error_log('Discord token refresh failed: ' . $e->getMessage());
        }
    }
}

// Initialize new OAuth flow
$clientId = '1290717811563565117';
$redirectUri = 'https://814.rocks/discord_callback.php';
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2state'] = $state;

$authorizationUrl = "https://discord.com/api/oauth2/authorize?" . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'identify email',
    'state' => $state
]);

header('Location: ' . $authorizationUrl);
exit();
?>
