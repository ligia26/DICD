<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors - breaks JSON

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $changes = $input['changes'] ?? [];
    
    $updated = 0;
    
    foreach ($changes as $change) {
        $id = $change['id'];
        $manual_category = $change['manual_category'];
        $dsli = $change['dsli'];
        
        $result = updateConfigChangeAPI($id, [
            'manual_category' => $manual_category,
            'dsli' => $dsli
        ]);
        
        ++$updated;
    }
    
    echo json_encode(['success' => true, 'updated' => $updated]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>