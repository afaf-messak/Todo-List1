<?php
// --- Connexion à la base de données ---
define('DB_USER', 'root');
define('DB_PASS', 'afou@123');
define('DB_NAME', 'todo-list');
define('DB_HOST', '127.0.0.1');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// --- Gestion des actions ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? '';
    $id = (int) ($_POST["id"] ?? 0);
    $title = trim($_POST["title"] ?? '');
    $celebrate = false;

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
        // Get current state to know if we switched from not-done (0) to done (1)
        $stmt = $conn->prepare("SELECT done FROM todo WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $currentDone = $stmt->fetchColumn();

        $stmt = $conn->prepare("UPDATE todo SET done = 1 - done WHERE id = :id");
        $stmt->execute(['id' => $id]);

        // If the task was previously not done (0) and we toggled it, celebrate
        if ($currentDone !== false && (int) $currentDone === 0) {
            $celebrate = true;
        }
    }

    // Supprimer
    if ($action === "delete" && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM todo WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    $location = 'index1.php' . ($celebrate ? '?celebrate=1' : '');
    header("Location: " . $location);
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: var(--text-color);
        }

        .tablet-frame {
            background-color: var(--main-color);
            border-radius: 24px;
            padding: 15px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 420px;
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            font-size: 0.85rem;
        }

        .app-header span {
            font-weight: 600;
            color: var(--accent-color);
        }

        .app-body {
            padding: 15px;
        }

        .title-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .title-container h2 {
            margin: 0;
            font-size: 1.7rem;
        }

        .add-task-container {
            display: flex;
            margin-bottom: 1.5rem;
        }

        .add-task-container input {
            flex-grow: 1;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            padding: 12px 20px;
            font-size: 0.9rem;
            outline: none;
            margin-right: -35px;
        }

        .add-task-container button {
            border: none;
            background-color: var(--accent-color);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
        }

        .task-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .date-header {
            color: var(--accent-color);
            font-size: 0.85rem;
            margin: 10px 0;
        }

        .task-item {
            display: flex;
            align-items: center;
            background-color: #fafafa;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 10px;
        }

        .task-item.done {
            background: #eeeeee;
        }

        .task-item.done .task-title {
            text-decoration: line-through;
            color: #aaa;
        }

        .checkbox-form button {
            width: 22px;
            height: 22px;
            border: 2px solid #ccc;
            border-radius: 50%;
            cursor: pointer;
            margin-right: 15px;
            background: transparent;
            font-size: 12px;
            color: white;
        }

        .task-item.done .checkbox-form button {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }

        .delete-form button {
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #aaa;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #888;
        }

        /* Pop-up de célébration */
        .celebration-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }

        .celebration-message {
            background: linear-gradient(135deg, #ffffff 0%, #f8f0ff 100%);
            padding: 28px 26px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(99, 52, 137, 0.15);
            text-align: center;
            max-width: 460px;
            width: 100%;
            animation: popIn 320ms cubic-bezier(.2, .9, .3, 1);
        }

        .celebration-message h3 {
            margin: 0 0 8px 0;
            font-size: 1.6rem;
            color: lightseagreen;
            /* purple-700 */
            letter-spacing: -0.4px;
        }

        .celebration-message p {
            margin: 0 0 16px 0;
            color: #444;
            font-size: 0.98rem;
        }

        .celebration-btn,
        #close-celebration {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(90deg, lightseagreen 0%, #ec4899 100%);
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.18);
            transition: transform 180ms ease, box-shadow 180ms ease;
            font-size: 1rem;
        }

        .celebration-btn:hover,
        #close-celebration:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 30px rgba(124, 58, 237, 0.22);
        }

        @keyframes popIn {
            from {
                transform: translateY(8px) scale(0.98);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
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
                <?php if (empty($grouped_tasks)): ?>
                    <li class="empty-message">Aucune tâche pour le moment.</li>
                <?php else: ?>
                    <?php foreach ($grouped_tasks as $date => $tasks_on_date): ?>
                        <div class="date-header">Date : <?= date('d/m/Y', strtotime($date)); ?></div>

                        <?php foreach ($tasks_on_date as $task): ?>
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
                ?>

            </ul>

        </main>
    </div>
    <script>
        // --- Horloge en temps réel ---
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
<<<<<<< HEAD
            document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
=======
            document.getElementById('clock').textContent = ${hours}:${minutes}:${seconds};
>>>>>>> 494d8beab97af22c4ebe34172a62799e08f3f228
        }
        setInterval(updateClock, 1000);
        updateClock();
        // --- Messages de célébration ---
        const messages = [
            "Vous avez accompli quelque chose de génial aujourd'hui !",
            "Chaque tâche terminée est une victoire !",
            "Continuez comme ça, vous faites du bon travail !",
            "Bravo pour votre productivité !",
            "Une tâche de moins, un pas de plus vers vos objectifs !"
        ];
        // Play a short celebration chime using Web Audio API
        function playCelebrationSound() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                const ctx = new AudioCtx();

                const now = ctx.currentTime;
                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0, now);
                gain.connect(ctx.destination);

