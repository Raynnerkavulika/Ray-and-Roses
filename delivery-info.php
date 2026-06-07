<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Information - Ray & Roses</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .delivery-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 5%;
        }
        
        .delivery-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .delivery-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2d2a24;
            margin-bottom: 1rem;
        }
        
        .delivery-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .delivery-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .delivery-card:hover {
            transform: translateY(-5px);
        }
        
        .delivery-card i {
            font-size: 2.5rem;
            color: #c45c4a;
            margin-bottom: 1rem;
        }
        
        .delivery-card h3 {
            margin-bottom: 1rem;
            color: #2d2a24;
        }
        
        .delivery-card p {
            color: #666;
            line-height: 1.6;
        }
        
        .delivery-info-section {
            background: #fef6ef;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .delivery-info-section h2 {
            color: #c45c4a;
            margin-bottom: 1rem;
        }
        
        .delivery-info-section ul {
            list-style: none;
            padding-left: 0;
        }
        
        .delivery-info-section li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .delivery-info-section li:before {
            content: "✓";
            color: #c45c4a;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        .table-shipping {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        
        .table-shipping th,
        .table-shipping td {
            border: 1px solid #e0d4c8;
            padding: 0.8rem;
            text-align: left;
        }
        
        .table-shipping th {
            background: #c45c4a;
            color: white;
        }
        
        .table-shipping tr:nth-child(even) {
            background: #f9f3ef;
        }
        
        @media (max-width: 768px) {
            .delivery-header h1 {
                font-size: 1.8rem;
            }
            
            .table-shipping {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<div class="delivery-container">
    <div class="delivery-header">
        <h1>🚚 Delivery Information</h1>
        <p>Everything you need to know about our delivery services</p>
    </div>
    
    <div class="delivery-grid">
        <div class="delivery-card">
            <i class="fas fa-clock"></i>
            <h3>Fast Delivery</h3>
            <p>Same-day delivery for orders placed before 2 PM within Nairobi. Next-day delivery for other locations.</p>
        </div>
        
        <div class="delivery-card">
            <i class="fas fa-gift"></i>
            <h3>Free Gift Wrapping</h3>
            <p>All orders come beautifully wrapped with a personalized message card at no extra cost.</p>
        </div>
        
        <div class="delivery-card">
            <i class="fas fa-shield-alt"></i>
            <h3>Freshness Guarantee</h3>
            <p>We guarantee fresh flowers. If you're not satisfied, we'll redeliver or refund.</p>
        </div>
    </div>
    
    <div class="delivery-info-section">
        <h2>📍 Delivery Areas</h2>
        <p>We deliver flowers across Kenya. Here are our current delivery zones:</p>
        <ul>
            <li><strong>Nairobi</strong> - All areas (including Westlands, Karen, Lang'ata, Eastlands, and CBD)</li>
            <li><strong>Kiambu</strong> - Thika, Ruiru, Juja, Kikuyu, Limuru</li>
            <li><strong>Machakos</strong> - Athi River, Syokimau, Machakos Town</li>
            <li><strong>Nakuru</strong> - Nakuru City, Naivasha, Gilgil</li>
            <li><strong>Other towns</strong> - Mombasa, Kisumu, Eldoret (2-3 days delivery)</li>
        </ul>
    </div>
    
    <div class="delivery-info-section">
        <h2>💰 Delivery Charges & Timeframes</h2>
        <table class="table-shipping">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Delivery Fee</th>
                    <th>Estimated Time</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Nairobi CBD & Surrounding</td>
                    <td>KES 300</td>
                    <td>2-4 hours (same day)</td>
                </tr>
                <tr>
                    <td>Nairobi Suburbs</td>
                    <td>KES 400 - 600</td>
                    <td>4-6 hours (same day)</td>
                </tr>
                <tr>
                    <td>Kiambu & Machakos</td>
                    <td>KES 600 - 800</td>
                    <td>Next day delivery</td>
                </tr>
                <tr>
                    <td>Nakuru & Naivasha</td>
                    <td>KES 1,000</td>
                    <td>2 days</td>
                </tr>
                <tr>
                    <td>Other Major Towns</td>
                    <td>KES 1,500 - 2,000</td>
                    <td>2-3 days</td>
                </tr>
            </tbody>
        </table>
        <p><strong>Note:</strong> Free delivery on orders above KES 5,000 within Nairobi.</p>
    </div>
    
    <div class="delivery-info-section">
        <h2>📅 Delivery Schedule</h2>
        <ul>
            <li><strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM</li>
            <li><strong>Saturday:</strong> 10:00 AM - 4:00 PM</li>
            <li><strong>Sunday & Public Holidays:</strong> No deliveries (except special occasions like Valentine's Day, Mother's Day)</li>
            <li><strong>Same-day delivery cutoff:</strong> 2:00 PM (orders after 2 PM delivered next day)</li>
        </ul>
    </div>
    
    <div class="delivery-info-section">
        <h2>📝 Important Information</h2>
        <ul>
            <li>Our delivery partners will call the recipient before delivery</li>
            <li>If recipient isn't available, flowers will be left with a neighbor or security (with permission)</li>
            <li>You can request specific delivery time windows in the order notes</li>
            <li>Tracking link will be sent via SMS/Email once order is dispatched</li>
            <li>For urgent deliveries, please call us at <strong>+254 712 345 678</strong></li>
        </ul>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>