<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('include/input-validation.php');
require_once('domain/PointsProg.php');
require_once('database/dbPointsProg.php');
require_once('domain/Person.php');
require_once('database/dbPersons.php');
require_once('database/dbConnect.php');

// -------------------------------------------------------------------
// 1) SECURITY CHECKS
// -------------------------------------------------------------------
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    exit;
}

$loggedIn    = isset($_SESSION['_id']);
$accessLevel = $_SESSION['access_level'] ?? 0;
$isAdmin     = $accessLevel >= 2;
$userID      = $_SESSION['_id'] ?? null;

if (!$loggedIn) {
    header('Location: login.php');
    exit;
}

$id = $isAdmin && isset($_GET['id']) ? sanitize($_GET['id']) : $userID;

// -------------------------------------------------------------------
// 2) PROCESS FORM SUBMISSION
// -------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ignoreList = array('password');
    $args = sanitize($_POST, $ignoreList);
    $_SESSION['form_data'] = $args;

    // Required fields
    $required = ['name', 'email', 'address', 'freezer_meals', 'house_cleaning', 'lawn_care', 'aaa_membership'];
    if (!wereRequiredFieldsSubmitted($args, $required)) {
        header("Location: pointsProg.php?error=missing_fields");
        exit;
    }

    $name  = $args['name'];
    $email = $args['email'];

    // Validate email existence in DB
    $con = connect();
    $stmt = $con->prepare("SELECT id FROM dbPersons WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        header("Location: pointsProg.php?error=email_not_found");
        exit;
    }

    // Calculate points
    $points_used = 0;
    $freezer_meals = $args['freezer_meals'];
    $points_used += ($freezer_meals != 2) ? ($freezer_meals / 2) : 0;
    $points_used += $args['house_cleaning'] * 7;
    $points_used += $args['lawn_care'] * 3;

    // Handle checkbox selections
    $allergies = isset($args["allergies"]) ? implode(",", $args["allergies"]) : "";
    if (!empty($args["otherAllergyText"])) {
        $allergies .= ",Other: " . $args["otherAllergyText"];
    }

    $snacks = isset($args["snacks"]) ? implode(",", $args["snacks"]) : "";
    if (!empty($args["otherSnackText"])) {
        $snacks .= ",Other: " . $args["otherSnackText"];
    }

    $grocery = isset($args["grocery"]) ? implode(",", $args["grocery"]) : "";
    $gas = isset($args["gas"]) ? implode(",", $args["gas"]) : "";

    // Extract negative points from grocery and gas selections
    preg_match_all('/-(\d+)/', $grocery, $groceryMatches);
    foreach ($groceryMatches[1] as $m) {
        $points_used += intval($m);
    }

    preg_match_all('/-(\d+)/', $gas, $gasMatches);
    foreach ($gasMatches[1] as $m) {
        $points_used += intval($m);
    }

    // Validate date
    $AAA_membership_name = $args['aaa_membership_name'] ?? "";
    $AAA_membership_DOB = !empty($args['aaa_membership_dob']) ? validateDate($args['aaa_membership_dob']) : "";
    if ($AAA_membership_DOB === false) {
        header("Location: pointsProg.php?error=invalid_dob");
        exit;
    }

    // Ensure points do not exceed limit
    if ($points_used > 19) {
        header("Location: pointsProg.php?error=too_many_points");
        exit;
    }

    // Generate new ID if necessary
    $id = find_next_id() + 1;
    $giftCardPickUp = '';
    $monthly_community_package = $args['monthly_community_package'] ?? null;

    // Create new PointsProg object
    $newPointsProg = new PointsProg(
        $id, $name, $email, $args['address'], $freezer_meals, 
        $allergies, $snacks, 
        $grocery, $gas, 
        $args['house_cleaning'], $args['lawn_care'], 
        $args['aaa_membership'], $AAA_membership_name, $AAA_membership_DOB, 
        $points_used, $giftCardPickUp, $monthly_community_package
    );

    // Insert into DB
    $result = add_points_prog($newPointsProg);

    if (!$result) {
        die("Database Insert Failed: " . mysqli_error($con));
    }

    // Success: redirect to homepage
    header("Location: index.php?success=points_added");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>FGP | Points Program</title>
</head>
<body>
    <?php require_once('header.php'); ?>
    <?php
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            require_once('pointsProgForm.php'); 
        } else {
            echo "<p>Form processed successfully. If not redirected, <a href='index.php'>click here</a>.</p>";
        }
    ?>
</body>
</html>
