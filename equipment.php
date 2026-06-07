<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

$stmt = $pdo->query("SELECT DISTINCT category FROM equipment ORDER BY category");
$categories = $stmt->fetchAll();

$statuses = ['available', 'in_use', 'maintenance', 'broken'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_equipment'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $quantity = (int)$_POST['quantity'];
    $status = $_POST['status'];
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $last_maintenance = !empty($_POST['last_maintenance']) ? $_POST['last_maintenance'] : null;
    $location = trim($_POST['location']);
    $notes = trim($_POST['notes']);
    
    if (empty($name)) {
        $error = "Equipment name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO equipment (name, category, quantity, status, purchase_date, last_maintenance, location, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$name, $category, $quantity, $status, $purchase_date, $last_maintenance, $location, $notes])) {
            $success = "Equipment added successfully!";
        } else {
            $error = "Failed to add equipment.";
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Equipment deleted successfully!";
    } else {
        $error = "Failed to delete equipment.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_equipment'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $quantity = (int)$_POST['quantity'];
    $status = $_POST['status'];
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $last_maintenance = !empty($_POST['last_maintenance']) ? $_POST['last_maintenance'] : null;
    $location = trim($_POST['location']);
    $notes = trim($_POST['notes']);
    
    if (empty($name)) {
        $error = "Equipment name is required.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE equipment 
            SET name = ?, category = ?, quantity = ?, status = ?, 
                purchase_date = ?, last_maintenance = ?, location = ?, notes = ?
            WHERE id = ?
        ");
        if ($stmt->execute([$name, $category, $quantity, $status, $purchase_date, $last_maintenance, $location, $notes, $id])) {
            $success = "Equipment updated successfully!";
        } else {
            $error = "Failed to update equipment.";
        }
    }
}

$query = "SELECT * FROM equipment WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR category LIKE ? OR location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_category) && $filter_category != 'all') {
    $query .= " AND category = ?";
    $params[] = $filter_category;
}

