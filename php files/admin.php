<?php
session_start();
include 'db.php';

// Allow admin and admin_limited
if (!isset($_SESSION['user_id']) || 
   ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'admin_limited')) {
    header("Location: login.php");
    exit;
}

$isSuperAdmin = ($_SESSION['role'] === 'admin');

// ------------------ ADD CANDIDATE ------------------
if (isset($_POST['add_candidate'])) {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $election_year = trim($_POST['election_year'] ?? '');
    $election_date = trim($_POST['election_date'] ?? '');
    
    if (!empty($name) && !empty($position) && !empty($election_year) && !empty($election_date) && isset($_FILES['photo'])) {
        $photo_dir = 'uploads/';
        if (!is_dir($photo_dir)) mkdir($photo_dir, 0755, true);
        
        $photo_name = time() . '_' . basename($_FILES['photo']['name']);
        $photo_path = $photo_dir . $photo_name;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            try {
                $sql = "INSERT INTO candidates (name, position, election_year, election_date, photo)
                    VALUES (:name, :position, :year, :election_date, :photo)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':position' => $position,
                    ':year' => $election_year,
                    ':election_date' => $election_date,
                    ':photo' => $photo_path
                ]);
                header("Location: admin.php?success=1");
                exit;
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error adding candidate: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// ------------------ DELETE CANDIDATE ------------------
if (isset($_GET['delete_candidate']) && $isSuperAdmin) {
    $id = $_GET['delete_candidate'];
    try {
        $stmt = $conn->prepare("DELETE FROM candidates WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: admin.php?delete_success=1");
        exit;
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error deleting candidate: " . $e->getMessage() . "</p>";
    }
}

// ------------------ DELETE USER ------------------
if (isset($_GET['delete_user']) && $isSuperAdmin) {
    $id = $_GET['delete_user'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: admin.php?delete_success=1");
        exit;
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error deleting user: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f4f4f4; }
        .container { max-width:1200px; margin:20px auto; background:white; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        .header-section { display:flex; justify-content:space-between; align-items:center; background:#007bff; color:white; padding:15px 20px; border-radius:8px 8px 0 0; }
        .header-section h1 { margin:0; font-size:1.8em; }
        .logout-btn { background:#dc3545; color:white; padding:8px 15px; border-radius:4px; text-decoration:none; font-weight:bold; }
        .tab-menu { display:flex; background:#f1f1f1; border-bottom:1px solid #ccc; }
        .tab-menu button { flex:1; padding:12px; cursor:pointer; border:none; background:#eee; font-weight:bold; }
        .tab-menu button.active, .tab-menu button:hover { background:#007bff; color:white; }
        .content-section { padding:20px; }
        .section { display:none; }
        .section.active { display:block; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #ddd; padding:8px; text-align:center; }
        th { background:#f1f1f1; }
        .candidate-img { width:60px; height:60px; border-radius:50%; object-fit:cover; }
        .form-container { background:#f9f9f9; padding:15px; border-radius:8px; margin-bottom:20px; }
        .form-button { background:#28a745; color:white; border:none; padding:10px 15px; border-radius:4px; cursor:pointer; margin-top:10px; }
    </style>
    <script>
        function showSection(section){
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.tab-menu button').forEach(b => b.classList.remove('active'));
            document.getElementById(section).classList.add('active');
            document.getElementById(section+'-btn').classList.add('active');
        }
        window.onload = function(){ showSection('candidates'); }
    </script>
</head>
<body>
<div class="container">
    <div class="header-section">
        <h1>Admin Dashboard</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="tab-menu">
        <button id="candidates-btn" onclick="showSection('candidates')">Candidates</button>
        <button id="users-btn" onclick="showSection('users')">Users</button>
        <button id="results-btn" onclick="showSection('results')">Results</button>
    </div>

    <div class="content-section">

        <!-- Candidates -->
        <div id="candidates" class="section active">
            <h2>All Candidates</h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Candidate Name" required>
                <input type="text" name="position" placeholder="Position" required>
                <input type="text" name="election_year" placeholder="Year" required>
                <input type="date" name="election_date" required>
                <input type="file" name="photo" accept="image/*" required>
                <button type="submit" name="add_candidate" class="form-button">Add Candidate</button>
            </form>

            <?php
            $stmt = $conn->prepare("SELECT id, name, position, election_year, TO_CHAR(election_date, 'YYYY-MM-DD') AS election_date, photo FROM candidates ORDER BY id DESC");
            $stmt->execute();
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($candidates) {
                echo "<table><tr><th>ID</th><th>Name</th><th>Position</th><th>Year</th><th>Date</th><th>Photo</th><th>Action</th></tr>";
                foreach ($candidates as $row) {
                    $delete_url = "admin.php?delete_candidate=" . $row['id'];
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['position']}</td>
                        <td>{$row['election_year']}</td>
                        <td>{$row['election_date']}</td>
                        <td><img src='{$row['photo']}' class='candidate-img'></td>
                        <td>";
                    if($isSuperAdmin){
                        echo "<a href='{$delete_url}' onclick=\"return confirm('Delete candidate?');\" style='color:red;'>Delete</a>";
                    } else {
                        echo "—";
                    }
                    echo "</td></tr>";
                }
                echo "</table>";
            }
            ?>
        </div>

        <!-- Users -->
        <div id="users" class="section">
            <h2>All Users</h2>

            <?php
            $stmt = $conn->prepare("SELECT id, fullname, username, role FROM users ORDER BY id DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($users) {
                echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr>";
                foreach ($users as $user) {
                    $delete_url = "admin.php?delete_user=" . $user['id'];
                    echo "<tr>
                        <td>{$user['id']}</td>
                        <td>{$user['fullname']}</td>
                        <td>{$user['username']}</td>
                        <td>{$user['role']}</td>
                        <td>";
                    if($isSuperAdmin && $user['role'] !== 'admin'){
                        echo "<a href='{$delete_url}' onclick=\"return confirm('Delete user?');\" style='color:red;'>Delete</a>";
                    } else {
                        echo "—";
                    }
                    echo "</td></tr>";
                }
                echo "</table>";
            }
            ?>
        </div>

        <!-- Results -->
        <div id="results" class="section">
            <h2>Election Results</h2>
            <p>Results display section remains unchanged.</p>
        </div>

    </div>
</div>
</body>
</html>