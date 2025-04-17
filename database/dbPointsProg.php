<?php
// Authors: Harmony Peura and Grayson Jones
include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/PointsProg.php');

//Add a new row to the Points Program table. If the given email is already
// in the table, update that row.
function add_points_prog($pointsprog) {
    if (!$pointsprog instanceof PointsProg)
        die("Error: add_points_prog type mismatch");
    $con=connect();

    $query = "SELECT * FROM dbPointsProg WHERE email = '" . $pointsprog->getEmail() . "'";
    $result = mysqli_query($con, $query);
    
    // Check if there's any entry for this email
    if ($result && mysqli_num_rows($result) > 0) {
        // Update the existing entry with the new data
        mysqli_query($con, "UPDATE dbPointsProg SET 
            id = '" . $pointsprog->getId() . "',
            name = '" . $pointsprog->getName() . "',
            email = '" . $pointsprog->getEmail() . "',
            address = '" . $pointsprog->getAddress() . "',
            freezer_meals = '" . $pointsprog->getFreezerMeals() . "',
            allergies = '" . $pointsprog->getAllergies() . "',
            snacks = '" . $pointsprog->getSnacks() . "',
            grocery = '" . $pointsprog->getGrocery() . "',
            gas = '" . $pointsprog->getGas() . "',
            house_cleaning = '" . $pointsprog->getHouseCleaning() . "',
            lawn_care = '" . $pointsprog->getLawnCare() . "',
            aaa_membership = '" . $pointsprog->getAAAMembership() . "',
            aaa_membership_name = '" . $pointsprog->getAAAMembershipName() . "',
            aaa_membership_dob = '" . $pointsprog->getAAAMembershipDOB() . "',
            points_used = '" . $pointsprog->getPointsUsed() . "',
            giftCardPickUp = '" . $pointsprog->getGiftCardPickUp() . "',
            monthly_community_package = '" . $pointsprog->getMonthlyPackage() . "' 
            WHERE email = '" . $pointsprog->getEmail() . "'
        ");
        mysqli_close($con);
        return true;
    } 
    else {
        $query = "SELECT * FROM dbPointsProg WHERE id = '" . $pointsprog->getId() . "'";
        $result = mysqli_query($con,$query);
        //if there's no entry for this id, add it
        if ($result == null || mysqli_num_rows($result) == 0) {
            mysqli_query($con,'INSERT INTO dbPointsProg VALUES("' .
                $pointsprog->getId() . '","' .
                $pointsprog->getName() . '","' .
                $pointsprog-> getEmail(). '","'.
                $pointsprog->getAddress() . '","' .
                $pointsprog->getFreezerMeals() . '","' .
                $pointsprog->getAllergies() . '","' .
                $pointsprog->getSnacks() . '","' .
                $pointsprog->getSnackNotes() . '","' .
                $pointsprog->getGrocery() . '","' .
                $pointsprog->getGas() . '","' .
                $pointsprog->getHouseCleaning() . '","' .
                $pointsprog->getLawnCare() . '","' .
                $pointsprog->getAAAMembership() . '","' .
                $pointsprog->getAAAMembershipName() . '","' .
                $pointsprog->getAAAMembershipDOB() . '","' .
                $pointsprog->getPointsUsed() . '","' .
                $pointsprog->getGiftCardPickUp() . '","' .
                $pointsprog->getMonthlyPackage() .
                '");'
            );							
            mysqli_close($con);
            return true;
        }
        mysqli_close($con);
        return false;
    }
}

// Remove a row from the PointsProg table. Not currently used anywhere.
function remove_points_prog($id) {
    $con=connect();
    $query = 'SELECT * FROM dbPointsProg WHERE id = "' . $id . '"';
    $result = mysqli_query($con,$query);
    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_close($con);
        return false;
    }
    $query = 'DELETE FROM dbPointsProg WHERE id = "' . $id . '"';
    $result = mysqli_query($con,$query);
    mysqli_close($con);
    return true;
}

