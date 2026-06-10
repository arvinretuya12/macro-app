<?php
require_once 'conn.php';

// 1. Determine the "Active Date"
$activeDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 2. Handle Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_food') {
        $stmt = $pdo->prepare("INSERT INTO food_logs (log_date, meal_type, food_name, cals, protein, carbs, fats) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['log_date'], $_POST['meal_type'], $_POST['food_name'], 
            (float)$_POST['cals'], (float)$_POST['protein'], (float)$_POST['carbs'], (float)$_POST['fats']
        ]);
        header("Location: index.php?date=" . $_POST['log_date']); 
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_food') {
        $stmt = $pdo->prepare("DELETE FROM food_logs WHERE id = ?");
        $stmt->execute([(int)$_POST['log_id']]);
        header("Location: index.php?date=" . $_POST['return_date']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_budget') {
        $stmt = $pdo->prepare("INSERT INTO budget (id, cals, protein, carbs, fats) VALUES (1, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE cals = VALUES(cals), protein = VALUES(protein), carbs = VALUES(carbs), fats = VALUES(fats)");
        $stmt->execute([
            (float)$_POST['set_cals'], (float)$_POST['set_protein'], (float)$_POST['set_carbs'], (float)$_POST['set_fats']
        ]);
        header("Location: index.php?date=" . $activeDate);
        exit;
    }
}

// 3. Fetch Data
$budgetQuery = $pdo->query("SELECT * FROM budget WHERE id = 1");
$budget = $budgetQuery->fetch() ?: ['cals' => 2000, 'protein' => 150, 'carbs' => 200, 'fats' => 65];

