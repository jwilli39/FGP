<?php
/**
 * ============================================================================
 *  reviewPointsProg.php
 *
 *  Displays a summary of the user's Points Program selections for review.
 *  Uses modular functions from pointsReviewHandler.php for rendering and logic.
 *
 *  Dependencies:
 *    - pointsReviewHandler.php
 *
 * @author   Josh Williams
 * ============================================================================
 */

session_start();
require_once('pointsReviewHandler.php');

// Access control
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    exit();
}

// Store submitted form data into session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['form_data'] = $_POST;
}

$data = $_SESSION['form_data'] ?? [];

if (empty($data)) {
    header('Location: pointsProgForm.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>FGP | Review Submission</title>
    <link rel="stylesheet" href="css/base.css">
    <?php require_once('universal.inc'); ?>
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
            $totalPoints = renderPointsAndCalculateTotal($data);
            renderPointLimitWarning($totalPoints);
        ?>

        <div class="review-row" style="font-weight:bold; margin-top:20px;">
            <span>Total Points Used:</span><span><?= $totalPoints ?></span>
        </div>

        <?php renderHiddenFormFields($data); ?>

        <br><br>
        <input type="button" value="Back to Form" onclick="history.back()" class="btn-back-form">
        <br>
        <input type="submit" value="Final Submit" class="btn btn-primary">
    </form>
</main>
</body>
</html>
