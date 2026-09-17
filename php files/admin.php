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
    $biography = trim($_POST['biography'] ?? '');
    $manifesto = trim($_POST['manifesto'] ?? '');
    
    if (!empty($name) && !empty($position) && !empty($election_year) && !empty($election_date) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        
        // Soma binary data na MIME kabla ya kuhamisha faili
        $tmp_name = $_FILES['photo']['tmp_name'];
        $photo_data = file_get_contents($tmp_name);
        $photo_mime = mime_content_type($tmp_name);
        
        $photo_dir = __DIR__ . '/uploads/';
        if (!is_dir($photo_dir)) mkdir($photo_dir, 0755, true);
        
        $original_name = basename($_FILES['photo']['name']);
        $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
        $photo_name = time() . '_' . $safe_name;
        $photo_path = 'uploads/' . $photo_name;
        
        if (move_uploaded_file($tmp_name, $photo_dir . $photo_name)) {
            try {
                $sql = "INSERT INTO candidates (name, position, election_year, election_date, photo, photo_data, photo_mime, biography, manifesto)
                    VALUES (:name, :position, :year, :election_date, :photo, :photo_data, :photo_mime, :biography, :manifesto)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                $stmt->bindValue(':position', $position, PDO::PARAM_STR);
                $stmt->bindValue(':year', $election_year, PDO::PARAM_STR);
                $stmt->bindValue(':election_date', $election_date, PDO::PARAM_STR);
                $stmt->bindValue(':photo', $photo_path, PDO::PARAM_STR);
                $stmt->bindValue(':photo_data', $photo_data, PDO::PARAM_LOB);
                $stmt->bindValue(':photo_mime', $photo_mime, PDO::PARAM_STR);
                $stmt->bindValue(':biography', $biography, PDO::PARAM_STR);
                $stmt->bindValue(':manifesto', $manifesto, PDO::PARAM_STR);
                $stmt->execute();
                
                header("Location: admin.php?success=1");
                exit;
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error adding candidate: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// ------------------ EDIT CANDIDATE ------------------
if (isset($_POST['update_candidate'])) {
    $candidate_id = filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $election_year = trim($_POST['election_year'] ?? '');
    $election_date = trim($_POST['election_date'] ?? '');
    $biography = trim($_POST['biography'] ?? '');
    $manifesto = trim($_POST['manifesto'] ?? '');

    if ($candidate_id && $name !== '' && $position !== '' && $election_year !== '' && $election_date !== '') {
        try {
            $photo_upload = isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK;
            if ($photo_upload) {
                $tmp_name = $_FILES['photo']['tmp_name'];
                $photo_data = file_get_contents($tmp_name);
                $photo_mime = mime_content_type($tmp_name) ?: 'application/octet-stream';
                $photo_dir = __DIR__ . '/uploads/';
                if (!is_dir($photo_dir)) mkdir($photo_dir, 0755, true);

                $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['photo']['name']));
                $photo_name = time() . '_' . $safe_name;
                $photo_path = 'uploads/' . $photo_name;

                if (!move_uploaded_file($tmp_name, $photo_dir . $photo_name)) {
                    throw new RuntimeException('The new photo could not be uploaded.');
                }

                $sql = "UPDATE candidates
                    SET name = :name, position = :position, election_year = :year,
                        election_date = :election_date, photo = :photo, photo_data = :photo_data,
                        photo_mime = :photo_mime, biography = :biography, manifesto = :manifesto
                    WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':photo', $photo_path, PDO::PARAM_STR);
                $stmt->bindValue(':photo_data', $photo_data, PDO::PARAM_LOB);
                $stmt->bindValue(':photo_mime', $photo_mime, PDO::PARAM_STR);
            } else {
                $sql = "UPDATE candidates
                    SET name = :name, position = :position, election_year = :year,
                        election_date = :election_date, biography = :biography, manifesto = :manifesto
                    WHERE id = :id";
                $stmt = $conn->prepare($sql);
            }

            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':position', $position, PDO::PARAM_STR);
            $stmt->bindValue(':year', $election_year, PDO::PARAM_STR);
            $stmt->bindValue(':election_date', $election_date, PDO::PARAM_STR);
            $stmt->bindValue(':biography', $biography, PDO::PARAM_STR);
            $stmt->bindValue(':manifesto', $manifesto, PDO::PARAM_STR);
            $stmt->bindValue(':id', $candidate_id, PDO::PARAM_INT);
            $stmt->execute();

            header('Location: admin.php?candidate_updated=1');
            exit;
        } catch (Throwable $e) {
            echo "<p style='color:red'>Error updating candidate: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

$edit_candidate = null;
if (isset($_GET['edit_candidate'])) {
    $edit_id = filter_input(INPUT_GET, 'edit_candidate', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $edit_stmt = $conn->prepare('SELECT id, name, position, election_year, election_date, biography, manifesto FROM candidates WHERE id = ?');
        $edit_stmt->execute([$edit_id]);
        $edit_candidate = $edit_stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (isset($_POST['save_settings']) && $isSuperAdmin) {
    $settings_stmt = $conn->prepare('UPDATE election_settings SET institution_name = ?, election_title = ?, starts_at = ?, ends_at = ?, results_visible = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1');
    $settings_stmt->execute([
        trim($_POST['institution_name'] ?? 'DIT'),
        trim($_POST['election_title'] ?? 'Student Online Voting System'),
        str_replace('T', ' ', $_POST['starts_at'] ?? ''),
        str_replace('T', ' ', $_POST['ends_at'] ?? ''),
        isset($_POST['results_visible'])
    ]);
    header('Location: admin.php?settings_saved=1');
    exit;
}

if (isset($_POST['save_eligibility']) && $isSuperAdmin) {
    $eligibility_stmt = $conn->prepare('UPDATE users SET is_eligible = ? WHERE id = ? AND role = ?');
    $eligibility_stmt->execute([isset($_POST['is_eligible']), (int) $_POST['user_id'], 'user']);
    header('Location: admin.php?eligibility_saved=1');
    exit;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <?php if ($isSuperAdmin): ?><button id="settings-btn" onclick="showSection('settings')">Election Settings</button><?php endif; ?>
        <button id="users-btn" onclick="showSection('users')">Users</button>
        <button id="results-btn" onclick="showSection('results')">Results</button>
    </div>

    <div class="content-section">

        <!-- Candidates -->
        <div id="candidates" class="section active">
            <h2>All Candidates</h2>

            <?php if ($edit_candidate): ?>
            <div class="form-container">
                <h3>Edit Candidate</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="candidate_id" value="<?php echo (int) $edit_candidate['id']; ?>">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_candidate['name']); ?>" placeholder="Candidate Name" required>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($edit_candidate['position']); ?>" placeholder="Position" required>
                    <input type="text" name="election_year" value="<?php echo htmlspecialchars($edit_candidate['election_year']); ?>" placeholder="Year" required>
                    <input type="date" name="election_date" value="<?php echo htmlspecialchars($edit_candidate['election_date']); ?>" required>
                    <input type="file" name="photo" accept="image/*">
                    <textarea name="biography" placeholder="Candidate biography"><?php echo htmlspecialchars($edit_candidate['biography'] ?? ''); ?></textarea>
                    <textarea name="manifesto" placeholder="Candidate manifesto"><?php echo htmlspecialchars($edit_candidate['manifesto'] ?? ''); ?></textarea>
                    <button type="submit" name="update_candidate" class="form-button">Save Candidate</button>
                    <a href="admin.php">Cancel</a>
                </form>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Candidate Name" required>
                <input type="text" name="position" placeholder="Position" required>
                <input type="text" name="election_year" placeholder="Year" required>
                <input type="date" name="election_date" required>
                <input type="file" name="photo" accept="image/*" required>
                <textarea name="biography" placeholder="Candidate biography"></textarea>
                <textarea name="manifesto" placeholder="Candidate manifesto"></textarea>
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
                        <td><img src='image.php?id=" . intval($row['id']) . "' class='candidate-img' alt='Candidate photo'></td>
                        <td><a href='admin.php?edit_candidate=" . intval($row['id']) . "'>Edit</a> ";
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

        <?php if ($isSuperAdmin): $settings = $conn->query('SELECT * FROM election_settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC); ?>
        <div id="settings" class="section">
            <h2>Election Settings</h2>
            <form method="POST">
                <input type="text" name="institution_name" value="<?php echo htmlspecialchars($settings['institution_name']); ?>" placeholder="Institution name" required>
                <input type="text" name="election_title" value="<?php echo htmlspecialchars($settings['election_title']); ?>" placeholder="Election title" required>
                <label>Starts at <input type="datetime-local" name="starts_at" value="<?php echo date('Y-m-d\TH:i', strtotime($settings['starts_at'])); ?>" required></label>
                <label>Ends at <input type="datetime-local" name="ends_at" value="<?php echo date('Y-m-d\TH:i', strtotime($settings['ends_at'])); ?>" required></label>
                <label><input type="checkbox" name="results_visible" <?php echo $settings['results_visible'] ? 'checked' : ''; ?>> Show live results</label>
                <button type="submit" name="save_settings" class="form-button">Save Election Settings</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Users -->
        <div id="users" class="section">
            <h2>All Users</h2>

            <?php
            $stmt = $conn->prepare("SELECT id, fullname, username, registration_number, is_eligible, role FROM users ORDER BY id DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($users) {
                echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Registration Number</th><th>Eligible</th><th>Role</th><th>Action</th></tr>";
                foreach ($users as $user) {
                    $delete_url = "admin.php?delete_user=" . $user['id'];
                    echo "<tr>
                        <td>{$user['id']}</td>
                        <td>{$user['fullname']}</td>
                        <td>{$user['username']}</td>
                        <td>{$user['registration_number']}</td>
                        <td>
                            <form method='POST'>
                                <input type='hidden' name='user_id' value='{$user['id']}'>
                                <input type='checkbox' name='is_eligible' onchange='this.form.submit()' " . ($user['is_eligible'] ? 'checked' : '') . ">
                                <input type='hidden' name='save_eligibility' value='1'>
                            </form>
                        </td>
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
            <?php
            $stmt_positions = $conn->prepare("SELECT DISTINCT position FROM candidates ORDER BY position");
            $stmt_positions->execute();
            $positions = $stmt_positions->fetchAll(PDO::FETCH_ASSOC);

            if ($positions) {
                foreach ($positions as $position_row) {
                    $position = $position_row['position'] ?? '';
                    if (!$position) continue;

                    $stmt_results = $conn->prepare("
                       SELECT c.id, c.name, c.photo, COUNT(v.id) AS total_votes
                       FROM candidates c
                       LEFT JOIN votes v ON c.id = v.candidate_id
                       WHERE c.position = ?
                       GROUP BY c.id, c.name, c.photo
                       ORDER BY total_votes DESC, c.name ASC
                    ");
                    $stmt_results->execute([$position]);
                    $results = $stmt_results->fetchAll(PDO::FETCH_ASSOC);

                    echo '<h3>' . htmlspecialchars($position) . '</h3>';
                    if ($results) {
                        echo '<table><tr><th>Candidate</th><th>Photo</th><th>Total Votes</th></tr>';
                        foreach ($results as $result) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($result['name'] ?? '') . '</td>';
                            echo '<td><img src="image.php?id=' . intval($result['id'] ?? 0) . '" class="candidate-img" alt="Candidate photo"></td>';
                            echo '<td><strong>' . intval($result['total_votes'] ?? 0) . '</strong></td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<p>No results available for this position.</p>';
                    }
                }
            } else {
                echo '<p>No election results available yet.</p>';
            }
            ?>
        </div>

    </div>
</div>
</body>
</html>