<?php

    // Authors: Harmony Peura and Grayson Jones
    // HTML form for the Points Program Form, to be filled out by families.

    //session_cache_expire(30);
    //session_start();
    ini_set("display_errors",1);
    error_reporting(E_ALL);

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
        // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 super admin (TBI)
        $accessLevel = $_SESSION['access_level'];
        $isAdmin = $accessLevel >= 2;
        $userID = $_SESSION['_id'];        
    } 

    else {
        header('Location: login.php');
        die();
    }

    if ($isAdmin && isset($_GET['id'])) {
        require_once('include/input-validation.php');
        $args = sanitize($_GET);
        $id = strtolower($args['id']);
    } else {
        $id = $userID; // Use session ID if no ID provided in GET parameters
    }
    
    require_once('database/dbPersons.php');
    
    // Generates a dropdown menu of families. (When logged in as Admin)
    // Only includes families with a lower access level than the current user.
    function renderFamilyDropdown() {
        global $db, $_SESSION;
    
        if ($_SESSION['access_level'] >= 2) {
            // Admin: Show dropdown with sorted options
            $people = getall_families();
    
            // Sort people alphabetically (case-insensitive)
            usort($people, function($a, $b) {
                return strcasecmp($a->get_first_name() . $a->get_last_name(), $b->get_first_name() . $b->get_last_name());
            });
    
            echo '<select id="familyDropdown" name="family" onchange="fillForm()">';
            echo '<option value="" data-name="" data-email="" data-address="">Select a person</option>'; // Default option
    
            foreach ($people as $person) {
                if ($person->get_access_level() < $_SESSION['access_level']) {
                    echo "<option value='{$person->get_id()}' data-name='{$person->get_first_name()} {$person->get_last_name()}' data-email='{$person->get_email()}' data-address='{$person->get_address()}'>";
                    echo "{$person->get_first_name()} ({$person->get_last_name()}) -- {$person->get_email()}";
                    echo "</option>";
                }
            }
            echo '</select>';
        }
    }

    // Auto fill the name, email, and address for points program form
    // when logged in as a family account
    function autoFillPointsFormFamily($userID) {
        global $db, $_SESSION;
    
        if ($_SESSION['access_level'] == 1) {
            $people = getall_families();
    
            foreach ($people as $person) {
                if ($person->get_email() == $userID) { // Match user by email
                    $fullName = "{$person->get_first_name()} {$person->get_last_name()}";
                    $email = $person->get_email();
                    $address = $person->get_address();
    
                    // Automatically set the values in the form
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('name').value = '" . addslashes($fullName) . "';
                            document.getElementById('email').value = '" . addslashes($email) . "';
                            document.getElementById('address').value = '" . addslashes($address) . "';
                        });
                    </script>";
                    return; // Stop after setting the first match
                }
            }
        }
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once('include/input-validation.php');
        require_once('database/dbPointsProg.php');
        // Sanitize input, check for required fields
        $args = sanitize($_POST, null);
        $required = array(
            "name", "address", "freezer_meals", "allergies", "snacks", "snack_notes", "house_cleaning", "lawn_care",
            "AAA_membership", "photography", "house_projects", "financial_relief"
        );
        if (!wereRequiredFieldsSubmitted($args, $required)) {
            echo 'bad form data';
            die();
        } else {
            $date = $args['AAA_membership_DOB'] = validateDate($args["AAA_membership_DOB"]);
            $id = ($args);
            if(!$id){
                echo "Oopsy!";
                die();
            }
            require_once('include/output.php');
        }
    }

    // Not used, but scared to remove.
    include_once('database/dbinfo.php'); 
    $con=connect();  

    // Fetch all gift card vendors to be displayed in the form.
    $sql = "SELECT * FROM `dbGiftCardVendors`";
    $all_vendors = mysqli_query($con,$sql);

    // Fetch all gift cards for grocery vendors
    $sql_grocery = "SELECT * FROM `dbGiftCardVendors` where vendorType = 'grocery'";
    $grocery_vendors = mysqli_query($con,$sql_grocery);

    // Fetch all gift cards for gas vendors
    $sql_gas = "SELECT * FROM `dbGiftCardVendors` where vendorType = 'gas'";
    $gas_vendors = mysqli_query($con,$sql_gas);
?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once('universal.inc') ?>
    <title>FGP | Points Program</title>
</head>

