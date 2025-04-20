<?php
// Authors: Harmony Peura and Grayson Jones
// Parses and saves input from the Points Program Form

ob_start();
session_cache_expire(30);
session_start();

require_once('pointsProgHandler.php');
require_once('email.php');

$loggedIn = false;
$accessLevel = 0;
$userID = null;
$isAdmin = false;

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    die();
}

if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    $accessLevel = $_SESSION['access_level'];
    $isAdmin = $accessLevel >= 2;
    $userID = $_SESSION['_id'];
} else {
    header('Location: login.php');
    die();
}

if ($isAdmin && isset($_GET['id'])) {
    $args = sanitize($_GET);
    $id = strtolower($args['id']);
} else {
    $id = $userID;
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once('universal.inc'); ?>
    <title>FGP | Points Program</title>
</head>

<body>
<?php
require_once('header.php');
require_once('database/dbPersons.php');
require_once('database/dbPointsProg.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ignoreList = ['password'];
    $args = sanitize($_POST, $ignoreList);
    $_SESSION['form_data'] = $args;

    $required = ['name', 'email', 'address', 'freezer_meals', 'house_cleaning', 'lawn_care', 'aaa_membership'];
    if (!wereRequiredFieldsSubmitted($args, $required)) {
        header("Location: pointsProg.php?error=unexpected_input");
        exit;
    }

    $con = connect();
    $email = $args['email'];
    $query = "SELECT * FROM dbPersons WHERE email = '$email'";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) === 0) {
        header("Location: pointsProg.php?emailError");
        exit;
    }

    $beforeUpdate = get_points_form($email);
    $newPointsProg = buildPointsProgFromForm($args);
    $result = add_points_prog($newPointsProg);
    $afterUpdate = get_points_form($email);
    $changed = hasFormChanged($afterUpdate, $beforeUpdate);

    echo "Form Changed: " . ($changed ? "Yes" : "No") . "\n\n";

    if (!$result) {
        echo '<p>We encountered an issue while processing your submission. Please try again later.</p>';
    } else {
        if ($changed) {
            // Retrieve person info
            require_once('database/dbPersons.php');
            $person = retrieve_person($userID);
    
            // ===== PRODUCTION =====
            // notifyUserOfPointsUpdate($email, $person->get_first_name(), $person->get_last_name());
            // notifyAdminsOfPointsUpdate($email, $person->get_first_name(), $person->get_last_name());
    
            // ===== TEST CASE =====
            notifyUserOfPointsUpdate($email, $person->get_first_name(), $person->get_last_name());
            notifyAdminsOfPointsUpdate($email, $person->get_first_name(), $person->get_last_name());
    
            // Redirect with success indicator
            echo '<script>document.location = "index.php?pointsProgSuccess=1&pointsProgUpdated=true";</script>';
        } else {
            echo '<script>document.location = "index.php?pointsProgSuccess=1&pointsProgUpdated=false";</script>';
        }
    }
    } else {
        require_once('pointsProgForm.php');
    }
    

?>
<?php ob_end_flush(); ?>
</body>
</html>
