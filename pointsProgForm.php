<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once('include/input-validation.php');
require_once('database/dbPersons.php');
include_once('database/dbinfo.php');
require_once ('pointsProgHandler.php');

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    die();
}

$loggedIn = isset($_SESSION['_id']);
$accessLevel = $_SESSION['access_level'];
$isAdmin = $accessLevel >= 2;
$userID = $_SESSION['_id'];
$id = ($isAdmin && isset($_GET['id'])) ? strtolower(sanitize($_GET)['id']) : $userID;

$con = connect();
$grocery_vendors = mysqli_query($con, "SELECT * FROM `dbGiftCardVendors` WHERE vendorType = 'grocery'");
$gas_vendors = mysqli_query($con, "SELECT * FROM `dbGiftCardVendors` WHERE vendorType = 'gas'");
?>

<!DOCTYPE html>
<html>

<head>
    <script src="js/fillForm.js"></script>
    <?php require_once('universal.inc'); ?>
    <title>FGP | Points Program</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <h1>Points Program</h1>

    <?php if (isset($_GET['pointsError'])): ?>
    <div class="error-toast">More than 19 points were used. Please modify your choices and resubmit.</div>
    <?php endif; ?>

    <?php if (isset($_GET['emailError'])): ?>
    <div class="error-toast">The email you entered was not found in our system. Please try again.</div>
    <?php endif; ?>

    <main class="date">
        <h2>Points Program Form</h2>
        <form id="new-points-prog-form" method="post" action="reviewPointsProg.php">
            <p>You have <b>19 points</b> to spend each month.</p>

            <?php renderFamilyDropdown(); ?>
            <script>
            document.getElementById("familyDropdown").addEventListener("change", fillForm());
            </script>

            <?php autoFillPointsFormFamily($userID); ?>

            <label for="name">Name</label>
            <input type="text" id="name" name="name" required readonly>

            <label for="email">Email</label>
            <input type="text" id="email" name="email" required readonly>

            <label for="address">Address</label>
            <input type="text" id="address" name="address" required readonly>

            <?php renderFreezerMealsSection(); ?>
            <?php renderAllergySection(); ?>

            <label>Grocery Store Gift Cards</label>
            <?php renderGiftCardSelect($grocery_vendors, 'grocery'); ?>

            <br><br>
            <label>Gas Gift Cards</label>
            <?php renderGiftCardSelect($gas_vendors, 'gas'); ?>

            <br><br>
            <?php renderHouseCleaningSection(); ?>
            <?php renderLawnCareSection(); ?>

            <label for="monthly_community_package">Would you like to receive a monthly community care package? <span
                    style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" name="monthly_community_package" value="yes" required> Yes</li>
                <li><input type="radio" name="monthly_community_package" value="no" required> No</li>
            </ul>

            <?php renderAAAMembershipSection(); ?>

            <br>
            <input type="submit" name="form_data" value="Submit">
        </form>
    </main>

</body>

</html>