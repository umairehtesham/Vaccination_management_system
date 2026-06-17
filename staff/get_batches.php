<?php
require_once '../config/database.php';

if (isset($_GET['vaccine_id'])) {
    $v_id = (int)$_GET['vaccine_id'];
    
    // Using the Procedure
    $stmt = $conn->prepare("CALL sp_GetAvailableBatches(?)");
    $stmt->bind_param("i", $v_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<option value="">-- Select Active Batch --</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="'.$row['batch_number'].'">'.$row['batch_number'].' (Exp: '.$row['expiry_date'].')</option>';
        }
    } else {
        echo '<option value="">No active batches available</option>';
    }
}
?>