<body>
    <?php require_once('header.php') ?>
    <h1>Points Program</h1>
    <?php if (isset($_GET['pointsError'])): ?>
    <div class="error-toast">More than 19 points were used. Please modify your choices and resubmit. </div>
    <?php endif ?>
    <?php if (isset($_GET['emailError'])): ?>
    <div class="error-toast">The email you entered was not found in our system. Please try again.</div>
    <?php endif ?>
    <main class="date">
        <h2>Points Program Form</h2>
        <form id="new-points-prog-form" method="post">

            <p> Our goal is to provide servies that meet your needs as a family during this
                difficult time. You have a total of <b>19 points</b> "to spend" each month.
                Your choices will remain the same each month unless you change them using
                this form.
            </p>

            <?php renderFamilyDropdown(); ?>

            <?php autoFillPointsFormFamily($userID); ?>

            <p>Please fill out the form below. Required fields are marked with an asterisk (<span
                    style="color: red;">*</span>)</p>

            <label for="name">Name </label>
            <input type="text" id="name" name="name" required placeholder="Enter contact name" readonly>

            <label for="email">Email </label>
            <input type="text" id="email" name="email" required placeholder="Enter email" readonly>

            <label for="address">Address </label>
            <input type="text" id="address" name="address" required placeholder="Enter address" readonly>
            <label for="freezer_meals_and_snacks">Freezer Meals & Snacks </label>
            <p><b>We offer two freezer meals and snacks per month at NO CHARGE to your points.</b>
                Freezer meals will be delivered on the third <b>Tuesday</b> of even months
                (February, April, June, August, October and December) and snacks will
                be delivered on the third <b>Tuesday</b> of the odd months
                (January, March, May, July, September and November)
                On freezer meal months, we ask that you leave a cooler on your doorstep.
                A volunteer will leave the meals in the cooler without ringing the doorbell
                and there's no need for you to be home.
            </p>

            <label for="freezer_meals_and_snacks">How many freezer meals would you like? <span
                    style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="freezer_2" name="freezer_meals" value=2> 2 Meals per month (Free)</li>
                <li><input type="radio" id="freezer_4" name="freezer_meals" value=4> 4 meals per month (2 points)</li>
                <li><input type="radio" id="freezer_6" name="freezer_meals" value=6> 6 meals per month (3 points)</li>
                <li><input type="radio" id="freezer_8" name="freezer_meals" value=8> 8 meals per month (4 points)</li>
                <li><input type="radio" id="freezer_0" name="freezer_meals" value=0> We do not want ANY freezer meals
                </li>
            </ul>

            <label for="allergies">Are there any food allergies that we need to be aware of? <span
                    style="color: red;">*</span></label>
            <ul>
                <li><input type="checkbox" id="peanuts" name="allergies[]" value="peanuts"> Peanuts</li>
                <li><input type="checkbox" id="treenuts" name="allergies[]" value="tree nuts"> Tree Nuts</li>
                <li><input type="checkbox" id="gluten" name="allergies[]" value="gluten"> Gluten</li>
                <li><input type="checkbox" id="soy" name="allergies[]" value="soy"> Soy</li>
                <li><input type="checkbox" id="egg" name="allergies[]" value="egg"> Egg</li>
                <li><input type="checkbox" id="dairy" name="allergies[]" value="dairy"> Dairy</li>
                <li><input type="checkbox" id="no allergies" name="allergies[]" value="no allergies"> No Known Allergies
                </li>
                <li><input type="checkbox" id="otherAllergy" name="otherAllergy" value="other"> Other:</li>
                <li><input type="text" name="otherAllergyText" placeholder="Enter other allergy"></li>
            </ul>

            <label for="snack_notes">Are there any snacks that your child/children do not prefer or will not eat? Is
                there anything else we should know when considering snacks for your family? </label>
            <input type="text" id="snack_notes" name="snack_notes" required placeholder="Your answer">
            <br><br>
            <label for="name">Grocery Store Gift Cards </label>
            <p>We only offer gift cards from stores that allow us to
                purchase the cards online. Shoppers Food Warehouse
                and Aldi do not currently have that service.
                Please note that Walmart does not allow shipments to
                PO Boxes. Please select the grocery store gift cards
                you would like.
            </p>
            <?php
                // Check if there are any vendors
                if (mysqli_num_rows($grocery_vendors) > 0) {
                    // Loop through each row in the result set
                    while ($vendor = mysqli_fetch_array($grocery_vendors, MYSQLI_ASSOC)) {
                            echo '<label for="'. $vendor['vendorName'] .'">'. $vendor['vendorName'] .' <span style="color: red;">*</span></label>';
                            echo '<select name="grocery[]" id="'. $vendor['vendorName'] .'">';
                            echo '<option value="none">No '. $vendor['vendorName'] .' Gift Cards</option>';
                            $numCards = 1;
                            for ($i = 25; $i <= 400; $i += 25) {
                                $value = $vendor['vendorName'] . "-" . $numCards ;
                                echo '<option value="'. $value .'" id="'. $value .'">$'. $i .' '. $vendor['vendorName'] . ' Gift Card ('. ($i / 25) .' points)</option>';
                                $numCards++;
                            }
                            echo '</select>';
                    }
                } else {
                        // Handle case when there are no vendors
                        echo "No vendors found.";
                }
                ?>

            <br><br>
            <label for="name">Gas Gift Cards</label>
            <p> Please select the gas gift cards you would like.</p>
            <?php
                // Check if there are any vendors
                if (mysqli_num_rows($gas_vendors) > 0) {
                    // Loop through each row in the result set
                    while ($vendor = mysqli_fetch_array($gas_vendors, MYSQLI_ASSOC)) {
                            echo '<label for="'. $vendor['vendorName'] .'">'. $vendor['vendorName'] .' <span style="color: red;">*</span></label>';
                            echo '<select name="gas[]" id="'. $vendor['vendorName'] .'">';
                            echo '<option value="none">No '. $vendor['vendorName'] .' Gift Cards</option>';
                            $numCards = 1;
                            for ($i = 25; $i <= 400; $i += 25) {
                                $value = $vendor['vendorName'] ."-". $numCards ;
                                echo '<option value="'. $value .'" id ="'. $value .'">$'. $i .' '. $vendor['vendorName'] . ' Gift Card ('. ($i / 25) .' points)</option>';
                                $numCards++;
                            }
                            echo '</select>';
                    }
                } else {
                        // Handle case when there are no vendors
                        echo "No vendors found.";
                }
                ?>
            <br><br>
            <label for="house_cleaning" required>Would you like house cleaning? <span
                    style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="house_cleaning_1" name="house_cleaning" value=1 required> Once a month (7
                    points)</li>
                <li><input type="radio" id="house_cleaning_2" name="house_cleaning" value=2 required> Twice a month (14
                    points)</li>
                <li><input type="radio" id="house_cleaning_0" name="house_cleaning" value=0 required> We do not want
                    house cleaning</li>
            </ul>

            <label for="lawn_care" required>Would you like lawn care? <span style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="lawn_care_yes" name="lawn_care" value=1 required> Yes (3 points per month)
                </li>
                <li><input type="radio" id="lawn_care_no" name="lawn_care" value=0 required> We do not want lawn care
                </li>
            </ul>

            <label for="aaa_membership" required>Would you like a AAA Plus Membership? <span
                    style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="aaa_yes" name="aaa_membership" value="Yes" required> Yes</li>
                <li><input type="radio" id="aaa_no" name="aaa_membership" value="No" required> No</li>
            </ul>

            <p> If yes to AAA Membership, please provide the responsible party's name and date of birth. </p>

            <label for="aaa_membership_name"> Responsible Party's Name </label>
            <input type="text" id="aaa_membership_name" name="aaa_membership_name" placeholder="Enter name">

            <label for="aaa_membership_dob"> Responsible Party's Date of Birth </label>
            <input type="date" id="aaa_membership_dob" name="aaa_membership_dob" placeholder="Date of birth"
                max="<?php echo date('Y-m-d'); ?>">

            <label for="photography"> Photography </label>
            <p>
                We offer your family two sessions of photography.
                We will do one during treatment and again after treatment has finished.
                There is no charge to your points for this service.
            </p>

            <label for="photography" required>Are you interested in a photography session? <span
                    style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="photo_yes" name="photography" value="Yes" required> Yes</li>
                <li><input type="radio" id="photo_no" name="photography" value="No" required> No</li>
            </ul>

            <label for="add_services"> Additional Services </label>
            <p>
                We currently offer to help with house projects and financial relief twice a year.
                If you are interested we will contact you when these services become available
            </p>
            <label for="house_projects" required>House Projects <span style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="house_more_info" name="house_projects" value="More info requested" required>
                    We would like more information when available</li>
                <li><input type="radio" id="house_not_interested" name="house_projects" value="Not interested" required>
                    We are not interested in house projects</li>
            </ul>

            <label for="financial_relief" required>Financial Relief <span style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="relief_more_info" name="financial_relief" value="More info requested"
                        required> We would like more information when available</li>
                <li><input type="radio" id="relief_not_interested" name="financial_relief" value="Not interested"
                        required> We are not interested in financial relief</li>
            </ul>
            <br>
            <input type="submit" name="form_data" value="Submit">
        </form>

    </main>
    <script>
    function fillForm() {
        var dropdown = document.getElementById("familyDropdown");
        var selectedOption = dropdown.options[dropdown.selectedIndex];

        document.getElementById("name").value = selectedOption.getAttribute("data-name") || "";
        document.getElementById("email").value = selectedOption.getAttribute("data-email") || "";
        document.getElementById("address").value = selectedOption.getAttribute("data-address") || "";
    }
    </script>
</body>

</html>