$dayQuery = $pdo->prepare("SELECT COALESCE(SUM(cals), 0) as cals, COALESCE(SUM(protein), 0) as protein, 
                                  COALESCE(SUM(carbs), 0) as carbs, COALESCE(SUM(fats), 0) as fats 
                           FROM food_logs WHERE log_date = ?");
$dayQuery->execute([$activeDate]);
$consumed = $dayQuery->fetch();

$foodsQuery = $pdo->prepare("SELECT * FROM food_logs WHERE log_date = ?");
$foodsQuery->execute([$activeDate]);
$rawFoods = $foodsQuery->fetchAll();

// 4. Fetch Historical Foods for Autocomplete (Gets the most recent entry for each unique food)
$historyQuery = $pdo->query("
    SELECT t1.food_name, t1.cals, t1.protein, t1.carbs, t1.fats 
    FROM food_logs t1
    INNER JOIN (SELECT food_name, MAX(id) as max_id FROM food_logs GROUP BY food_name) t2 
    ON t1.id = t2.max_id
");
$historicalFoods = $historyQuery->fetchAll(PDO::FETCH_ASSOC);

$groupedFoods = ['Breakfast' => [], 'Lunch' => [], 'Dinner' => [], 'Snack' => []];
foreach ($rawFoods as $food) {
    $groupedFoods[$food['meal_type']][] = $food;
}

// Helper function to calculate circular progress styles
function getProgressStyle($consumedVal, $budgetVal) {
    $pct = $budgetVal > 0 ? ($consumedVal / $budgetVal) * 100 : 0;
    if ($pct > 100) {
        return "background: conic-gradient(#ef4444 100%, #e2e8f0 0);"; // Red if over budget
    } else {
        return "background: conic-gradient(#10b981 {$pct}%, #e2e8f0 0);"; // Green and Gray if under/equal
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Macro Tracker</title>
    <link rel="stylesheet" href="style.css">

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#F6F6F6">
    <link rel="apple-touch-icon" href="logos/macros.png">


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
                
                <div class="macro-box">
                    <h3>Calories</h3>
                    <div class="circular-progress" style="<?= getProgressStyle($consumed['cals'], $budget['cals']) ?>">
                        <div class="inner-circle">
                            <span class="value"><?= round((float)$consumed['cals'], 1) ?></span>
                            <span class="budget">/ <?= round((float)$budget['cals'], 1) ?></span>
                        </div>
                    </div>
                </div>

                <div class="macro-box">
                    <h3>Protein</h3>
                    <div class="circular-progress" style="<?= getProgressStyle($consumed['protein'], $budget['protein']) ?>">
                        <div class="inner-circle">
                            <span class="value"><?= round((float)$consumed['protein'], 1) ?>g</span>
                            <span class="budget">/ <?= round((float)$budget['protein'], 1) ?>g</span>
                        </div>
                    </div>
                </div>

                <div class="macro-box">
                    <h3>Carbs</h3>
                    <div class="circular-progress" style="<?= getProgressStyle($consumed['carbs'], $budget['carbs']) ?>">
                        <div class="inner-circle">
                            <span class="value"><?= round((float)$consumed['carbs'], 1) ?>g</span>
                            <span class="budget">/ <?= round((float)$budget['carbs'], 1) ?>g</span>
                        </div>
                    </div>
                </div>

                <div class="macro-box">
                    <h3>Fats</h3>
                    <div class="circular-progress" style="<?= getProgressStyle($consumed['fats'], $budget['fats']) ?>">
                        <div class="inner-circle">
                            <span class="value"><?= round((float)$consumed['fats'], 1) ?>g</span>
                            <span class="budget">/ <?= round((float)$budget['fats'], 1) ?>g</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card log-container">
            <h2>Food Log</h2>
            <?php foreach ($groupedFoods as $mealName => $items): ?>
                <?php $mealCals = array_sum(array_column($items, 'cals')); ?>
                <div class="meal-section">
                    <div class="meal-header">
                        <h3><?= htmlspecialchars($mealName) ?></h3>
                        <span class="meal-total"><?= round((float)$mealCals, 1) ?> cals</span>
                    </div>
                    <?php if (empty($items)): ?>
                        <div class="empty-meal">No food logged yet.</div>
                    <?php else: ?>
                        <ul class="food-list">
                            <?php foreach ($items as $item): ?>
                                <li class="food-item">
                                    <div class="food-details">
                                        <span class="food-name"><?= htmlspecialchars($item['food_name']) ?></span>
                                        <span class="food-macros">P: <?= round((float)$item['protein'], 1) ?>g | C: <?= round((float)$item['carbs'], 1) ?>g | F: <?= round((float)$item['fats'], 1) ?>g</span>
                                    </div>
                                    <div class="food-actions">
                                        <span class="food-cals-right"><?= round((float)$item['cals'], 1) ?></span>
                                        <form method="POST" action="index.php" style="display: inline; margin: 0; flex-direction: row;">
                                            <input type="hidden" name="action" value="delete_food">
                                            <input type="hidden" name="log_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="return_date" value="<?= htmlspecialchars($activeDate) ?>">
                                            <button type="submit" class="btn-delete" title="Delete entry" onclick="return confirm('Delete this food entry?');">×</button>
                                        </form>
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
                    <input type="text" id="food_name_input" name="food_name" list="food-history-list" placeholder="Start typing to search past meals..." autocomplete="off" required>
                    <datalist id="food-history-list">
                        <?php foreach($historicalFoods as $hf): ?>
                            <option value="<?= htmlspecialchars($hf['food_name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="input-row">
                    <div><label>Calories</label><input type="number" id="cals_input" name="cals" min="0" step="0.1" placeholder="0.0" required></div>
                    <div><label>Protein (g)</label><input type="number" id="protein_input" name="protein" min="0" step="0.1" placeholder="0.0" required></div>
                    <div><label>Carbs (g)</label><input type="number" id="carbs_input" name="carbs" min="0" step="0.1" placeholder="0.0" required></div>
                    <div><label>Fats (g)</label><input type="number" id="fats_input" name="fats" min="0" step="0.1" placeholder="0.0" required></div>
                </div>
                <button type="submit" class="btn">Log Entry</button>
            </form>
        </div>

        <div class="card">
            <h2>Adjust Target Allocations</h2>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="update_budget">
                <div class="input-row">
                    <div><label>Target Calories</label><input type="number" name="set_cals" step="0.1" value="<?= round((float)$budget['cals'], 1) ?>" required></div>
                    <div><label>Target Protein</label><input type="number" name="set_protein" step="0.1" value="<?= round((float)$budget['protein'], 1) ?>" required></div>
                    <div><label>Target Carbs</label><input type="number" name="set_carbs" step="0.1" value="<?= round((float)$budget['carbs'], 1) ?>" required></div>
                    <div><label>Target Fats</label><input type="number" name="set_fats" step="0.1" value="<?= round((float)$budget['fats'], 1) ?>" required></div>
                </div>
                <button type="submit" class="btn" style="background-color: #475569;">Save New Budget</button>
            </form>
        </div>

    </div>

    <script>
        const foodHistory = <?= json_encode($historicalFoods) ?>;
        const foodInput = document.getElementById('food_name_input');
        
        foodInput.addEventListener('input', function() {
            // Check if the typed value perfectly matches a previous entry
            const match = foodHistory.find(f => f.food_name.toLowerCase() === this.value.toLowerCase());
            
            if (match) {
                // Auto-fill the textboxes
                document.getElementById('cals_input').value = parseFloat(match.cals);
                document.getElementById('protein_input').value = parseFloat(match.protein);
                document.getElementById('carbs_input').value = parseFloat(match.carbs);
                document.getElementById('fats_input').value = parseFloat(match.fats);
            }
        });
    </script>
</body>
</html>