<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Citizens only see their own; Admin/Staff can see by passing ?id=X
$citizen_id = null;
if (hasRole('citizen')) {
    $user_id = $_SESSION['user_id'];
    $cq = $conn->prepare("SELECT citizen_id FROM citizens WHERE user_id = ?");
    $cq->bind_param("i", $user_id);
    $cq->execute();
    $res = $cq->get_result()->fetch_assoc();
    $citizen_id = $res['citizen_id'];
} else if (isset($_GET['id'])) {
    $citizen_id = (int)$_GET['id'];
}

if (!$citizen_id) { die("Access Denied: Citizen ID not found."); }

// Fetch Patient Details
$citizen = $conn->query("SELECT * FROM citizens WHERE citizen_id = $citizen_id")->fetch_assoc();

// Fetch Full History
$history = $conn->query("
    SELECT vr.dose_number, vr.date_administered, v.vaccine_name, v.manufacturer 
    FROM vaccination_records vr 
    JOIN vaccines v ON vr.vaccine_id = v.vaccine_id 
    WHERE vr.citizen_id = $citizen_id 
    ORDER BY vr.dose_number ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vaccination Certificate - <?php echo $citizen['full_name']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .cert-container { border: 15px double #004085; padding: 50px; margin: 30px auto; max-width: 850px; background: #fff; }
        .cert-header { border-bottom: 2px solid #004085; margin-bottom: 30px; padding-bottom: 10px; }
        @media print { .no-print { display: none; } body { background: white; } .cert-container { border: 10px solid #004085; margin: 0; } }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4 no-print text-center">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print / Save PDF</button>
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<div class="cert-container shadow-lg">
    <div class="cert-header text-center">
        <h1 class="text-primary">Official Vaccination Certificate</h1>
        <p class="text-muted">Vaccination Management System (VMS) - Authorized Record</p>
    </div>

    <div class="row mb-5">
        <div class="col-7">
            <h5>Citizen Details</h5>
            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($citizen['full_name']); ?></p>
            <p class="mb-1"><strong>ID Number:</strong> VMS-<?php echo str_pad($citizen['citizen_id'], 5, '0', STR_PAD_LEFT); ?></p>
            <p class="mb-1"><strong>Date of Birth:</strong> <?php echo date('d-M-Y', strtotime($citizen['date_of_birth'])); ?></p>
        </div>
        <div class="col-5 text-end">
            <p><strong>Issue Date:</strong> <?php echo date('d-M-Y'); ?></p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=VERIFIED_CITIZEN_<?php echo $citizen_id; ?>" alt="QR Code">
        </div>
    </div>

    <table class="table table-bordered text-center">
        <thead class="table-primary">
            <tr>
                <th>Dose #</th>
                <th>Vaccine Name</th>
                <th>Manufacturer</th>
                <th>Date Received</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($history->num_rows > 0): ?>
                <?php while ($row = $history->fetch_assoc()): ?>
                <tr>
                    <td>Dose <?php echo $row['dose_number']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['vaccine_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['manufacturer']); ?></td>
                    <td><?php echo date('d-M-Y', strtotime($row['date_administered'])); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No vaccination records available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row mt-5 pt-5">
        <div class="col-6 text-center">
            <div style="border-top: 1px solid #000; width: 200px; margin: auto;"></div>
            <p class="small">Healthcare Authority Signature</p>
        </div>
        <div class="col-6 text-center">
             <p class="small text-muted italic">This is a system-generated document and remains valid as a digital or physical copy.</p>
        </div>
    </div>
</div>

</body>
</html>