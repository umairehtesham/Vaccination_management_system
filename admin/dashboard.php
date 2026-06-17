<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Admin Dashboard - VMS";


$total_citizens = $conn->query("SELECT COUNT(*) as count FROM citizens")->fetch_assoc()['count'];
$total_vaccines = $conn->query("SELECT COUNT(*) as count FROM vaccines")->fetch_assoc()['count'];
$total_vaccinations = $conn->query("SELECT COUNT(*) as count FROM vaccination_records")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'scheduled'")->fetch_assoc()['count'];

$usage_query = $conn->query("
    SELECT v.vaccine_name, COUNT(vr.record_id) as total_used 
    FROM vaccines v 
    LEFT JOIN vaccination_records vr ON v.vaccine_id = vr.vaccine_id 
    GROUP BY v.vaccine_id, v.vaccine_name 
    ORDER BY total_used DESC
");

$expiry_query = $conn->query("
    SELECT v.vaccine_name, vs.expiry_date 
    FROM vaccine_stock vs 
    JOIN vaccines v ON vs.vaccine_id = v.vaccine_id 
    WHERE vs.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
    AND vs.expiry_date >= CURDATE()
    AND vs.quantity > 0
");

$low_stock_query = $conn->query("
    SELECT v.vaccine_name, SUM(vs.quantity) as total_combined_stock 
    FROM vaccines v 
    JOIN vaccine_stock vs ON v.vaccine_id = vs.vaccine_id 
    GROUP BY v.vaccine_id, v.vaccine_name 
    HAVING total_combined_stock < 100
    ORDER BY total_combined_stock ASC
");


$recent_vaccinations_query = $conn->query("
    SELECT vr.date_administered, c.full_name, v.vaccine_name, vr.dose_number 
    FROM vaccination_records vr
    JOIN citizens c ON vr.citizen_id = c.citizen_id
    JOIN vaccines v ON vr.vaccine_id = v.vaccine_id
    ORDER BY vr.date_administered DESC
    LIMIT 5
");

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-shield"></i> Admin Panel</h5>
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_vaccines.php"><i class="fas fa-syringe"></i> Manage Vaccines</a>
            <a href="manage_stock.php"><i class="fas fa-boxes"></i> Manage Stock</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="card dashboard-card stat-card primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="stat-label">Total Citizens</p>
                                    <h3 class="stat-value"><?php echo $total_citizens; ?></h3>
                                </div>
                                <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="stat-label">Total Vaccines</p>
                                    <h3 class="stat-value"><?php echo $total_vaccines; ?></h3>
                                </div>
                                <div class="stat-icon text-success"><i class="fas fa-syringe"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="stat-label">Total Vaccinations</p>
                                    <h3 class="stat-value"><?php echo $total_vaccinations; ?></h3>
                                </div>
                                <div class="stat-icon text-warning"><i class="fas fa-notes-medical"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card stat-card danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="stat-label">Pending Appointments</p>
                                    <h3 class="stat-value"><?php echo $total_appointments; ?></h3>
                                </div>
                                <div class="stat-icon text-danger"><i class="fas fa-calendar-check"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card dashboard-card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-hourglass-end"></i> Vaccine Expiry Alerts</h5>
                        </div>
                        <div class="card-body">
                            <?php if($expiry_query->num_rows > 0): ?>
                                <table class="table table-sm">
                                    <thead><tr><th>Vaccine</th><th>Batch</th><th>Expiry Date</th></tr></thead>
                                    <tbody>
                                        <?php while($ex = $expiry_query->fetch_assoc()): ?>
                                            <tr class="table-danger">
                                                <td><?php echo htmlspecialchars($ex['vaccine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($ex['batch_number']); ?></td>
                                                <td><strong><?php echo $ex['expiry_date']; ?></strong></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-success small"><i class="fas fa-check-circle"></i> No vaccines expiring soon.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card dashboard-card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Vaccine Usage Report</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead><tr><th>Vaccine Name</th><th>Administered</th></tr></thead>
                                <tbody>
                                    <?php while($u = $usage_query->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['vaccine_name']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $u['total_used']; ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

                <div class="row mt-4">
                      <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header bg-warning text-dark fw-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i> Low Stock Alert
                        </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Vaccine</th>
                                <th>Batch #</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Bilkul simple query: Jis row ki quantity 100 se kam hai
                            $query = "SELECT v.vaccine_name, vs.batch_number, vs.quantity 
                                    FROM vaccine_stock vs 
                                    JOIN vaccines v ON vs.vaccine_id = v.vaccine_id 
                                    WHERE vs.quantity < 100 and CURDATE() < vs.expiry_date
                                    ORDER BY vs.quantity ASC";
                            
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td class='ps-3'>{$row['vaccine_name']}</td>
                                            <td><small class='badge bg-light text-dark'>{$row['batch_number']}</small></td>
                                            <td class='text-danger fw-bold'>{$row['quantity']}</td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center py-3'>No low stock batches found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
                </div>

                <div class="col-md-6">
                    <div class="card dashboard-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Vaccinations</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_vaccinations_query->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead><tr><th>Date</th><th>Citizen</th><th>Vaccine</th></tr></thead>
                                        <tbody>
                                            <?php while ($vacc = $recent_vaccinations_query->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($vacc['date_administered'])); ?></td>
                                                    <td><?php echo htmlspecialchars($vacc['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($vacc['vaccine_name']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No recent records.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>