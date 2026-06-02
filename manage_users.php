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
$filter_role = isset($_GET['filter_role']) ? $_GET['filter_role'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                $user_id = $pdo->lastInsertId();
                
                if ($role == 'trainer') {
                    $stmt = $pdo->prepare("INSERT INTO trainers (user_id, specialty, bio) VALUES (?, 'Fitness Coach', 'Professional trainer')");
                    $stmt->execute([$user_id]);
                }
                
                $success = "User added successfully!";
            } else {
                $error = "Failed to add user.";
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $success = "User deleted successfully!";
        } else {
            $error = "Failed to delete user.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    if (empty($name) || empty($email)) {
        $error = "Name and email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $role, $user_id])) {
            $success = "User updated successfully!";
        } else {
            $error = "Failed to update user.";
        }
    }
}

$query = "
    SELECT u.id, u.name, u.email, u.role, u.created_at
    FROM users u
    WHERE u.role IN ('member', 'trainer', 'staff')
";

$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_role) && $filter_role != 'all') {
    $query .= " AND u.role = ?";
    $params[] = $filter_role;
}

$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
$total_members = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
$total_trainers = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'");
$total_staff = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Manage Users</title>
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
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #d6ff00;
        }
        .table-dark {
            background-color: #1a1a1a;
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
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .role-member {
            background-color: #22c55e;
            color: #fff;
        }
        .role-trainer {
            background-color: #f59e0b;
            color: #000;
        }
        .role-staff {
            background-color: #3b82f6;
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
                <li class="nav-item"><a class="nav-link" href="manage_users.php" style="color: #d6ff00 !important;">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php">Equipment</a></li>
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
            <h1>User Management</h1>
            <p class="text-muted">View, search, and manage all system users</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">+ Add New User</button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_members; ?></div>
                <div class="text-muted">Total Members</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_trainers; ?></div>
                <div class="text-muted">Total Trainers</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_staff; ?></div>
                <div class="text-muted">Total Staff</div>
            </div>
        </div>
    </div>

    <div class="search-filter-bar">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Filter by Role</label>
                <select name="filter_role" class="form-select">
                    <option value="all" <?php echo $filter_role == 'all' ? 'selected' : ''; ?>>All Roles</option>
                    <option value="member" <?php echo $filter_role == 'member' ? 'selected' : ''; ?>>Member</option>
                    <option value="trainer" <?php echo $filter_role == 'trainer' ? 'selected' : ''; ?>>Trainer</option>
                    <option value="staff" <?php echo $filter_role == 'staff' ? 'selected' : ''; ?>>Staff</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary-custom w-100">Search</button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="manage_users.php" class="btn btn-outline-custom w-100">Clear</a>
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
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($users) > 0): ?>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?> </td>
                            <td>
                                <button class="btn btn-warning-custom" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                    data-id="<?php echo $user['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-role="<?php echo $user['role']; ?>">Edit</button>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger-custom" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="text-muted">Min 8 chars: Uppercase, Lowercase, Number, Special char</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="member">Member</option>
                            <option value="trainer">Trainer</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary-custom">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="member">Member</option>
                            <option value="trainer">Trainer</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-primary-custom">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit_user_id').value = button.getAttribute('data-id');
        document.getElementById('edit_name').value = button.getAttribute('data-name');
        document.getElementById('edit_email').value = button.getAttribute('data-email');
        document.getElementById('edit_role').value = button.getAttribute('data-role');
    });
</script>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>