// Retreive a row from the PointsProg table. Not currently used anywhere.
function retrieve_points_prog($id) {
    $con=connect();
    $query = "SELECT * FROM dbPointsProg WHERE id = '" . $id . "'";
    $result = mysqli_query($con,$query);
    if (mysqli_num_rows($result) !== 1) {
        mysqli_close($con);
        return false;
    }
    $result_row = mysqli_fetch_assoc($result);
    // var_dump($result_row);
    $thePointsProg = make_a_points_prog($result_row);
//    mysqli_close($con);
    return $thePointsProg;
}

// Finds the most recent points program entry with the given email
function retrieve_points_prog_by_email($email) {
    $con=connect();
    $query = "SELECT * FROM dbPointsProg WHERE email = '" . $email . "' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($con,$query);
    if (mysqli_num_rows($result) !== 1) {
        mysqli_close($con);
        return false;
    }
    $result_row = mysqli_fetch_assoc($result);
    // var_dump($result_row);
    $thePointsProg = make_a_points_prog($result_row);
//    mysqli_close($con);
    return $thePointsProg;
}


// Make a new PointsProg instance.     
function make_a_points_prog($result_row) {
    $thePointsProg = new PointsProg(
        $result_row['id'],
        $result_row['name'],
        $result_row['email'],
        $result_row['address'],
        $result_row['freezer_meals'],
        $result_row['allergies'],
        $result_row['snacks'],
        $result_row['grocery'],
        $result_row['gas'],
        $result_row['house_cleaning'],
        $result_row['lawn_care'],
        $result_row['AAA_membership'],
        $result_row['AAA_membership_name'],
        $result_row['AAA_membership_DOB'],
        $result_row['points_used'],
        $result_row['giftCardPickUp']
    );   
    return $thePointsProg;
}

// Fetch everything from the table.
function getall_pointsProgs() {
    $con=connect();
    $query = 'SELECT * FROM dbPointsProg';
    $result = mysqli_query($con,$query);
    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_close($con);
        return false;
    }
    $result = mysqli_query($con,$query);
    $thePointsProgs = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $thePointsProg = make_a_points_prog($result_row);
        $thePointsProgs[] = $thePointsProg;
    }

    return $thePointsProgs;
}

// Generate a unique ID for a new row.
function find_next_id() {
    $query = "SELECT MAX(id) AS max_id FROM dbPointsProg";
    $connection = connect();

    if (!$connection) {
        // Connection failed, return null or handle error accordingly
        return null;
    }

    $result = mysqli_query($connection, $query);

    if (!$result) {
        // Query execution failed
        mysqli_close($connection);
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($connection);

    if (!$row || empty($row['max_id'])) {
        // No max vendorID found
        return null;
    }

    return $row['max_id'];
}

function renderFamilyDropdown() {
    global $db, $_SESSION;

    if ($_SESSION['access_level'] >= 2) {
        $people = getall_families();

        usort($people, function($a, $b) {
            return strcasecmp($a->get_first_name() . $a->get_last_name(), $b->get_first_name() . $b->get_last_name());
        });

        echo '<select id="familyDropdown" name="family" onchange="fillForm()">';
        echo '<option value="" data-name="" data-email="" data-address="" data-aaa-name="" data-aaa-dob="">Select a person</option>'; // Default option

        foreach ($people as $person) {
            if ($person->get_access_level() < $_SESSION['access_level']) {
                // Fetch AAA membership details using the existing functions
                $aaaName = retrieve_aaa_name_by_email($person->get_email());
                $aaaDob = retrieve_aaa_dob_by_email($person->get_email());

                echo "<option value='{$person->get_id()}'
                    data-name='{$person->get_first_name()} {$person->get_last_name()}'
                    data-email='{$person->get_email()}'
                    data-address='{$person->get_address()}'
                    data-aaa-name='{$aaaName}'
                    data-aaa-dob='{$aaaDob}'>";
                echo "{$person->get_first_name()} ({$person->get_last_name()}) -- {$person->get_email()}";
                echo "</option>";
            }
        }
        echo '</select>';
    }
}


function autoFillPointsFormFamily($userID) {
    global $db, $_SESSION;

    if ($_SESSION['access_level'] == 1) {
        $people = getall_families();

        foreach ($people as $person) {
            if ($person->get_email() == $userID) { // Match user by email
                $fullName = "{$person->get_first_name()} {$person->get_last_name()}";
                $email = $person->get_email();
                $address = $person->get_address();

                // Fetch AAA membership details
                $aaaName = retrieve_aaa_name_by_email($email);
                $aaaDob = retrieve_aaa_dob_by_email($email);

                // Automatically set the values in the form
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('name').value = '" . addslashes($fullName) . "';
                        document.getElementById('email').value = '" . addslashes($email) . "';
                        document.getElementById('address').value = '" . addslashes($address) . "';
                        document.getElementById('aaa_membership_name').value = '" . addslashes($aaaName) . "';
                        document.getElementById('aaa_membership_dob').value = '" . addslashes($aaaDob) . "';

                        // Check 'Already Have It' if AAA membership exists
                        if ('" . addslashes($aaaName) . "' !== '') {
                            document.getElementById('aaa_already_have_it').checked = true;
                        }
                    });
                </script>";
                return; // Stop after setting the first match
            }
        }
    }
}