<<<<<<< HEAD
                // Bell-like partials (inharmonic-ish) for a pleasant chime
                const freqs = [880, 1325, 1760, 2640];
                freqs.forEach((f, i) => {
                    const osc = ctx.createOscillator();
                    // use a mix of waveforms for a richer timbre
                    osc.type = i === 0 ? 'sine' : 'triangle';
                    const start = now + i * 0.02;
                    osc.frequency.setValueAtTime(f, start);

                    const localGain = ctx.createGain();
                    // small attack, longer exponential decay for bell-like tail
                    localGain.gain.setValueAtTime(0.0001, start);
                    localGain.gain.exponentialRampToValueAtTime(0.18 / (i + 1), start + 0.02);
                    localGain.gain.exponentialRampToValueAtTime(0.0001, start + 1.4);

                    osc.connect(localGain);
                    localGain.connect(gain);
                    osc.start(start);
                    osc.stop(start + 1.5);
                });

                // overall envelope to avoid clicks and shape final amplitude
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(1.0, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 1.5);
                // close AudioContext after playback
                setTimeout(() => { if (ctx.close) ctx.close(); }, 1700);
            } catch (e) {
                // Audio may be blocked by autoplay policies — ignore silently
                console.warn('Audio playback failed:', e);
            }
        }

        if (window.location.search.includes('celebrate=1')) {
            const message = messages[Math.floor(Math.random() * messages.length)];
            const overlay = document.createElement('div');
            overlay.className = 'celebration-overlay';
            overlay.innerHTML = `
                <div class="celebration-message">
                    <h3>Félicitations ! 🎉</h3>
                    <p>${message}</p>
                    <button id="close-celebration" class="celebration-btn">Fermer</button>
                </div>
            `;
            document.body.appendChild(overlay);
            const closeBtn = overlay.querySelector('#close-celebration');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () { overlay.remove(); });
            }
            // Close on overlay click (but not when clicking inside the message)
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.remove();
            });

=======
                const freqs = [880, 1320, 1760]; // simple triad
                freqs.forEach((f, i) => {
                    const osc = ctx.createOscillator();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(f, now + i * 0.06);
                    const localGain = ctx.createGain();
                    localGain.gain.setValueAtTime(0, now + i * 0.06);
                    localGain.gain.linearRampToValueAtTime(0.12, now + i * 0.06 + 0.02);
                    localGain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.6);
                    osc.connect(localGain);
                    localGain.connect(gain);
                    osc.start(now + i * 0.06);
                    osc.stop(now + i * 0.6);
                });

                // overall envelope
                gain.gain.linearRampToValueAtTime(1, now + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
                // close context after sound
                setTimeout(() => { if (ctx.close) ctx.close(); }, 1200);
            } catch (e) {
                // Audio may be blocked by autoplay policies — ignore silently
                console.warn('Audio playback failed:', e);
            }
        }

        if (window.location.search.includes('celebrate=1')) {
            const message = messages[Math.floor(Math.random() * messages.length)];
            const overlay = document.createElement('div');
            overlay.className = 'celebration-overlay';
            overlay.innerHTML = `
                <div class="celebration-message">
                    <h3>Félicitations ! 🎉</h3>
                    <p>${message}</p>
                    <button id="close-celebration" class="celebration-btn">Fermer</button>
                </div>
            `;
            document.body.appendChild(overlay);
            const closeBtn = overlay.querySelector('#close-celebration');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () { overlay.remove(); });
            }
            // Close on overlay click (but not when clicking inside the message)
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.remove();
            });

>>>>>>> 494d8beab97af22c4ebe34172a62799e08f3f228
            // Try to play the celebration sound. If blocked, it will fail silently.
            playCelebrationSound();
        }


<<<<<<< HEAD
=======


>>>>>>> 494d8beab97af22c4ebe34172a62799e08f3f228
    </script>
</body>

</html>