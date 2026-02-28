<?php
/**
 * Smart Telegram Sender for Vercel
 * Handles Admin and Guest notifications
 */

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['type'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'type is required']);
    exit;
}

// Your Bot Token
$botToken = '8402876466:AAFeQnHnOxwcvcLZhBObSNekAMHCmDa-dEQ';
$telegramUrl = "https://api.telegram.org/bot$botToken/sendMessage";

// Admin Chat ID
$adminChatId = '7354567274';

// Functions
function sendMessage($chatId, $text) {
    global $telegramUrl;

    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($telegramUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

// Helper: format booking message
function formatBookingMessage($bookingData, $type = 'guest') {
    $message = "";
    if ($type === 'guest') {
        $message .= "🏨 <b>BOOKING CONFIRMED</b>\n\n";
        $message .= "✅ Your reservation at Melinda's Hotel has been confirmed!\n\n";
    } else {
        $message .= "🔔 <b>NEW BOOKING ALERT</b>\n\n";
    }

    $message .= "🔖 Booking ID: <b>#{$bookingData['booking_id']}</b>\n";
    $message .= "👤 Guest: " . htmlspecialchars($bookingData['guest_name']) . "\n";
    if (!empty($bookingData['guest_email'])) $message .= "Email: " . htmlspecialchars($bookingData['guest_email']) . "\n";
    if (!empty($bookingData['guest_phone'])) $message .= "Phone: " . htmlspecialchars($bookingData['guest_phone']) . "\n";
    $message .= "🏠 Room: " . htmlspecialchars($bookingData['room_type']) . " Room #" . $bookingData['room_number'] . "\n";
    $message .= "📅 Check-in: " . date('M d, Y', strtotime($bookingData['check_in'])) . "\n";
    $message .= "📅 Check-out: " . date('M d, Y', strtotime($bookingData['check_out'])) . "\n";
    $message .= "👥 Guests: {$bookingData['adults']}";
    if ($bookingData['children'] > 0) $message .= ", {$bookingData['children']} Child(ren)";
    $message .= "\n🌙 Nights: " . ($bookingData['nights'] ?? 1) . "\n";
    $paymentMethod = !empty($bookingData['payment_method']) ? htmlspecialchars($bookingData['payment_method']) : 'N/A';
    $message .= "💳 Payment: <b>$paymentMethod</b>\n";
    $message .= "💰 Total: <b>₱" . number_format($bookingData['total_price'], 2) . "</b>\n";

    if ($type === 'guest') {
        $message .= "\n⏰ <b>Important Information:</b>\n• Check-in: 2:00 PM\n• Check-out: 12:00 PM\n• Bring valid ID\n";
        $message .= "\n📞 Contact: +63 2 1234 5678\nEmail: reservations@melindashotel.com";
    }

    return $message;
}

// Determine type and send message
$type = $input['type']; // 'admin' or 'guest'
$bookingData = $input['bookingData'] ?? [];

if ($type === 'admin') {
    $message = formatBookingMessage($bookingData, 'admin');
    $sent = sendMessage($adminChatId, $message);
} elseif ($type === 'guest' && !empty($input['chat_id'])) {
    $message = formatBookingMessage($bookingData, 'guest');
    $sent = sendMessage($input['chat_id'], $message);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid type or missing chat_id for guest']);
    exit;
}

if ($sent) {
    echo json_encode(['status' => 'success', 'message' => 'Message sent']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
}
