<?php
require 'config.php';

if (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = parse_url($_SERVER['HTTP_REFERER']);
    if ($referrer['path'] !== '/manage.php' && $referrer['path'] !== '/archive.php') {
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}

if (isset($_POST['rename']) && isset($_POST['log_id'])) {
    $id = intval($_POST['log_id']);
    $new_name = trim($_POST['new_name']);
    $pdo->prepare("UPDATE logs SET name = ? WHERE id = ?")->execute([$new_name, $id]);
    header("Location: archive.php");
    exit;
}

if (isset($_GET['unarchive'])) {
    $id = intval($_GET['unarchive']);
    $pdo->prepare("UPDATE logs SET archived = 0 WHERE id = ?")->execute([$id]);
    header("Location: archive.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM logs WHERE id = ?")->execute([$id]);
    header("Location: archive.php");
    exit;
}

if (isset($_GET['delete_all'])) {
    $pdo->exec("DELETE FROM logs WHERE archived = 1");
    header("Location: archive.php");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM logs WHERE archived = 1";

if ($search) {
    $query .= " AND (name LIKE :search OR ip LIKE :search OR id LIKE :search)";
}

$query .= " ORDER BY timestamp DESC";
$stmt = $pdo->prepare($query);

if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #2196F3;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .top-bar a {
            background-color: #2196F3;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
        }
        .top-bar a:hover {
            background-color: #1976D2;
        }
        .search-bar input {
            padding: 8px;
            width: 150px;
            border: 1px solid #555;
            border-radius: 3px;
            background-color: #444;
            color: #e0e0e0;
        }
        .search-bar button {
            background-color: #2196F3;
            color: white;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            margin-left: 10px;
        }
        .search-bar button:hover {
            background-color: #1976D2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #333;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background-color: #222;
            color: #e0e0e0;
        }
        td {
            background-color: #2c2c2c;
        }
        .action-btns {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            font-size: 14px;
            text-decoration: none;
        }
        .btn-unarchive {
            background-color: #FF9800;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn-rename {
            background-color: #FF5722;
            color: white;
        }
        .btn-unarchive:hover, .btn-delete:hover, .btn-rename:hover {
            opacity: 0.9;
        }
        .delete-all {
            background-color: #d32f2f;
            color: white;
            padding: 10px 20px;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-all:hover {
            background-color: #c62828;
        }
        /* Censorship effect */
        .censored-ip {
            color: #ff4d4d;
            font-weight: bold;
            cursor: pointer;
        }

        .censored-ip:hover {
            color: #e0e0e0;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Archived Logs</h2>
        <a href="manage.php">🔙 Manage Active Logs</a>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="get">
            <input type="text" name="search" placeholder="Search by ID, Name or IP..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">🔍 Search</button>
        </form>
    </div>

    <!-- Censor Button -->
    <div class="censor-buttons">
        <button id="censorUncensorToggle" class="btn btn-rename" onclick="toggleCensorship()">🔒 Censor All</button>
        <a href="archive.php?delete_all=true" class="delete-all" onclick="return confirm('Delete ALL archived logs?')">🗑️ Delete All</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>IP</th>
                <th>User-Agent</th>
                <th>Domain</th>
                <th>Timestamp</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                            <input type="text" name="new_name" value="<?= htmlspecialchars($log['name']) ?>">
                            <button type="submit" name="rename" class="btn btn-rename">✏️ Rename</button>
                        </form>
                    </td>
                    <td>
                        <span class="censored-ip">CENSORED</span>
                        <span class="actual-ip" style="display:none;"><?= htmlspecialchars($log['ip']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($log['user_agent']) ?></td>
                    <td><?= htmlspecialchars($log['domain']) ?></td>
                    <td><?= $log['timestamp'] ?></td>
                    <td class="action-btns">
                        <a href="archive.php?unarchive=<?= $log['id'] ?>" class="btn btn-unarchive">🔄 Unarchive</a>
                        <a href="archive.php?delete=<?= $log['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this log?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    let isCensored = true;

    function toggleCensorship() {
        const ipElements = document.querySelectorAll('.censored-ip');
        const actualIpElements = document.querySelectorAll('.actual-ip');
        const button = document.getElementById("censorUncensorToggle");

        if (isCensored) {
            isCensored = false;
            button.innerHTML = "🔓 Censor All";
            ipElements.forEach(ip => ip.style.display = 'none');
            actualIpElements.forEach(ip => ip.style.display = 'inline');
        } else {
            isCensored = true;
            button.innerHTML = "🔒 Uncensor All";
            ipElements.forEach(ip => ip.style.display = 'inline');
            actualIpElements.forEach(ip => ip.style.display = 'none');
        }
    }

    window.onload = function() {
        toggleCensorship();
    };
</script>

</body>
</html>
