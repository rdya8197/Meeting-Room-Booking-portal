<?php
require 'db.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

// 1. جلب جميع الحجوزات
if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM bookings ORDER BY booking_date ASC, start_time ASC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    exit;
}

// 2. إضافة أو تعديل حجز مع منع التعارض
if ($action === 'save') {
    $id = !empty($input['id']) ? (int)$input['id'] : null;
    $room = trim($input['room_name'] ?? '');
    $dept = trim($input['department'] ?? '');
    $organizer = trim($input['organizer_name'] ?? '');
    $ext = trim($input['extension_number'] ?? '');
    $title = trim($input['meeting_title'] ?? '');
    $date = $input['booking_date'] ?? '';
    $start = $input['start_time'] ?? '';
    $end = $input['end_time'] ?? '';
    $notes = trim($input['notes'] ?? '');

    if (!$room || !$dept || !$organizer || !$title || !$date || !$start || !$end) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى تعبئة جميع الحقول المطلوبة']);
        exit;
    }

    if ($start >= $end) {
        echo json_encode(['status' => 'error', 'message' => 'وقت النهاية يجب أن يكون بعد وقت البداية']);
        exit;
    }

    // فحص التضارب في قاعدة البيانات (منع تداخل الأوقات لنفس القاعة ونفس اليوم)
    $sql = "SELECT id, department, start_time, end_time FROM bookings 
            WHERE room_name = :room 
              AND booking_date = :bdate 
              AND (:start < end_time AND :end > start_time)";
    
    $params = [
        ':room' => $room,
        ':bdate' => $date,
        ':start' => $start,
        ':end' => $end
    ];

    if ($id) {
        $sql .= " AND id != :id";
        $params[':id'] = $id;
    }

    $check = $pdo->prepare($sql);
    $check->execute($params);
    $conflict = $check->fetch();

    if ($conflict) {
        echo json_encode([
            'status' => 'conflict',
            'message' => "القاعة محجوزة مسبقاً لقسم ({$conflict['department']}) من " . substr($conflict['start_time'], 0, 5) . " إلى " . substr($conflict['end_time'], 0, 5)
        ]);
        exit;
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE bookings SET room_name=?, department=?, organizer_name=?, extension_number=?, meeting_title=?, booking_date=?, start_time=?, end_time=?, notes=? WHERE id=?");
        $stmt->execute([$room, $dept, $organizer, $ext, $title, $date, $start, $end, $notes, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO bookings (room_name, department, organizer_name, extension_number, meeting_title, booking_date, start_time, end_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$room, $dept, $organizer, $ext, $title, $date, $start, $end, $notes]);
    }

    echo json_encode(['status' => 'success']);
    exit;
}

// 4. حذف حجز
if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}
?>