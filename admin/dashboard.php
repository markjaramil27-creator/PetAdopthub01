<?php
$page_title = "Admin Dashboard - PetAdoptHub";
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Debug: Log session state
error_log("Admin Dashboard Access Attempt - Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("User Role: " . ($_SESSION['user_role'] ?? 'NOT SET'));

// Check if user is logged in first
if (!isLoggedIn()) {
    error_log("User not logged in - redirecting to login");
    header('Location: ../login.php');
    exit();
}

// Verify role from database to ensure it's not tampered with

$user_id = $_SESSION['user_id'];
// If user_id is 0, it's the hardcoded admin, allow access
if ($user_id === 0) {
    error_log("Hardcoded admin access granted.");
} else {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0 || $result->fetch_assoc()['role'] !== 'admin') {
        error_log("User ID " . $user_id . " is not an admin - redirecting to index");
        header('Location: ../index.php');
        exit();
    }
}

error_log("Admin access granted for user ID: " . $user_id);

// Get dashboard stats
$total_pets = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pets");
$stmt->execute();
$result = $stmt->get_result();
$total_pets = $result->fetch_assoc()['count'];

$pending_apps = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = 'Pending'");
$stmt->execute();
$result = $stmt->get_result();
$pending_apps = $result->fetch_assoc()['count'];

$available_pets = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pets WHERE status = 'Available'");
$stmt->execute();
$result = $stmt->get_result();
$available_pets = $result->fetch_assoc()['count'];

$this_month_apps = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE MONTH(applied_at) = MONTH(NOW()) AND YEAR(applied_at) = YEAR(NOW())");
$stmt->execute();
$result = $stmt->get_result();
$this_month_apps = $result->fetch_assoc()['count'];

// Get recent applications
$stmt = $conn->prepare("
    SELECT a.id, a.status, a.applied_at, u.fullname, p.name 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    JOIN pets p ON a.pet_id = p.id 
    ORDER BY a.applied_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_apps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        .admin-container {
            display: flex;
            gap: 16px;
            min-height: 100vh;
            padding: 16px;
            align-items: flex-start;
        }
        .sidebar {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            width: 220px;
            height: fit-content;
            position: sticky;
            top: 16px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            display: none;
        }
        .sidebar-logo {
            color: #111;
            font-size: 18px;
            margin-bottom: 18px;
            font-weight: 700;
        }
        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 8px;
        }
        .sidebar-menu li {
            margin-bottom: 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 8px;
            border-radius: 8px;
            text-decoration: none;
            color: #777;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .sidebar-menu a:hover {
            color: #333;
            background-color: rgba(255, 138, 91, 0.05);
        }
        .sidebar-menu a.active {
            color: #333;
            background-color: rgba(255, 138, 91, 0.1);
            font-weight: 600;
        }
        .sidebar-menu i {
            font-size: 16px;
            width: 20px;
            color: #bbb;
        }
        .sidebar-menu a.active i {
            color: #ff8a5b;
        }
        .sidebar-menu a:hover i {
            color: #ff8a5b;
        }
        .sign-out-section {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .sign-out-section a {
            color: #ff6b42 !important;
            font-weight: 600 !important;
        }
        .sign-out-section i {
            color: #ff6b42 !important;
        }
        .main-content {
            flex: 1;
            background-color: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            min-height: calc(100vh - 32px);
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a3a52;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        .stat-card {
            padding: 25px;
            border-radius: 12px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-card-cyan {
            background-color: #7dd4dd;
        }
        .stat-card-orange {
            background-color: #ffb399;
        }
        .stat-card-green {
            background-color: #a8d9a8;
        }
        .stat-card-pink {
            background-color: #ffb3d9;
        }
        .stat-content h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        .stat-content p {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }
        .stat-icon {
            font-size: 32px;
            opacity: 0.8;
        }
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a3a52;
            margin-bottom: 25px;
        }
        .applications-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .app-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            background: #ffffff;
            border-radius: 12px;
            color: #222;
            font-size: 15px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.14);
            transition: box-shadow 0.25s ease, background-color 0.25s ease;
            border: 1px solid #f0f0f0;
        }

        .app-item:hover {
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
            background-color: #f9fafb;
        }
        .app-info h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .app-info p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending {
            background-color: #ffb399;
            color: white;
        }
        .status-approved {
            background-color: #a8d9a8;
            color: white;
        }
        .status-rejected {
            background-color: #ff9999;
            color: white;
        }

        @media (max-width: 900px) {
            .admin-container {
                flex-direction: column;
                gap: 12px;
                padding: 12px;
            }
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            .main-content {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">Admin Panel</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active" style="background-color: #ffe8d6;">
                        <img src="/images/iconhouse.png" alt="house" style="width:20px; height:20px; filter:invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg);">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_pets.php">
                        <img src="/images/iconFavorite.png" alt="iconFavorite" style="width:20px; height:20px; opacity:0.73;">
                        Pets
                    </a>
                </li>
                <li>
                    <a href="archive.php">
                        <img src="/images/iconarchive.png" alt="iconarchive" style="width:20px; height:20px; opacity:0.73;">
                        Archive
                    </a>
                </li>
                <li>
                    <a href="applications.php">
                        <img src="/images/icondocuments.png" alt="icondocuments" style="width:20px; height:20px; opacity:0.73;">
                        Application
                    </a>
                </li>
                <li>
                    <a href="chat.php">
                        <img src="/images/iconchat.png" alt="iconchat" style="width:20px; height:20px; opacity:0.73;">
                        Messages
                    </a>
                </li>
            </ul>
            <div style="flex:1"></div>
            <div class="sign-out-section">
                <a href="../api/logout.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-radius: 8px; text-decoration: none;">
                    <img src="/images/iconlogout.png" alt="iconlogout" style="width:20px; height:20px; opacity:0.73;">
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h1 class="page-title">Dashboard Overview</h1>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card stat-card-cyan">
                    <div class="stat-content">
                        <h3>Total Pets</h3>
                        <p><?php echo $total_pets; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                </div>

                <div class="stat-card stat-card-orange">
                    <div class="stat-content">
                        <h3>Pending Applications</h3>
                        <p><?php echo $pending_apps; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clipboard"></i>
                    </div>
                </div>

                <div class="stat-card stat-card-green">
                    <div class="stat-content">
                        <h3>Available</h3>
                        <p><?php echo $available_pets; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <div class="stat-card stat-card-pink">
                    <div class="stat-content">
                        <h3>This Month</h3>
                        <p><?php echo $this_month_apps; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <h2 class="section-title">Recent Applications</h2>
            <div class="applications-list">
                <?php 
                if (!empty($recent_apps)) {
                    foreach ($recent_apps as $app) {
                        $status_class = strtolower($app['status']);
                        if ($status_class === 'pending') {
                            $status_class = 'status-pending';
                        } elseif ($status_class === 'approved') {
                            $status_class = 'status-approved';
                        } elseif ($status_class === 'rejected') {
                            $status_class = 'status-rejected';
                        }
                ?>
                <div class="app-item">
                    <div class="app-info">
                        <h4><?php echo htmlspecialchars($app['fullname']); ?></h4>
                        <p>Applying for <?php echo htmlspecialchars($app['name']); ?></p>
                    </div>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($app['status']); ?>
                    </span>
                </div>
                <?php
                    }
                } else {
                    echo '<p style="color: #999; text-align: center; padding: 40px;">No recent applications</p>';
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>
