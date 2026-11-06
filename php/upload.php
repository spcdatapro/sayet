<?php
    set_time_limit(0);
    ini_set('upload-max-filesize', '200M');
    ini_set('post_max_size', '200M');

    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_FILES['file'], $_POST['directorio'], $_POST['prefijo'])
        || !isset($_FILES['file']['error'])
        || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false]);
        exit;
    }

    $filename = preg_replace('/[^A-Za-z0-9.\-]/', '', $_FILES['file']['name']);
    $elDir = $_POST['directorio'];
    $elPref = $_POST['prefijo'];
    $destination = $elDir . $elPref . $filename;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        http_response_code(500);
        echo json_encode(['success' => false]);
        exit;
    }

    echo json_encode(['success' => true]);
