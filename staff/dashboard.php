<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('staff')) {
    header('Location: ../login.php');
    exit();
}


//Total Citizens
$total_citizens = $conn->query("SELECT COUNT(*) as count FROM citizens")->fetch_assoc()['count'];

//Today's Vaccinations
$today_vax = $conn->query("SELECT COUNT(*) as count FROM vaccination_records WHERE DATE(date_administered) = CURDATE()")->fetch_assoc()['count'];

//Today's Pending Appointments
$today_app_res = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'scheduled' AND DATE(scheduled_date) = CURDATE()");
$today_app = $today_app_res->fetch_assoc()['count'];


$appointments_query = $conn->query("
    SELECT a.scheduled_date, c.full_name, v.vaccine_name 
    FROM appointments a 
    JOIN citizens c ON a.citizen_id = c.citizen_id 
    JOIN vaccines v ON a.vaccine_id = v.vaccine_id 
    WHERE DATE(a.scheduled_date) = CURDATE() 
    ORDER BY a.scheduled_date ASC
");

$recent_records_query = $conn->query("
    SELECT vr.dose_number, c.full_name, v.vaccine_name 
    FROM vaccination_records vr 
    JOIN citizens c ON vr.citizen_id = c.citizen_id 
    JOIN vaccines v ON vr.vaccine_id = v.vaccine_id 
    ORDER BY vr.record_id DESC LIMIT 5
");

$pageTitle = "Staff Dashboard - VMS";
include '../includes/header.php';
?>

<style>
    
    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border-left: 5px solid;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .border-blue { border-left-color: #4e73df !important; }
    .border-green { border-left-color: #1cc88a !important; }
    .border-orange { border-left-color: #f6c23e !important; }

    .stat-title {
        color: #4e73df;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    .text-green { color: #1cc88a !important; }
    .text-orange { color: #f6c23e !important; }

    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #5a5c69;
    }

    .stat-icon-new {
        color: #dddfeb; /* Light silver/gray icons */
    }

    /* Section Headers */
    .section-header-yellow {
        background-color: #f6c23e;
        color: white;
        padding: 12px 15px;
        border-radius: 5px 5px 0 0;
        font-weight: bold;
    }

    .section-header-blue {
        background-color: #4e73df;
        color: white;
        padding: 12px 15px;
        border-radius: 5px 5px 0 0;
        font-weight: bold;
    }

    /* Quick Action Buttons */
    .btn-action {
        border-radius: 5px;
        padding: 12px;
        font-size: 0.9rem;
        margin-bottom: 10px;
        font-weight: 500;
        transition: transform 0.2s;
    }
    .btn-action:hover {
        transform: translateY(-3px);
    }
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-nurse"></i> Staff Panel</h5>
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_citizens.php"><i class="fas fa-users"></i> Citizens</a>
            <a href="record_vaccination.php"><i class="fas fa-syringe"></i> Record Vaccination</a>
            <a href="manage_appointments.php"><i class="fas fa-calendar-alt"></i> Appointments</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0 text-gray-800">Staff Dashboard</h2>
                <span class="badge bg-white text-dark p-2 shadow-sm border"><?php echo date('l, d M Y'); ?></span>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card border-blue">
                        <div>
                            <div class="stat-title">Total Registered Citizens</div>
                            <div class="stat-value"><?php echo $total_citizens; ?></div>
                        </div>
                        <i class="fas fa-users fa-2x stat-icon-new"></i>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card border-green">
                        <div>
                            <div class="stat-title text-green">Today's Vaccinations</div>
                            <div class="stat-value"><?php echo $today_vax; ?></div>
                        </div>
                        <i class="fas fa-syringe fa-2x stat-icon-new"></i>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card border-orange">
                        <div>
                            <div class="stat-title text-orange">Today's Appointments</div>
                            <div class="stat-value"><?php echo $today_app; ?></div>
                        </div>
                        <i class="fas fa-calendar-check fa-2x stat-icon-new"></i>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6 mb-4">
                    <div class="section-header-yellow">
                        <i class="fas fa-calendar-alt"></i> Today's Appointment List
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light small text-uppercase">
                                        <tr><th>Citizen</th><th>Vaccine</th><th>Time</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if($appointments_query->num_rows > 0): ?>
                                            <?php while($row = $appointments_query->fetch_assoc()): ?>
                                            <tr>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['vaccine_name']); ?></td>
                                                <td><span class="badge bg-light text-dark"><?php echo date('h:i A', strtotime($row['scheduled_date'])); ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No appointments scheduled for today.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="section-header-blue">
                        <i class="fas fa-history"></i> Recent Vaccinations
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light small text-uppercase">
                                        <tr><th>Citizen</th><th>Vaccine</th><th>Dose</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if($recent_records_query->num_rows > 0): ?>
                                            <?php while($row = $recent_records_query->fetch_assoc()): ?>
                                            <tr>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['vaccine_name']); ?></td>
                                                <td><span class="badge bg-info">Dose <?php echo $row['dose_number']; ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No records found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>