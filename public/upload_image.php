<?php

require_once '../config/bootstrap.php';

// Setăm folderul de upload
$uploadDir = APP_ROOT . 'uploads/';
$uploadUrl = APP_URL.'uploads/'; // pentru browser accesibil

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true); // creăm folderul dacă nu există
}

if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['image']['tmp_name'];
    $name = basename($_FILES['image']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tip de fișier invalid.']);
        exit;
    }

    $newName = uniqid('img_', true) . '.' . $ext;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($tmpName, $destination)) {
        echo json_encode(['url' => $uploadUrl . $newName]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la salvare.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Niciun fișier uploadat.']);
}
