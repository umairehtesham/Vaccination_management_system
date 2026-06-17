<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!hasRole('staff') && !hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

$success = $error = "";

// form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_vax'])) {
    $c_id = $_POST['citizen_id'] ?? null;
    $v_id = $_POST['vaccine_id'] ?? null;
    $b_no = $_POST['batch_number'] ?? null; 
    $d_no = (int)($_POST['dose_number'] ?? 1);
    $d_admin = $_POST['date_administered'] ?? date('Y-m-d');

    if ($c_id && $v_id && $b_no) {
        try {
            $stmt = $conn->prepare("CALL sp_RecordVaccination(?, ?, ?, ?, ?)");
            $stmt->bind_param("iisis", $c_id, $v_id, $b_no, $d_no, $d_admin);
            
            if ($stmt->execute()) {
                $success = "Success: Dose $d_no recorded and stock quantity updated.";
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Please ensure all fields are selected.";
    }
}

$pageTitle = "Record Vaccination - VMS";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 sidebar">
            <h5 class="text-white mb-4"><i class="fas fa-user-nurse"></i> Staff Panel</h5>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_citizens.php"><i class="fas fa-users"></i> Citizens</a>
            <a href="record_vaccination.php" class="active"><i class="fas fa-syringe"></i> Record Vaccination</a>
            <a href="manage_appointments.php"><i class="fas fa-calendar-alt"></i> Appointments</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="col-md-10">
            <h2 class="mb-4"><i class="fas fa-syringe"></i> Vaccination Entry</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">Record Vaccination Dose</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Citizen Name</label>
                                <select name="citizen_id" class="form-select form-control" required>
                                    <option value="">-- Choose Patient --</option>
                                    <?php 
                                    $citizens = $conn->query("SELECT citizen_id, full_name FROM citizens ORDER BY full_name ASC");
                                    while($c = $citizens->fetch_assoc()) {
                                        echo "<option value='{$c['citizen_id']}'>{$c['full_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vaccine Type</label>
                                <select name="vaccine_id" id="vaccine_select" class="form-select form-control" required>
                                    <option value="">-- Choose Vaccine --</option>
                                    <?php 
                                    $vaccines = $conn->query("SELECT vaccine_id, vaccine_name FROM vaccines");
                                    while($v = $vaccines->fetch_assoc()) {
                                        echo "<option value='{$v['vaccine_id']}'>{$v['vaccine_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Available Batch No.</label>
                                <select name="batch_number" id="batch_select" class="form-select form-control" required>
                                    <option value="">-- First Select Vaccine --</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dose Number</label>
                                <select name="dose_number" class="form-select form-control" required>
                                    <option value="1">Dose 1</option>
                                    <option value="2">Dose 2</option>
                                    <option value="3">Dose 3</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Administration Date</label>
                                <input type="date" name="date_administered" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <hr>
                                <a href="dashboard.php" class="btn btn-light px-4 me-2">Cancel</a>
                                <button type="submit" name="record_vax" class="btn btn-success px-5 shadow-sm">
                                    <i class="fas fa-save me-2"></i> Save Vaccination Record
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('vaccine_select').addEventListener('change', function() {
    var vaccineId = this.value;
    var batchSelect = document.getElementById('batch_select');

    if (vaccineId) {
        batchSelect.innerHTML = '<option value="">Loading batches...</option>';
        fetch('get_batches.php?vaccine_id=' + vaccineId)
            .then(response => response.text())
            .then(data => {
                batchSelect.innerHTML = data;
            });
    } else {
        batchSelect.innerHTML = '<option value="">-- First Select Vaccine --</option>';
    }
});
</script>

<?php include '../includes/footer.php'; ?>