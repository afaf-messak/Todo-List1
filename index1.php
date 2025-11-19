<?php
// --- Connexion à la base de données ---
define('DB_USER', 'root');
define('DB_PASS', 'afou@123');
define('DB_NAME', 'todo-list');
define('DB_HOST', '127.0.0.1');

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// --- Gestion des actions ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? '';
    $id = (int)($_POST["id"] ?? 0);
    $title = trim($_POST["title"] ?? '');

    // Ajouter une nouvelle tâche
    if ($action === "new" && $title !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO todo (title, done, created_at) 
             VALUES (:title, 0, NOW())"
        );
        $stmt->execute(['title' => $title]);
    }

    // Cocher / décocher
    if ($action === "toggle" && $id > 0) {
        $stmt = $conn->prepare("UPDATE todo SET done = 1 - done WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    // Supprimer
    if ($action === "delete" && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM todo WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    header("Location: index1.php");
    exit;
}

// --- Récupérer les tâches par date ---
$stmt = $conn->query("
    SELECT id, title, done, DATE(created_at) as task_date 
    FROM todo 
    ORDER BY created_at DESC
");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_tasks = [];
foreach ($tasks as $task) {
    $grouped_tasks[$task['task_date']][] = $task;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>To Do List - Projet</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
/* --- STYLES --- */
:root {
    --bg-color: #f0f2f5;
    --main-color: #ffffff;
    --text-color: #3a3a3a;
    --accent-color: #ff6b6b;
    --border-color: #eeeeee;
    --shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
}

body {
    background-color: var(--bg-color);
    font-family: 'Poppins', sans-serif;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    margin:0;
    color:var(--text-color);
}

.tablet-frame {
    background-color: var(--main-color);
    border-radius:24px;
    padding:15px;
    box-shadow:var(--shadow);
    width:100%;
    max-width:420px;
}

.app-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px 15px;
    font-size:0.85rem;
}

.app-header span {
    font-weight:600;
    color:var(--accent-color);
}

.app-body { padding:15px; }
.title-container { text-align:center; margin-bottom:1.5rem; }
.title-container h2 { margin:0; font-size:1.7rem; }

.add-task-container {
    display:flex;
    margin-bottom:1.5rem;
}

.add-task-container input {
    flex-grow:1;
    border:1px solid var(--border-color);
    border-radius:25px;
    padding:12px 20px;
    font-size:0.9rem;
    outline:none;
    margin-right:-35px;
}

.add-task-container button {
    border:none;
    background-color:var(--accent-color);
    color:white;
    padding:12px 25px;
    border-radius:25px;
    cursor:pointer;
    font-weight:500;
}

.task-list { list-style:none; padding:0; margin:0; }
.date-header { color:var(--accent-color); font-size:0.85rem; margin:10px 0; }

.task-item {
    display:flex;
    align-items:center;
    background-color:#fafafa;
    border:1px solid var(--border-color);
    border-radius:12px;
    padding:12px 15px;
    margin-bottom:10px;
}

.task-item.done { background:#eeeeee; }
.task-item.done .task-title { text-decoration:line-through; color:#aaa; }

.checkbox-form button {
    width:22px;
    height:22px;
    border:2px solid #ccc;
    border-radius:50%;
    cursor:pointer;
    margin-right:15px;
    background:transparent;
    font-size:12px;
    color:white;
}

.task-item.done .checkbox-form button {
    background:var(--accent-color);
    border-color:var(--accent-color);
}

.delete-form button {
    border:none;
    background:none;
    cursor:pointer;
    font-size:1.2rem;
    color:#aaa;
}

.empty-message { text-align:center; padding:20px; color:#888; }

/* Pop-up de célébration */
.celebration-message {
    position:fixed;
    top:50%; left:50%;
    transform:translate(-50%,-50%);
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 10px 30px rgba(0,0,0,0.2);
    text-align:center;
}
</style>
</head>

<body>
<div class="tablet-frame">

<header class="app-header">
    <div>Hello <span>SweetHeart !</span></div>
    <div class="time">Time: <span id="clock"></span></div>
</header>

<main class="app-body">
    <div class="title-container">
        <h2>To Do List 📝</h2>
    </div>

    <form method="POST" class="add-task-container">
        <input type="text" name="title" placeholder="Add new task" required>
        <button type="submit" name="action" value="new">Add</button>
    </form>

    <ul class="task-list">
        <?php if(empty($grouped_tasks)): ?>
            <li class="empty-message">Aucune tâche pour le moment.</li>
        <?php else: ?>
            <?php foreach($grouped_tasks as $date => $tasks_on_date): ?>
                <div class="date-header">Date : <?= date('d/m/Y', strtotime($date)); ?></div>

                <?php foreach($tasks_on_date as $task): ?>
                    <li class="task-item <?= $task['done'] ? 'done' : ''; ?>">
                        <form method="POST" class="checkbox-form">
                            <input type="hidden" name="id" value="<?= $task['id']; ?>">
                            <button type="submit" name="action" value="toggle">
                                <?= $task['done'] ? '✔' : '' ?>
                            </button>
                        </form>

                        <span class="task-title"><?= htmlspecialchars($task['title']); ?></span>

                        <form method="POST" class="delete-form">
                            <input type="hidden" name="id" value="<?= $task['id']; ?>">
                            <button name="action" value="delete">🗑</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif;
