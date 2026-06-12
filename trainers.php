<?php
session_start();
require_once 'include/db.php';

$stmt = $pdo->prepare("
    SELECT t.trainer_id as trainer_id, u.id as user_id, u.name as trainer_name, 
           t.specialty,
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
$trainer_images = [
    1 => 'https://images.pexels.com/photos/1552108/pexels-photo-1552108.jpeg',
    2 => 'https://images.pexels.com/photos/3768695/pexels-photo-3768695.jpeg',
    3 => 'https://images.pexels.com/photos/4761788/pexels-photo-4761788.jpeg',
    4 => 'https://images.pexels.com/photos/4944955/pexels-photo-4944955.jpeg',
    5 => 'https://images.pexels.com/photos/4720822/pexels-photo-4720822.jpeg',
    6 => 'https://images.pexels.com/photos/25596680/pexels-photo-25596680.jpeg',
    7 => 'https://images.pexels.com/photos/8612030/pexels-photo-8612030.jpeg',
    8 => 'https://images.pexels.com/photos/4164771/pexels-photo-4164771.jpeg',
    9 => 'https://images.pexels.com/photos/8957624/pexels-photo-8957624.jpeg',
    10 => 'https://images.pexels.com/photos/13993704/pexels-photo-13993704.jpeg',
    11 => 'https://images.pexels.com/photos/4976936/pexels-photo-4976936.jpeg',
    12 => 'https://images.pexels.com/photos/13896072/pexels-photo-13896072.jpeg',
    13 => 'https://images.pexels.com/photos/4944983/pexels-photo-4944983.jpeg',
    14 => 'https://images.pexels.com/photos/4534667/pexels-photo-4534667.jpeg',
    15 => 'https://images.pexels.com/photos/3931374/pexels-photo-3931374.jpeg'
];

$fallback_image = 'https://randomuser.me/api/portraits/lego/1.jpg';

$specialty_icons = [
    'General Fitness' => 'fa-heartbeat',
    'Zumba Dance' => 'fa-music',
    'Boxing & Kickboxing' => 'fa-fist-raised',
    'Powerlifting' => 'fa-dumbbell',
    'CrossFit' => 'fa-fire',
    'Pilates & Core' => 'fa-spa',
    'Muay Thai' => 'fa-fist-raised',
    'Dance Fitness' => 'fa-music',
    'Strength Training' => 'fa-dumbbell',
    'Functional Training' => 'fa-cogs',
    'HIIT Training' => 'fa-bolt',
    'Marathon Prep' => 'fa-running',
    'Cardio Training' => 'fa-heartbeat',
    'Yoga & Stretch' => 'fa-praying-hands',
    'Body Shaping' => 'fa-dumbbell'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Our Trainers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #111;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            padding: 8px 16px;
            text-transform: uppercase;
            transition: color 0.3s;
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
            transition: all 0.3s;
        }
        .btn-primary-custom:hover {
            background-color: #c0e800;
            color: #000;
            transform: translateY(-2px);
        }
        .btn-outline-custom {
            border: 2px solid #d6ff00;
            color: #d6ff00;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            background-color: transparent;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover {
            background-color: #d6ff00;
            color: #000;
        }
        .trainers-hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1571902943202-507ec2618e8f?q=80&w=2070&auto=format');
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            text-align: center;
        }
        .trainers-hero h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .trainers-hero h1 span {
            color: #d6ff00;
        }
        .trainers-hero p {
            color: #ccc;
            font-size: 1.1rem;
        }

        .trainer-section {
            padding: 80px 0;
            background-color: #111;
        }
        .trainer-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }
        .trainer-card:hover {
            transform: translateY(-5px);
            border-color: #d6ff00;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .trainer-image {
            height: 280px;
            overflow: hidden;
            position: relative;
        }
        .trainer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .trainer-card:hover .trainer-image img {
            transform: scale(1.05);
        }
        .trainer-info {
            padding: 20px;
            text-align: center;
            background-color: #1a1a1a;
        }
        .trainer-name {
            color: #d6ff00;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .trainer-specialty {
            display: inline-block;
            background-color: rgba(214, 255, 0, 0.15);
            color: #d6ff00;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 30px;
            margin-bottom: 12px;
        }
        .trainer-specialty i {
            margin-right: 6px;
            font-size: 0.7rem;
        }
        .slot-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #252525;
            padding: 10px;
            border-radius: 10px;
            margin: 12px 0;
        }
        .slot-icon {
            font-size: 0.9rem;
            color: #d6ff00;
        }
        .slot-text {
            font-size: 0.8rem;
            color: #ccc;
            font-weight: 500;
        }
        .slot-available {
            color: #22c55e;
            font-weight: bold;
        }
        .slot-none {
            color: #6b7280;
        }
        .btn-view-slots {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-view-slots:hover {
            background-color: #c0e800;
            color: #000;
            transform: translateY(-2px);
        }
        .modal-content {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
        }
        .modal-header {
            border-bottom: 1px solid #333;
            padding: 15px 20px;
        }
        .modal-header .modal-title {
            color: #d6ff00;
            font-weight: bold;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-body {
            padding: 20px;
        }
        .slot-item {
            background-color: #252525;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #333;
            transition: all 0.3s;
        }
        .slot-item:hover {
            border-color: #d6ff00;
        }
        .slot-date {
            color: #fff;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .slot-time {
            color: #d6ff00;
            font-size: 0.85rem;
        }
        .btn-book-slot {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s;
        }
        .btn-book-slot:hover {
            background-color: #c0e800;
            transform: scale(1.02);
        }
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.2);
            border: 1px solid #f59e0b;
            color: #fff;
        }
        .alert-warning a {
            color: #d6ff00;
        }
        .modal-footer {
            border-top: 1px solid #333;
            padding: 15px 20px;
        }
        .modal-footer .btn-secondary {
            background-color: #444;
            border: none;
            color: #fff;
        }
        .modal-footer .btn-secondary:hover {
            background-color: #555;
        }

        @media (max-width: 768px) {
            .trainers-hero h1 {
                font-size: 2rem;
            }
            .trainer-image {
                height: 220px;
            }
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
                <li class="nav-item"><a class="nav-link" href="classes.php">Gym Sessions</a></li>
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

<section class="trainers-hero">
    <div class="container">
        <h1>Meet Our <span>Elite Trainers</span></h1>
        <p>World-class coaches dedicated to helping you reach your peak performance</p>
    </div>
</section>

<section class="trainer-section">
    <div class="container">
        <div class="row g-4">
            <?php if(count($trainers) > 0): ?>
                <?php foreach($trainers as $index => $trainer): ?>
                    <?php
                    $has_slots = $trainer['available_slots'] > 0;
                    $slot_text = $has_slots ? $trainer['available_slots'] . ' slots available' : 'No slots available';
                    $slot_class = $has_slots ? 'slot-available' : 'slot-none';
                    $trainer_image = isset($trainer_images[$trainer['trainer_id']]) ? $trainer_images[$trainer['trainer_id']] : $fallback_image;
                    $specialty = $trainer['specialty'] ?? 'Fitness Coach';
                    $icon = isset($specialty_icons[$specialty]) ? $specialty_icons[$specialty] : 'fa-dumbbell';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="trainer-card">
                            <div class="trainer-image">
                                <img src="<?php echo $trainer_image; ?>" 
                                     alt="<?php echo htmlspecialchars($trainer['trainer_name']); ?>"
                                     onerror="this.src='<?php echo $fallback_image; ?>'">
                            </div>
                            <div class="trainer-info">
                                <h3 class="trainer-name"><?php echo htmlspecialchars($trainer['trainer_name']); ?></h3>
                                <div class="trainer-specialty">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <?php echo htmlspecialchars($specialty); ?>
                                </div>
                                <div class="slot-info">
                                    <i class="fas fa-calendar-alt slot-icon"></i>
                                    <span class="slot-text <?php echo $slot_class; ?>"><?php echo $slot_text; ?></span>
                                </div>
                                <button class="btn-view-slots" onclick="showSlots(<?php echo $trainer['trainer_id']; ?>, '<?php echo htmlspecialchars($trainer['trainer_name']); ?>', '<?php echo htmlspecialchars($specialty); ?>')">
                                    <i class="fas fa-clock"></i> View Schedule <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">Trainer profiles coming soon!</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="modal fade" id="slotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrainerName">Trainer Name</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center py-4">
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
    document.getElementById('modalTrainerName').innerHTML = trainerName + ' <span style="color: #d6ff00;">(' + specialty + ')</span>';
    document.getElementById('modalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
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
                                <div class="slot-date"><i class="fas fa-calendar-day me-2"></i>${slot.date}</div>
                                <div class="slot-time"><i class="fas fa-clock me-2"></i>${slot.start_time} - ${slot.end_time}</div>
                            </div>
                            ${data.is_logged_in ? 
                                `<a href="book_trainer.php?slot_id=${slot.id}" class="btn-book-slot"><i class="fas fa-bookmark me-1"></i> Book</a>` :
                                `<a href="login.php?redirect=book_trainer.php&slot_id=${slot.id}" class="btn-book-slot"><i class="fas fa-sign-in-alt me-1"></i> Join to Book</a>`
                            }
                        </div>
                    `;
                });
                html += '</div>';
                if (!data.is_logged_in) {
                    html += '<div class="alert alert-warning mt-3 text-center"><i class="fas fa-lock me-2"></i> Please <a href="login.php" class="text-warning">login</a> or <a href="register.php" class="text-warning">register</a> to book a session.</div>';
                }
                document.getElementById('modalBody').innerHTML = html;
            } else {
                document.getElementById('modalBody').innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-calendar-times me-2"></i>No available slots for this trainer at the moment.<br>Please check back later.</div>';
            }
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = '<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading slots. Please try again.</div>';
        });
}
</script>
</body>
</html>