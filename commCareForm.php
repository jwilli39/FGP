<?php
    // Authors: Harmony Peura and Grayson Jones
    // Stores input from the Community Care Package Form into an SQL table.

    // session_cache_expire(30);
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
    function autoFillCommCareFormFamily($userID) {
        global $db, $_SESSION;
    
        if ($_SESSION['access_level'] == 1) {
            $people = getall_families();
    
            foreach ($people as $person) {
                if ($person->get_email() == $userID) { // Match user by email
                    $email = $person->get_email();
    
                    // Automatically set the values in the form
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('email').value = '" . addslashes($email) . "';
                        });
                    </script>";
                    return; // Stop after setting the first match
                }
            }
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once('include/input-validation.php');
        require_once('database/dbCommCare.php');
        // Sanitize input
        $args = sanitize($_POST, null);
        $required = array(
            "adultNames", "childrenInfo", "sportsFan", "sitDinner", "fastFood", "sweetTreat", "faveSweet", "faveSalt",
            "faveCandy", "faveCookie", "forFun", "warmAct", "coldAct"
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
            
            // Not used, scared to delete
            $name = htmlspecialchars_decode($args['name']);
            $date = date('l, F j, Y', strtotime($date));
            require_once('database/dbMessages.php');
            system_message_all_users_except($userID, "A new event was created!", "Exciting news!\r\n\r\nThe [$name](event: $id) event at $startTime on $date was added!\r\nSign up today!");
            header("Location: event.php?id=$id&createSuccess");
            die();
        }
    }
    $date = null;
    if (isset($_GET['date'])) {
        $date = $_GET['date'];
        $datePattern = '/[0-9]{4}-[0-9]{2}-[0-9]{2}/';
        $timeStamp = strtotime($date);
        if (!preg_match($datePattern, $date) || !$timeStamp) {
            header('Location: calendar.php');
            die();
        }
    }

    // Also not used, but scared to delete and breaks some sort of dependency
    include_once('database/dbinfo.php'); 
    $con=connect();  

?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once('universal.inc') ?>
    <title>FGP | Community Care Package Form</title>
</head>

<body>
    <?php require_once('header.php') ?>
    <h1>Community Care Package Form</h1>
    <?php if (isset($_GET['emailError'])): ?>
    <div class="error-toast">The email you entered was not found in our system. Please try again.</div>
    <?php endif ?>
    <main class="date">
        <h2>Community Care Package Form</h2>
        <p>Please fill out the form below. Required fields are marked with an asterisk (<span
                style="color: red;">*</span>)</p>
        <form id="new-event-form" method="post">

        <?php renderFamilyDropdown(); ?>
        <?php autoFillCommCareFormFamily($userID); ?>

            <!--Adult names and children info -->
            <label for="email">Email </label>
            <input type="text" id="email" name="email" required placeholder="Enter email" readonly>
            <label for="adultNames">Names of adults in household </label>
            <input type="text" id="adultNames" name="adultNames" required placeholder="Enter adult names">
            <label for="childrenInfo">For children in the house hold: list names,ages, genders and clothing
                sizes</label>
            <input type="text" id="childrenInfo" name="childrenInfo" required placeholder="Enter children information">
            <!--Sports Fans? If so, what teams do you like? -->
            <label for="sportsFan">Are you sports fans? <span style="color: red;">*</span></label>
            <ul>
                <li><input type="radio" id="sportsYes" name="sportsFan" value=1 required> Yes </li>
                <li><input type="radio" id="sportsNo" name="sportsFan" value=0> No </li>
            </ul>
            <label for="sportsInfo">What teams do you enjoy cheering for? </label>
            <input type="text" id="sportsInfo" name="sportsInfo" placeholder="Enter team names">
            <!--Resturants and Snack Preferences -->
            <label for="sitDinner">Where do you like to eat a sit-down dinner?</label>
            <input type="text" id="sitDinner" name="sitDinner" required placeholder="Enter a restaurant name">
            <label for="fastFood">What are your favorite fast food restaurants?</label>
            <input type="text" id="fastFood" name="fastFood" required placeholder="Enter a restaurant name">
            <label for="sweetTreat">Where do you like to grab a sweet treat?</label>
            <input type="text" id="sweetTreat" name="sweetTreat" required placeholder="Enter a restaurant/store name">
            <label for="faveSweet">What is your favorite sweet treat?</label>
            <input type="text" id="faveSweet" name="faveSweet" required placeholder="Enter a sweet treat">
            <label for="faveSalt">What is your favorite salty snack?</label>
            <input type="text" id="faveSalt" name="faveSalt" required placeholder="Enter a salty snack">
            <label for="faveCandy">What is your favorite candy? </label>
            <input type="text" id="faveCandy" name="faveCandy" required placeholder="Enter a candy name">
            <label for="faveCookie">What is your favorite cookie?</label>
            <input type="text" id="faveCookie" name="faveCookie" required placeholder="Enter a cookie name">
            <!--Activity Preferences -->
            <label for="forFun">What does your family like to do for fun?</label>
            <input type="text" id="forFun" name="forFun" required placeholder="Enter an activity">
            <label for="warmAct">What is your favorite warm weather activity? </label>
            <input type="text" id="warmAct" name="warmAct" required placeholder="Enter an activity name">
            <label for="coldAct">What is your favorite cold weather activity?</label>
            <input type="text" id="coldAct" name="coldAct" required placeholder="Enter an activity name">
            <label for="notes">What else would you like us to know about your family?</label>
            <input type="text" id="notes" name="notes" placeholder="Enter any other information">

            <input type="submit" value="Submit">
        </form>
        <?php if ($date): ?>
        <a class="button cancel" href="calendar.php?month=<?php echo substr($date, 0, 7) ?>"
            style="margin-top: -.5rem">Return to Calendar</a>
        <?php else: ?>
        <a class="button cancel" href="index.php" style="margin-top: -.5rem">Return to Dashboard</a>
        <?php endif ?>
        <!-- Require at least one checkbox be checked -->
        <script type="text/javascript">
        $(document).ready(function() {
            var checkboxes = $('.checkboxes');
            checkboxes.change(function() {
                if ($('.checkboxes:checked').length > 0) {
                    checkboxes.removeAttr('required');
                } else {
                    checkboxes.attr('required', 'required');
                }
            });
        });
        </script>
        <script>
        function fillForm() {
            var dropdown = document.getElementById("familyDropdown");
            var selectedOption = dropdown.options[dropdown.selectedIndex];
            document.getElementById("email").value = selectedOption.getAttribute("data-email") || "";
        }
        </script>
    </main>
</body>

</html>