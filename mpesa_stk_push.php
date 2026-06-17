<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php';

use Aramics\MpesaSdk\Mpesa;

function getAccessToken() {
    $consumer_key = MPESA_CONSUMER_KEY;
    $consumer_secret = MPESA_CONSUMER_SECRET;
    
    $url = (MPESA_ENV == 'sandbox') 
        ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $result = json_decode($response);
    curl_close($ch);
    
    return $result->access_token ?? null;
}

function stkPushRequest($phone, $amount, $account_ref) {
    $token = getAccessToken();
    if (!$token) {
        return ['error' => 'Failed to get access token'];
    }
    
    $url = (MPESA_ENV == 'sandbox')
        ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
        : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    
    $data = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => (int)$amount,
        'PartyA' => $phone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => $account_ref ?: 'Payment',
        'TransactionDesc' => 'Payment for services'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);
    
    return $result;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    $amount = (int)$_POST['amount'];
    $account_ref = $_POST['account_ref'] ?? 'Payment';
    
    // Validate phone number starts with 254
    if (substr($phone, 0, 3) != '254') {
        $_SESSION['error'] = 'Phone number must start with 254 (e.g., 254712345678)';
        header('Location: index.php');
        exit;
    }
    
    // Store initial transaction record
    $stmt = $pdo->prepare("INSERT INTO transactions (phone_number, amount, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$phone, $amount]);
    $transaction_id = $pdo->lastInsertId();
    
    // Send STK Push
    $response = stkPushRequest($phone, $amount, $account_ref);
    
    // Log the request
    $logStmt = $pdo->prepare("INSERT INTO payment_logs (transaction_id, log_type, request_payload, response_payload) VALUES (?, 'stk_request', ?, ?)");
    $logStmt->execute([$transaction_id, json_encode($_POST), json_encode($response)]);
    
    if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
        // Update transaction with checkout request ID
        $updateStmt = $pdo->prepare("UPDATE transactions SET checkout_request_id = ? WHERE id = ?");
        $updateStmt->execute([$response['CheckoutRequestID'], $transaction_id]);
        
        $_SESSION['success'] = 'STK Push sent! Please check your phone and enter M-Pesa PIN.';
        $_SESSION['checkout_request_id'] = $response['CheckoutRequestID'];
        header('Location: status.php');
    } else {
        $errorMsg = $response['errorMessage'] ?? $response['ResponseDescription'] ?? 'Failed to initiate payment';
        $_SESSION['error'] = $errorMsg;
        
        // Update transaction as failed
        $failStmt = $pdo->prepare("UPDATE transactions SET status = 'failed', result_desc = ? WHERE id = ?");
        $failStmt->execute([$errorMsg, $transaction_id]);
        
        header('Location: index.php');
    }
    exit;
}
?>