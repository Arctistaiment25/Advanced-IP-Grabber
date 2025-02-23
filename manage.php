<?php
require 'config.php';

$config = json_decode(file_get_contents('config.json'), true);
$managementPassword = isset($config['management_password']) ? $config['management_password'] : '';

if (!empty($managementPassword)) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($_POST['password'] != $managementPassword) {
            $error = 'Falsches Passwort!';
        } else {
            $search = '';
            $searchQuery = '';

            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = htmlspecialchars($_GET['search']);
                $searchQuery = " WHERE id LIKE :search OR name LIKE :search OR ip LIKE :search ";
            }

            if (isset($_POST['rename']) && isset($_POST['log_id'])) {
                $id = intval($_POST['log_id']);
                $new_name = trim($_POST['new_name']);
                $pdo->prepare("UPDATE logs SET name = ? WHERE id = ?")->execute([$new_name, $id]);
                header("Location: manage.php");
                exit;
            }

            if (isset($_GET['archive'])) {
                $id = intval($_GET['archive']);
                $pdo->prepare("UPDATE logs SET archived = 1 WHERE id = ?")->execute([$id]);
                header("Location: manage.php");
                exit;
            }

            if (isset($_GET['delete'])) {
                $id = intval($_GET['delete']);
                $pdo->prepare("DELETE FROM logs WHERE id = ?")->execute([$id]);
                header("Location: manage.php");
                exit;
            }

            if (isset($_GET['delete_all'])) {
                $pdo->exec("DELETE FROM logs WHERE archived = 0");
                header("Location: manage.php");
                exit;
            }

            $sql = "SELECT * FROM logs WHERE archived = 0";
            if ($searchQuery) {
                $sql .= $searchQuery;
            }
            $sql .= " ORDER BY timestamp DESC";

            $stmt = $pdo->prepare($sql);

            if ($searchQuery) {
                $stmt->bindValue(':search', '%' . $search . '%');
            }

            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!isset($error) && !isset($logs)) {
        echo '<!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Management Login</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #121212;
                    color: #e0e0e0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .login-container {
                    background-color: #333;
                    padding: 30px;
                    border-radius: 8px;
                    width: 300px;
                    text-align: center;
                }
                .login-container h2 {
                    color: #4CAF50;
                }
                .login-container input {
                    width: 100%;
                    padding: 10px;
                    margin: 10px 0;
                    border: 1px solid #444;
                    border-radius: 5px;
                    background-color: #222;
                    color: #e0e0e0;
                }
                .login-container button {
                    width: 100%;
                    background-color: #4CAF50;
                    color: white;
                    padding: 10px;
                    border-radius: 5px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .login-container button:hover {
                    background-color: #45a049;
                }
                .error {
                    color: red;
                    margin-top: 10px;
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2>Login zum Management</h2>
                <form method="POST">
                    <input type="password" name="password" placeholder="Passwort" required>
                    <button type="submit">Einloggen</button>
                </form>';
        
        if (isset($error)) {
            echo "<p class='error'>$error</p>";
        }

        echo '</div>
        </body>
        </html>';
        exit;
    }
} else {
    $search = '';
    $searchQuery = '';

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = htmlspecialchars($_GET['search']);
        $searchQuery = " WHERE id LIKE :search OR name LIKE :search OR ip LIKE :search ";
    }

    if (isset($_POST['rename']) && isset($_POST['log_id'])) {
        $id = intval($_POST['log_id']);
        $new_name = trim($_POST['new_name']);
        $pdo->prepare("UPDATE logs SET name = ? WHERE id = ?")->execute([$new_name, $id]);
        header("Location: manage.php");
        exit;
    }

    if (isset($_GET['archive'])) {
        $id = intval($_GET['archive']);
        $pdo->prepare("UPDATE logs SET archived = 1 WHERE id = ?")->execute([$id]);
        header("Location: manage.php");
        exit;
    }

    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $pdo->prepare("DELETE FROM logs WHERE id = ?")->execute([$id]);
        header("Location: manage.php");
        exit;
    }

    if (isset($_GET['delete_all'])) {
        $pdo->exec("DELETE FROM logs WHERE archived = 0");
        header("Location: manage.php");
        exit;
    }

    $sql = "SELECT * FROM logs WHERE archived = 0";
    if ($searchQuery) {
        $sql .= $searchQuery;
    }
    $sql .= " ORDER BY timestamp DESC";

    $stmt = $pdo->prepare($sql);

    if ($searchQuery) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }

    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Management</title>
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
            color: #4CAF50;
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
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
        }
        .top-bar a:hover {
            background-color: #45a049;
        }
        .search-bar input {
            position: absolute;
            left: 800px;
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #444;
            background-color: #333;
            color: #e0e0e0;
        }
        .search-bar button {
            position: absolute;
            left: 1100px;
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
            min-width: 150px;
        }
        th {
            background-color: #222;
            color: #e0e0e0;
        }
        td {
            background-color: #2c2c2c;
        }
        input[type="text"] {
            padding: 8px;
            width: 250px;
            border: 1px solid #555;
            border-radius: 3px;
            background-color: #444;
            color: #e0e0e0;
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
        .btn-archive {
            background-color: #2196F3;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn-rename {
            background-color: #FF9800;
            color: white;
        }
        .btn-archive:hover, .btn-delete:hover, .btn-rename:hover {
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

        .rename-container {
            display: flex;
            align-items: center;
        }

        .rename-container input {
            margin-right: 10px;
        }

        .censor-buttons {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Log Management</h2>
        <div>
            <a href="archive.php">📦 View Archive</a>
            <a href="edit.php" style="background-color: #2196F3;">⚙️ Settings</a>
        </div>
    </div>

    <div class="search-bar">
        <form method="get">
            <input type="text" name="search" placeholder="Search by ID, Name or IP..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="censor-buttons">
        <button id="censorUncensorToggle" class="btn btn-rename" onclick="toggleCensorship()">🔒 Censor All</button>
        <a href="manage.php?delete_all=true" class="delete-all" onclick="return confirm('Delete ALL logs?')">🗑️ Delete All</a>
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
            <?php if (isset($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td>
                            <div class="rename-container">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                                    <input type="text" name="new_name" value="<?= htmlspecialchars($log['name']) ?>">
                                    <button type="submit" name="rename" class="btn btn-rename">✏️ Rename</button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <span class="censored-ip">CENSORED</span>
                            <span class="actual-ip" style="display:none;"><?= htmlspecialchars($log['ip']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($log['user_agent']) ?></td>
                        <td><?= htmlspecialchars($log['domain']) ?></td>
                        <td><?= $log['timestamp'] ?></td>
                        <td class="action-btns">
                            <a href="manage.php?archive=<?= $log['id'] ?>" class="btn btn-archive">📦 Archive</a>
                            <a href="manage.php?delete=<?= $log['id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this log?')">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
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
            button.innerHTML = "🔓 Uncensor All";
            ipElements.forEach(ip => ip.style.display = 'none');
            actualIpElements.forEach(ip => ip.style.display = 'inline');
        } else {
            isCensored = true;
            button.innerHTML = "🔒 Censor All";
            ipElements.forEach(ip => ip.style.display = 'inline');
            actualIpElements.forEach(ip => ip.style.display = 'none');
        }
    }
</script>

</body>
</html>
