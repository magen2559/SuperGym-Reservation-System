<?php
session_start();
require_once 'include/db.php';

$stmt = $pdo->prepare("
    SELECT t.trainer_id as trainer_id, u.id as user_id, u.name as trainer_name, 
           t.specialty, t.bio,
           (SELECT COUNT(*) FROM trainer_slots 
            WHERE trainer_id = t.trainer_id 
            AND slot_date >= CURDATE() 
            AND is_available = 1) as available_slots
    FROM trainers t
    JOIN users u ON t.user_id = u.id
    WHERE u.role = 'trainer'
    ORDER BY t.trainer_id
");
$stmt->execute();
$trainers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Our Trainers</title>
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
            color: #aaa;
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
        .hero-small {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1571902943202-507ec2618e8f?q=80&w=2070&auto=format');
            background-size: cover;
            background-position: center;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        .trainer-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            transition: all 0.3s ease;
            height: 100%;
            text-align: center;
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .trainer-card:hover {
            transform: translateY(-5px);
            border-color: #d6ff00;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        .trainer-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #d6ff00 0%, #a0cc00 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.8rem;
            font-weight: bold;
            color: #000;
        }
        .trainer-name {
            color: #d6ff00;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .trainer-specialty {
            font-size: 0.75rem;
            color: #fff;
            background-color: rgba(214, 255, 0, 0.15);
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
        .trainer-bio {
            color: #aaa;
            font-size: 0.75rem;
            margin: 8px 0;
            line-height: 1.4;
        }
        .slot-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            margin-top: 5px;
        }
        .slot-available {
            background-color: #22c55e;
            color: #fff;
        }
        .slot-none {
            background-color: #6b7280;
            color: #fff;
        }
        .btn-view-slots {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 6px 20px;
            border-radius: 8px;
            font-size: 0.75rem;
            margin-top: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            width: auto;
            cursor: pointer;
        }
        .btn-view-slots:hover {
            background-color: #c0e800;
            color: #000;
            text-decoration: none;
            transform: scale(1.02);
        }
        .modal-dialog {
            border: none;
        }
        .modal-content {
            background-color: #1a1a1a;
            border: 3px solid #d6ff00 !important;
            border-radius: 16px !important;
            box-shadow: 0 0 25px rgba(214, 255, 0, 0.4);
            overflow: hidden;
        }
        .modal-header {
            border-bottom: 1px solid #444;
            background-color: #222;
        }
        .modal-header .modal-title {
            color: #d6ff00;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-body {
            background-color: #1a1a1a;
        }
        .modal-body .text-muted {
            color: #ffffff !important;
        }
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.2);
            border: 1px solid #f59e0b;
            color: #ffffff !important;
        }
        .alert-warning a {
            color: #d6ff00;
        }
        .slot-item {
            background-color: #2a2a2a;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #444;
        }
        .slot-item:hover {
            background-color: #3a3a3a;
            border-color: #d6ff00;
        }
        .slot-date {
            color: #ffffff !important;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .slot-time {
            color: #d6ff00 !important;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .btn-book-slot {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            padding: 6px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-book-slot:hover {
            background-color: #c0e800;
            color: #000;
            transform: scale(1.02);
        }
        .modal-footer {
            border-top: 1px solid #444;
            background-color: #1a1a1a;
        }
        .modal-footer .btn-secondary {
            background-color: #444;
            border: none;
            color: #fff;
        }
        .modal-footer .btn-secondary:hover {
            background-color: #555;
        }
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">SUPERGYM</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="classes.php">Classes</a></li>
                <li class="nav-item"><a class="nav-link" href="trainers.php" style="color: #d6ff00 !important;">Trainers</a></li>
            </ul>
            <div class="ms-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary-custom me-2">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline-custom">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-custom me-2">Login</a>
                    <a href="register.php" class="btn btn-primary-custom">Join Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="hero-small">
    <div class="container">
        <h1>Meet Our Trainers</h1>
        <p class="lead">Expert coaches ready to help you achieve your goals</p>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <?php if(count($trainers) > 0): ?>
            <?php foreach($trainers as $index => $trainer): ?>
                <?php
                $avatar_letter = strtoupper(substr($trainer['trainer_name'], 0, 1));
                $has_slots = $trainer['available_slots'] > 0;
                $slot_text = $has_slots ? $trainer['available_slots'] . ' slots available' : 'No slots available';
                $slot_class = $has_slots ? 'slot-available' : 'slot-none';
                ?>
                <div class="col-md-4 mb-4">
                    <div class="trainer-card">
                        <div class="trainer-avatar"><?php echo $avatar_letter; ?></div>
                        <h3 class="trainer-name"><?php echo htmlspecialchars($trainer['trainer_name']); ?></h3>
                        <div class="trainer-specialty"><?php echo htmlspecialchars($trainer['specialty'] ?? 'Fitness Coach'); ?></div>
                        <p class="trainer-bio"><?php echo htmlspecialchars(substr($trainer['bio'] ?? 'Certified personal trainer with years of experience.', 0, 65)); ?>...</p>
                        <div class="slot-badge <?php echo $slot_class; ?>">📅 <?php echo $slot_text; ?></div>
                        <button class="btn-view-slots" onclick="showSlots(<?php echo $trainer['trainer_id']; ?>, '<?php echo htmlspecialchars($trainer['trainer_name']); ?>', '<?php echo htmlspecialchars($trainer['specialty'] ?? 'Fitness Coach'); ?>')">View Schedule →</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">Trainer profiles coming soon!</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="slotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrainerName">Trainer Name</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center py-3">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showSlots(trainerId, trainerName, specialty) {
    document.getElementById('modalTrainerName').innerHTML = trainerName + ' <small style="color: #d6ff00;">(' + specialty + ')</small>';
    document.getElementById('modalBody').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    var myModal = new bootstrap.Modal(document.getElementById('slotModal'));
    myModal.show();
    
    fetch('get_trainer_slots.php?trainer_id=' + trainerId)
        .then(response => response.json())
        .then(data => {
            if (data.slots && data.slots.length > 0) {
                let html = '<div class="slots-list">';
                data.slots.forEach(slot => {
                    html += `
                        <div class="slot-item">
                            <div>
                                <div class="slot-date">📅 ${slot.date}</div>
                                <div class="slot-time">⏰ ${slot.start_time} - ${slot.end_time}</div>
                            </div>
                            ${data.is_logged_in ? 
                                `<a href="book_trainer.php?slot_id=${slot.id}" class="btn-book-slot">Book Now</a>` :
                                `<a href="login.php?redirect=book_trainer.php&slot_id=${slot.id}" class="btn-book-slot">Join to Book</a>`
                            }
                        </div>
                    `;
                });
                html += '</div>';
                if (!data.is_logged_in) {
                    html += '<div class="alert alert-warning mt-3 text-center">🔐 Please <a href="login.php" class="text-warning">login</a> or <a href="register.php" class="text-warning">register</a> to book a session.</div>';
                }
                document.getElementById('modalBody').innerHTML = html;
                } else {
                    document.getElementById('modalBody').innerHTML = '<div class="text-center py-3 text-muted">No available slots for this trainer at the moment.<br>Please check back later.</div>';
                }
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = '<div class="text-center py-3 text-danger">Error loading slots. Please try again.</div>';
        });
}
</script>
</body>
</html>