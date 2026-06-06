<?php
require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_food') {
        $stmt = $pdo->prepare("INSERT INTO food_logs (food_name, cals, protein, carbs, fats) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['food_name'], (int)$_POST['cals'], (int)$_POST['protein'], (int)$_POST['carbs'], (int)$_POST['fats']]);
        header("Location: index.php");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_budget') {
        $stmt = $pdo->prepare("INSERT INTO budget (id, cals, protein, carbs, fats) VALUES (1, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cals = VALUES(cals), protein = VALUES(protein), carbs = VALUES(carbs), fats = VALUES(fats)");
        $stmt->execute([(int)$_POST['set_cals'], (int)$_POST['set_protein'], (int)$_POST['set_carbs'], (int)$_POST['set_fats']]);
        header("Location: index.php");
        exit;
    }
}

$budgetQuery = $pdo->query("SELECT * FROM budget WHERE id = 1");
$budget = $budgetQuery->fetch() ?: ['cals' => 2000, 'protein' => 150, 'carbs' => 200, 'fats' => 65];

$todayQuery = $pdo->query("SELECT COALESCE(SUM(cals), 0) as cals, COALESCE(SUM(protein), 0) as protein, COALESCE(SUM(carbs), 0) as carbs, COALESCE(SUM(fats), 0) as fats FROM food_logs WHERE log_date = CURRENT_DATE");
$consumed = $todayQuery->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Macro Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Macro Tracker Dashboard</h1>
        <div class="card">
            <h2>Today's Progress</h2>
            <div class="macro-grid">
                <div class="macro-box" style="border-left: 4px solid #3b82f6;"><h3>Calories</h3><p><?= htmlspecialchars($consumed['cals']) ?> / <?= htmlspecialchars($budget['cals']) ?></p></div>
                <div class="macro-box" style="border-left: 4px solid #ef4444;"><h3>Protein</h3><p><?= htmlspecialchars($consumed['protein']) ?>g / <?= htmlspecialchars($budget['protein']) ?>g</p></div>
                <div class="macro-box" style="border-left: 4px solid #10b981;"><h3>Carbs</h3><p><?= htmlspecialchars($consumed['carbs']) ?>g / <?= htmlspecialchars($budget['carbs']) ?>g</p></div>
                <div class="macro-box" style="border-left: 4px solid #f59e0b;"><h3>Fats</h3><p><?= htmlspecialchars($consumed['fats']) ?>g / <?= htmlspecialchars($budget['fats']) ?>g</p></div>
            </div>
        </div>
        <div class="card">
            <h2>Log Food Consumed</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_food">
                <label>Food Description</label><input type="text" name="food_name" required>
                <div class="input-row">
                    <div><label>Calories</label><input type="number" name="cals" required></div>
                    <div><label>Protein (g)</label><input type="number" name="protein" required></div>
                    <div><label>Carbs (g)</label><input type="number" name="carbs" required></div>
                    <div><label>Fats (g)</label><input type="number" name="fats" required></div>
                </div>
                <button type="submit" class="btn">Log Entry</button>
            </form>
        </div>
        <div class="card">
            <h2>Adjust Targets</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_budget">
                <div class="input-row">
                    <div><label>Calories</label><input type="number" name="set_cals" value="<?= htmlspecialchars($budget['cals']) ?>" required></div>
                    <div><label>Protein (g)</label><input type="number" name="set_protein" value="<?= htmlspecialchars($budget['protein']) ?>" required></div>
                    <div><label>Carbs (g)</label><input type="number" name="set_carbs" value="<?= htmlspecialchars($budget['carbs']) ?>" required></div>
                    <div><label>Fats (g)</label><input type="number" name="set_fats" value="<?= htmlspecialchars($budget['fats']) ?>" required></div>
                </div>
                <button type="submit" class="btn" style="background-color: #475569;">Save Budget</button>
            </form>
        </div>
    </div>
</body>
</html>