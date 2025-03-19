<?php
// pointsProg.php

session_cache_expire(30);
session_start();

require_once('include/input-validation.php');
require_once('domain/PointsProg.php');
require_once('database/dbPointsProg.php');
require_once('domain/Person.php');
require_once('database/dbPersons.php');

// -------------------------------------------------------------------
// 1) SECURITY CHECKS
// -------------------------------------------------------------------
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    exit;  // stop execution after redirect
}

$loggedIn    = false;
$accessLevel = 0;
$userID      = null;
$isAdmin     = false;

if (isset($_SESSION['_id'])) {
    $loggedIn    = true;
    $accessLevel = $_SESSION['access_level'];
    $isAdmin     = ($accessLevel >= 2);
    $userID      = $_SESSION['_id'];
} else {
    header('Location: login.php');
    exit;
}

// If admin & GET['id'], handle it
if ($isAdmin && isset($_GET['id'])) {
    $args = sanitize($_GET);
    $id   = strtolower($args['id']);
} else {
    $id = $userID;
}

// -------------------------------------------------------------------
// 2) PROCESS THE SUBMITTED FORM (IF POST)
// -------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1) Sanitize
    $ignoreList = array('password');
    $args       = sanitize($_POST, $ignoreList);
    $_SESSION['form_data'] = $args;

    // 2) Required fields
    $required = array('name', 'email', 'address', 'freezer_meals',
                      'house_cleaning', 'lawn_care', 'aaa_membership');
    
    $errors = false;
    if (!wereRequiredFieldsSubmitted($args, $required)) {
        $errors = true;
    }

    // 3) Validate input and possibly redirect if invalid
    $name  = $args['name'];
    $email = $args['email'];

    // Check DB for email
    $con    = connect();
    $query  = "SELECT * FROM dbPersons WHERE email = '$email'";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) == 0) {
        // Email not found, redirect with error
        header("Location: pointsProg.php?emailError");
        exit;
    }

    // Calculate the points total
    $points_used     = 0;
    $address         = $args['address'];
    $freezer_meals   = $args['freezer_meals'];
    if ($freezer_meals != 2) {
        $points_used += ($freezer_meals / 2);
    }
    $house_cleaning  = $args['house_cleaning'];
    $points_used    += ($house_cleaning * 7);
    $lawn_care       = $args['lawn_care'];
    $points_used    += ($lawn_care * 3);
    $AAA_membership  = $args['aaa_membership'];

    // Handle allergies
    $allergies = "";
    if (isset($args["allergies"])) {
        $allergies = implode(",", $args["allergies"]);
    }
    if (!empty($args["otherAllergyText"])) {
        $allergies .= ",Other: " . $args["otherAllergyText"];
    }

    // Handle snacks
    $snacks = "";
    if (isset($args["snacks"])) {
        $snacks = implode(",", $args["snacks"]);
    }
    if (!empty($args["otherSnackText"])) {
        $snacks .= ",Other: " . $args["otherSnackText"];
    }

    // Grocery
    $grocery = "";
    if (isset($args["grocery"])) {
        $grocery = implode(",", $args["grocery"]);
    }

    // Gas
    $gas = "";
    if (isset($args["gas"])) {
        $gas = implode(",", $args["gas"]);
    }

    // Use regex to add up points from grocery/gas
    $pattern = '/-(\d+)/';

    preg_match_all($pattern, $grocery, $matches);
    foreach ($matches[1] as $m) {
        $points_used += intval($m);
    }

    preg_match_all($pattern, $gas, $matches);
    foreach ($matches[1] as $m) {
        $points_used += intval($m);
    }

    // If any errors so far
    if ($errors) {
        header("Location: pointsProg.php?formError");
        exit;
    }

    // Optional fields
    $AAA_membership_name = !empty($args['aaa_membership_name']) ? $args['aaa_membership_name'] : "";
    $AAA_membership_DOB  = "";
    if (!empty($args['aaa_membership_dob'])) {
        $AAA_membership_DOB = validateDate($args['aaa_membership_dob']);
        if (!$AAA_membership_DOB) {
            // invalid DOB
            header("Location: pointsProg.php?badDOB");
            exit;
        }
    }

    // Check if too many points
    if ($points_used > 19) {
        header("Location: pointsProg.php?pointsError");
        exit;
    }

    // Prepare domain object
    $id = find_next_id() + 1;  // or however you handle new ID
    $giftCardPickUp             = '';
    $monthly_community_package  = isset($args['monthly_community_package']) ? $args['monthly_community_package'] : null;

    $newpointsprog = new PointsProg(
        $id, $name, $email, $address, $freezer_meals, 
        $allergies, $snacks, 
        $grocery, $gas, 
        $house_cleaning, $lawn_care, 
        $AAA_membership, $AAA_membership_name, $AAA_membership_DOB, 
        $points_used,
        $giftCardPickUp, 
        $monthly_community_package
    );

    // Insert to DB
    $result = add_points_prog($newpointsprog);

    // If DB insert fails
    if (!$result) {
        header("Location: pointsProg.php?dbError");
        exit;
    }

    // Success => redirect
    header("Location: index.php?pointsProgSuccess");
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
    <?php
        require_once('header.php');
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            require_once('pointsProgForm.php'); 
        } else {
            echo "<p>Form processed without redirection.</p>";
        }
    ?>
</body>
</html>
