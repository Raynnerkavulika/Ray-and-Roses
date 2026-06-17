<?php
require_once 'config.php';

// Log incoming callback for debugging
$callbackData = file_get_contents('php://input');
$callback = json_decode($callbackData, true);

// Log callback
$logStmt = $pdo->prepare("INSERT INTO payment_logs (log_type, request_payload) VALUES ('callback_received', ?)");
$logStmt->execute([$callbackData]);

if (isset($callback['Body']['stkCallback'])) {
    $stkCallback = $callback['Body']['stkCallback'];
    $resultCode = $stkCallback['ResultCode'];
    $resultDesc = $stkCallback['ResultDesc'];
    $checkoutRequestID = $stkCallback['CheckoutRequestID'];
    
    // Find transaction
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE checkout_request_id = ?");
    $stmt->execute([$checkoutRequestID]);
    $transaction = $stmt->fetch();
    
    if ($transaction) {
        if ($resultCode == 0) {
            // Payment successful
            $metadata = $stkCallback['CallbackMetadata']['Item'];
            $mpesaReceipt = '';
            $amount = 0;
            
            foreach ($metadata as $item) {
                if ($item['Name'] == 'MpesaReceiptNumber') {
                    $mpesaReceipt = $item['Value'];
                }
                if ($item['Name'] == 'Amount') {
                    $amount = $item['Value'];
                }
            }
            
            $updateStmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'completed', 
                    mpesa_receipt_number = ?,
                    result_code = ?,
                    result_desc = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$mpesaReceipt, $resultCode, $resultDesc, $transaction['id']]);
            
            // You can also update your order/booking system here
            
        } else {
            // Payment failed
            $updateStmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'failed', 
                    result_code = ?,
                    result_desc = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$resultCode, $resultDesc, $transaction['id']]);
        }
    }
}

// Always respond with success to M-Pesa
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
?>