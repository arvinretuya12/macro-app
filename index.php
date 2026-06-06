<?php
require_once 'conn.php';

// 1. Determine the "Active Date" from the calendar, default to today
$activeDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 2. Handle Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_food') {
        $stmt = $pdo->prepare("INSERT INTO food_logs (log_date, meal_type, food_name, cals, protein, carbs, fats) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['log_date'], $_POST['meal_type'], $_POST['food_name'], 
            (int)$_POST['cals'], (int)$_POST['protein'], (int)$_POST['carbs'], (int)$_POST['fats']
        ]);
        header("Location: index.php?date=" . $_POST['log_date']); 
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_budget') {
        $stmt = $pdo->prepare("INSERT INTO budget (id, cals, protein, carbs, fats) VALUES (1, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE cals = VALUES(cals), protein = VALUES(protein), carbs = VALUES(carbs), fats = VALUES(fats)");
        $stmt->execute([
            (int)$_POST['set_cals'], (int)$_POST['set_protein'], (int)$_POST['set_carbs'], (int)$_POST['set_fats']
        ]);
        header("Location: index.php?date=" . $activeDate);
        exit;
    }
}

// 3. Fetch Data for the Active Date
$budgetQuery = $pdo->query("SELECT * FROM budget WHERE id = 1");
$budget = $budgetQuery->fetch() ?: ['cals' => 2000, 'protein' => 150, 'carbs' => 200, 'fats' => 65];

$dayQuery = $pdo->prepare("SELECT COALESCE(SUM(cals), 0) as cals, COALESCE(SUM(protein), 0) as protein, 
                                  COALESCE(SUM(carbs), 0) as carbs, COALESCE(SUM(fats), 0) as fats 
                           FROM food_logs WHERE log_date = ?");
$dayQuery->execute([$activeDate]);
$consumed = $dayQuery->fetch();

// Fetch foods and group them by meal type
$foodsQuery = $pdo->prepare("SELECT * FROM food_logs WHERE log_date = ?");
$foodsQuery->execute([$activeDate]);
$rawFoods = $foodsQuery->fetchAll();

// Initialize empty arrays for each meal to ensure they always show up in the UI
$groupedFoods = [
    'Breakfast' => [],
    'Lunch' => [],
    'Dinner' => [],
    'Snack' => []
];

// Sort the fetched foods into their respective meal categories
foreach ($rawFoods as $food) {
    $groupedFoods[$food['meal_type']][] = $food;
}
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

        <div class="card date-selector">
            <form method="GET" action="index.php" id="date-form">
                <label for="date-picker"><strong>Select Date to View:</strong></label>
                <input type="date" id="date-picker" name="date" value="<?= htmlspecialchars($activeDate) ?>" onchange="document.getElementById('date-form').submit();">
            </form>
        </div>

        <div class="card">
            <h2>Progress for <?= date('F j, Y', strtotime($activeDate)) ?></h2>
            <div class="macro-grid">
                <div class="macro-box" style="border-left: 4px solid #3b82f6;">
                    <h3>Calories</h3>
                    <p><?= htmlspecialchars($consumed['cals']) ?> / <?= htmlspecialchars($budget['cals']) ?></p>
                </div>
                <div class="macro-box" style="border-left: 4px solid #ef4444;">
                    <h3>Protein</h3>
                    <p><?= htmlspecialchars($consumed['protein']) ?>g / <?= htmlspecialchars($budget['protein']) ?>g</p>
                </div>
                <div class="macro-box" style="border-left: 4px solid #10b981;">
                    <h3>Carbs</h3>
                    <p><?= htmlspecialchars($consumed['carbs']) ?>g / <?= htmlspecialchars($budget['carbs']) ?>g</p>
                </div>
                <div class="macro-box" style="border-left: 4px solid #f59e0b;">
                    <h3>Fats</h3>
                    <p><?= htmlspecialchars($consumed['fats']) ?>g / <?= htmlspecialchars($budget['fats']) ?>g</p>
                </div>
            </div>
        </div>

        <div class="card log-container">
            <h2>Food Log</h2>
            
            <?php foreach ($groupedFoods as $mealName => $items): ?>
                <?php 
                    // Calculate total calories for this specific meal
                    $mealCals = array_sum(array_column($items, 'cals')); 
                ?>
                <div class="meal-section">
                    <div class="meal-header">
                        <h3><?= htmlspecialchars($mealName) ?></h3>
                        <span class="meal-total"><?= $mealCals ?> cals</span>
                    </div>
                    
                    <?php if (empty($items)): ?>
                        <div class="empty-meal">No food logged yet.</div>
                    <?php else: ?>
                        <ul class="food-list">
                            <?php foreach ($items as $item): ?>
                                <li class="food-item">
                                    <div class="food-details">
                                        <span class="food-name"><?= htmlspecialchars($item['food_name']) ?></span>
                                        <span class="food-macros">P: <?= htmlspecialchars($item['protein']) ?>g | C: <?= htmlspecialchars($item['carbs']) ?>g | F: <?= htmlspecialchars($item['fats']) ?>g</span>
                                    </div>
                                    <div class="food-cals-right">
                                        <?= htmlspecialchars($item['cals']) ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h2>Log Food Consumed</h2>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="add_food">
                
                <div class="input-row">
                    <div>
                        <label>Date</label>
                        <input type="date" name="log_date" value="<?= htmlspecialchars($activeDate) ?>" required>
                    </div>
                    <div>
                        <label>Meal</label>
                        <select name="meal_type" required>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Dinner">Dinner</option>
                            <option value="Snack">Snack</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label>Food Description</label>
                    <input type="text" name="food_name" placeholder="e.g., Plain Oats and Chia" required>
                </div>
                
                <div class="input-row">
                    <div><label>Calories</label><input type="number" name="cals" min="0" placeholder="0" required></div>
                    <div><label>Protein (g)</label><input type="number" name="protein" min="0" placeholder="0" required></div>
                    <div><label>Carbs (g)</label><input type="number" name="carbs" min="0" placeholder="0" required></div>
                    <div><label>Fats (g)</label><input type="number" name="fats" min="0" placeholder="0" required></div>
                </div>
                <button type="submit" class="btn">Log Entry</button>
            </form>
        </div>

        <div class="card">
            <h2>Adjust Target Allocations</h2>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="update_budget">
                <div class="input-row">
                    <div><label>Target Calories</label><input type="number" name="set_cals" value="<?= htmlspecialchars($budget['cals']) ?>" required></div>
                    <div><label>Target Protein</label><input type="number" name="set_protein" value="<?= htmlspecialchars($budget['protein']) ?>" required></div>
                    <div><label>Target Carbs</label><input type="number" name="set_carbs" value="<?= htmlspecialchars($budget['carbs']) ?>" required></div>
                    <div><label>Target Fats</label><input type="number" name="set_fats" value="<?= htmlspecialchars($budget['fats']) ?>" required></div>
                </div>
                <button type="submit" class="btn" style="background-color: #475569;">Save New Budget</button>
            </form>
        </div>

    </div>
</body>
</html>