function retrieve_aaa_name_by_email($email) {
    $con = connect();
    if (!$con) return "";

    $email = mysqli_real_escape_string($con, $email);
    $query = "SELECT AAA_membership_name FROM dbPointsProg WHERE email = '$email'";
    $result = mysqli_query($con, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_close($con);
        return $row['AAA_membership_name'] ?? "";
    }

    mysqli_close($con);
    return "";
}

function retrieve_aaa_dob_by_email($email) {
    $con = connect();
    if (!$con) return "";

    $email = mysqli_real_escape_string($con, $email);
    $query = "SELECT AAA_membership_dob FROM dbPointsProg WHERE email = '$email'";
    $result = mysqli_query($con, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_close($con);
        return $row['AAA_membership_dob'] ?? "";
    }

    mysqli_close($con);
    return "";
}

/**
 * Retrieve the most recent Points Program submission for a given email address.
 *
 * This function queries the dbPointsProg table and returns the latest
 * record (based on ID) submitted by the specified email.
 *
 * @param string $email  The email address to search for
 * @return array                The most recent submission as an associative array,
 *                                         or an empty array if no record is found
 */
function get_points_prog_from_email($email) {
        $con = connect(); // Assumes connect() returns a valid MySQLi connection
        $email = mysqli_real_escape_string($con, $email);

        $query = "SELECT * FROM dbPointsProg WHERE email = '$email' ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($con, $query);       

        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }       
        
        return [];
}

?>


<script>
function fillForm() {
    var dropdown = document.getElementById("familyDropdown");
    var selectedOption = dropdown.options[dropdown.selectedIndex];

    // Fill personal info fields
    document.getElementById("name").value = selectedOption.getAttribute("data-name") || "";
    document.getElementById("email").value = selectedOption.getAttribute("data-email") || "";
    document.getElementById("address").value = selectedOption.getAttribute("data-address") || "";

    // Fill AAA Membership fields
    var aaaName = selectedOption.getAttribute("data-aaa-name") || "";
    var aaaDob = selectedOption.getAttribute("data-aaa-dob") || "";

    document.getElementById("aaa_membership_name").value = aaaName;
    document.getElementById("aaa_membership_dob").value = aaaDob;

    // Automatically check "Already Have It" if an AAA membership exists
    if (aaaName.trim() !== "") {
        document.getElementById("aaa_already_have_it").checked = true;
    }
}
</script>