if (!empty($filter_status) && $filter_status != 'all') {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY id ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$equipment_list = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipment");
$total = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM equipment GROUP BY status");
$status_stats = [];
while ($row = $stmt->fetch()) {
    $status_stats[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Equipment Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #111;
            color: #fff;
        }
        .navbar {
            background-color: #1a1a1a;
            border-bottom: 1px solid #333;
            padding: 6px;
        }
        .navbar .container {
            max-width: 100%;
            width: 100%;
            padding-left: 0;
            padding-right: 0;
            margin: 0;
        }
        .navbar-brand,
        .navbar-brand:hover,
        .navbar-brand:focus,
        .navbar-brand:active {
            font-weight: bold;
            font-size: 30px;
            color: #d6ff00 !important;
            text-decoration: none;
            padding-left: 15px;
        }
        .nav-link {
            color: #fff !important;
            font-weight: bold;
            text-transform: uppercase;
        }
        .nav-link:hover {
            color: #d6ff00 !important;
        }
        .btn-primary-custom {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
        }
        .btn-primary-custom:hover {
            background-color: #c0e800;
            color: #000;
        }
        .btn-outline-custom {
            border: 2px solid #d6ff00;
            color: #d6ff00;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            background-color: transparent;
        }
        .btn-outline-custom:hover {
            background-color: #d6ff00;
            color: #000;
        }
        .btn-danger-custom {
            background-color: #ef4444;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
        }
        .btn-warning-custom {
            background-color: #f59e0b;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
        }
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .stat-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #d6ff00;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #aaa;
            text-transform: uppercase;
        }
        .table-dark {
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }
        .table-dark td, .table-dark th {
            border-color: #333;
            color: #ddd;
            text-align: center;
            vertical-align: middle;
        }
        .table-dark th {
            color: #d6ff00;
        }
        .modal-content {
            background-color: #1a1a1a;
            border: 1px solid #333;
        }
        .modal-header {
            border-bottom: 1px solid #333;
        }
        .modal-footer {
            border-top: 1px solid #333;
        }
        .form-control {
            background-color: #2a2a2a;
            border: 1px solid #333;
            color: #fff;
        }
        .form-control:focus {
            background-color: #2a2a2a;
            border-color: #d6ff00;
            color: #fff;
            box-shadow: none;
        }
        .form-select {
            background-color: #2a2a2a;
            border: 1px solid #333;
            color: #fff;
        }
        .form-select:focus {
            border-color: #d6ff00;
            box-shadow: none;
        }
        textarea.form-control {
            resize: vertical;
        }
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
        h1 {
            color: #fff;
        }
        .text-muted {
            color: #aaa !important;
        }
        .search-filter-bar {
            background-color: #1a1a1a;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-available {
            background-color: #22c55e;
            color: #fff;
        }
        .status-in_use {
            background-color: #f59e0b;
            color: #000;
        }
        .status-maintenance {
            background-color: #3b82f6;
            color: #fff;
        }
        .status-broken {
            background-color: #ef4444;
            color: #fff;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="index.php">SUPERGYM</a>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="staff_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php" style="color: #d6ff00 !important;">Equipment</a></li>
                <li class="nav-item"><a class="nav-link" href="gym_capacity.php">Gym Capacity</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Account</a></li>
            </ul>
            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h1>Equipment Management</h1>
            <p class="text-muted">Track and manage gym equipment inventory</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">+ Add Equipment</button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total; ?></div>
                <div class="stat-label">Total Items</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_stats['available'] ?? 0; ?></div>
                <div class="stat-label">Available</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_stats['maintenance'] ?? 0; ?></div>
                <div class="stat-label">Maintenance</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_stats['broken'] ?? 0; ?></div>
                <div class="stat-label">Broken</div>
            </div>
        </div>
    </div>

    <div class="search-filter-bar">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, category or location..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Filter by Category</label>
                <select name="filter_category" class="form-select">
                    <option value="all" <?php echo $filter_category == 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $filter_category == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Filter by Status</label>
                <select name="filter_status" class="form-select">
                    <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="available" <?php echo $filter_status == 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="in_use" <?php echo $filter_status == 'in_use' ? 'selected' : ''; ?>>In Use</option>
                    <option value="maintenance" <?php echo $filter_status == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="broken" <?php echo $filter_status == 'broken' ? 'selected' : ''; ?>>Broken</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary-custom w-100">Search</button>
            </div>
        </form>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Last Maintenance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($equipment_list) > 0): ?>
                    <?php foreach($equipment_list as $item): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $item['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($item['location']); ?></td>
                            <td><?php echo $item['last_maintenance'] ? date('d M Y', strtotime($item['last_maintenance'])) : '-'; ?></td>
                            <td>
                                <button class="btn btn-warning-custom" data-bs-toggle="modal" data-bs-target="#editEquipmentModal" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-category="<?php echo htmlspecialchars($item['category']); ?>"
                                    data-quantity="<?php echo $item['quantity']; ?>"
                                    data-status="<?php echo $item['status']; ?>"
                                    data-purchase_date="<?php echo $item['purchase_date']; ?>"
                                    data-last_maintenance="<?php echo $item['last_maintenance']; ?>"
                                    data-location="<?php echo htmlspecialchars($item['location']); ?>"
                                    data-notes="<?php echo htmlspecialchars($item['notes'] ?? ''); ?>">Edit</button>
                                <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-danger-custom" onclick="return confirm('Are you sure you want to delete this equipment?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No equipment found.<?php echo htmlspecialchars($search); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addEquipmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Equipment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Equipment Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="Cardio">Cardio</option>
                                <option value="Strength">Strength</option>
                                <option value="Accessories">Accessories</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="broken">Broken</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g., Cardio Area, Studio 1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Maintenance Date</label>
                            <input type="date" name="last_maintenance" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any additional information..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_equipment" class="btn btn-primary-custom">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editEquipmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Equipment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Equipment Name *</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" id="edit_category" class="form-select">
                                <option value="Cardio">Cardio</option>
                                <option value="Strength">Strength</option>
                                <option value="Accessories">Accessories</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="broken">Broken</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Maintenance Date</label>
                            <input type="date" name="last_maintenance" id="edit_last_maintenance" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_equipment" class="btn btn-primary-custom">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const editModal = document.getElementById('editEquipmentModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit_id').value = button.getAttribute('data-id');
        document.getElementById('edit_name').value = button.getAttribute('data-name');
        document.getElementById('edit_category').value = button.getAttribute('data-category');
        document.getElementById('edit_quantity').value = button.getAttribute('data-quantity');
        document.getElementById('edit_status').value = button.getAttribute('data-status');
        document.getElementById('edit_purchase_date').value = button.getAttribute('data-purchase_date');
        document.getElementById('edit_last_maintenance').value = button.getAttribute('data-last_maintenance');
        document.getElementById('edit_location').value = button.getAttribute('data-location');
        document.getElementById('edit_notes').value = button.getAttribute('data-notes');
    });
</script>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>