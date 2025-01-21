<?php
$json = ['success' => false, 'errors' => []];

if(isset($_GET['action']) && $_GET['action'] === 'health')
{
    $json['success'] = true;
}

header('Content-Type: application/json');
echo json_encode($json);