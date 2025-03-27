<?php
session_start();

// Access check
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    exit();
}

// Make sure form data is stored
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['form_data'] = $_POST;
}

$data = $_SESSION['form_data'] ?? [];

if (empty($data)) {
    header('Location: pointsProgForm.php');
    exit();
}

// Safe access helper
function safe($key, $default = 'Not provided') {
    global $data;
    if (!isset($data[$key])) return $default;
    if (is_array($data[$key])) return implode(', ', $data[$key]);
    return htmlspecialchars($data[$key]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>FGP | Review Submission</title>
    <style>
        .review-row {
            display: flex;
            justify-content: space-between;
            max-width: 600px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<?php
    $permission_array['reviewPointsProg.php'] = 0;
    require_once('header.php');
?>

<h1>Review Your Points Program Submission</h1>
<main class="date">
    <form method="post" action="pointsProg.php">
        <p>Please review your point-eligible selections below before final submission.</p>

        <?php
        $totalPoints = 0;

        $meals = (int)($data['freezer_meals'] ?? 0);
        if ($meals > 2) {
            $points = $meals / 2;
            $totalPoints += $points;
            echo "<div class='review-row'><span>Freezer Meals ($meals meals)</span><span>$points pts</span></div>";
        }

        $cleaning = (int)($data['house_cleaning'] ?? 0);
        if ($cleaning > 0) {
            $points = $cleaning * 7;
            $totalPoints += $points;
            echo "<div class='review-row'><span>House Cleaning ($cleaning time(s))</span><span>$points pts</span></div>";
        }

        $lawn = (int)($data['lawn_care'] ?? 0);
        if ($lawn > 0) {
            $points = $lawn * 3;
            $totalPoints += $points;
            echo "<div class='review-row'><span>Lawn Care</span><span>$points pts</span></div>";
        }

        $groceryItems = $data['grocery'] ?? [];
        if (is_array($groceryItems)) {
            foreach ($groceryItems as $item) {
                if ($item !== 'none' && preg_match('/^(.*)-(\d+)$/', $item, $matches)) {
                    $label = htmlspecialchars($matches[1]);
                    $qty = (int)$matches[2];
                    $amount = $qty * 25;
                    $totalPoints += $qty;
                    echo "<div class='review-row'><span>Grocery: $label ($$amount)</span><span>$qty pts</span></div>";
                }
            }
        }

        $gasItems = $data['gas'] ?? [];
        if (is_array($gasItems)) {
            foreach ($gasItems as $item) {
                if ($item !== 'none' && preg_match('/^(.*)-(\d+)$/', $item, $matches)) {
                    $label = htmlspecialchars($matches[1]);
                    $qty = (int)$matches[2];
                    $amount = $qty * 25;
                    $totalPoints += $qty;
                    echo "<div class='review-row'><span>Gas: $label ($$amount)</span><span>$qty pts</span></div>";
                }
            }
        }

        $aaa = (int)($data['aaa_membership'] ?? 0);
        if ($aaa === 1) {
            $totalPoints += 5;
            echo "<div class='review-row'><span>AAA Membership</span><span>5 pts</span></div>";
            echo "<div class='review-row'><span>AAA Name</span><span>" . safe('aaa_membership_name') . "</span></div>";
            echo "<div class='review-row'><span>AAA DOB</span><span>" . safe('aaa_membership_dob') . "</span></div>";
        }
        ?>

        <div class="review-row" style="font-weight:bold; margin-top:20px;">
            <span>Total Points Used:</span><span><?= $totalPoints ?></span>
        </div>

        <?php foreach ($data as $key => $value): ?>
            <?php if (is_array($value)): ?>
                <?php foreach ($value as $subValue): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($subValue) ?>">
                <?php endforeach; ?>
            <?php else: ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
            <?php endif; ?>
        <?php endforeach; ?>

        <br><br>
        <input 
            type="button" 
            value="Back to Form" 
            onclick="history.back()" 
            style="
                background-color: #0082be; 
                color: white; 
                padding: 10px 20px; 
                border: none; 
                border-radius: 4px; 
                font-weight: 500; 
                width: 200%; 
                max-width: 542px; 
                margin: 0 auto; 
                display: block; 
                text-align: center; 
                cursor: pointer;
            "
        >
        <br>
        <input type="submit" value="Final Submit" class="btn btn-primary">
    </form>
</main>
</body>
</html>
