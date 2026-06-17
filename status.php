<?php
session_start();
require_once 'config.php';

$checkout_request_id = $_SESSION['checkout_request_id'] ?? null;
$transaction = null;

if ($checkout_request_id) {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE checkout_request_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$checkout_request_id]);
    $transaction = $stmt->fetch();
}

// Auto-refresh every 3 seconds to check status
header('Refresh: 3; url=status.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
        }
        .pending { color: #ff9800; }
        .completed { color: #4CAF50; }
        .failed { color: #f44336; }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2196F3;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💰 Payment Status</h2>
        
        <?php if ($transaction): ?>
            <?php if ($transaction['status'] == 'pending'): ?>
                <div class="spinner"></div>
                <h3 class="pending">⏳ Waiting for payment...</h3>
                <p>Please check your phone and enter your M-Pesa PIN to complete payment.</p>
                <p><small>Transaction ID: <?php echo $transaction['checkout_request_id']; ?></small></p>
                
            <?php elseif ($transaction['status'] == 'completed'): ?>
                <div style="font-size: 60px;">✅</div>
                <h3 class="completed">Payment Successful!</h3>
                <p>Amount: <strong>KES <?php echo number_format($transaction['amount'], 2); ?></strong></p>
                <p>M-Pesa Receipt: <strong><?php echo $transaction['mpesa_receipt_number']; ?></strong></p>
                <p>Thank you for your payment!</p>
                <a href="index.php" class="btn">Make Another Payment</a>
                
            <?php else: ?>
                <div style="font-size: 60px;">❌</div>
                <h3 class="failed">Payment Failed</h3>
                <p><?php echo htmlspecialchars($transaction['result_desc']); ?></p>
                <a href="index.php" class="btn">Try Again</a>
            <?php endif; ?>
            
        <?php else: ?>
            <p>No active payment found.</p>
            <a href="index.php" class="btn">Start New Payment</a>
        <?php endif; ?>
    </div>
</body>
</html>