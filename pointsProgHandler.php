<?php
/**
* ============================================================================
*  pointsProgHandler.php
*
*  Core utility module for handling the Points Program form.
*  Provides:
*    - Construction of PointsProg domain objects
*    - Points calculation logic
*    - Dynamic rendering of form sections
*    - Data retrieval + change detection for submissions
*
*  Relies on:
*    - PointsProg domain model
*    - dbPointsProg database access layer
*    - Input sanitation & validation helpers
*
* @author   Josh Williams
* ============================================================================
*/

require_once('domain/PointsProg.php');
require_once('database/dbPointsProg.php');
require_once('include/input-validation.php');

/**
 * ------------------------------------------------------------------------
 *  FORM SECTION RENDER
 *  These functions generate individual sections of the HTML form.
 *  Each one handles a specific service or option, such as:
 * 
 *    [1]  ->   Freezer meals
 *    [2]  ->  Allergies
 *    [3]  ->  House cleaning
 *    [4]  ->  Lawn care
 *    [5]  ->  AAA membership
 *  Used to display selectable options that contribute to point totals.
 * ------------------------------------------------------------------------
 */

    /**
     * [1]... (Freezer Meals)
     * Renders a form section for selecting the number of freezer meals.
     *
     * Displays radio button options that allow the user to choose how many
     * freezer meals they would like to receive each month. Each selection
     * corresponds to a specific number of points used, except the 2-meal option
     * which is provided for free.
     *
     * No parameters.
     * @return void
     */
     function renderFreezerMealsSection() {
         echo <<<HTML
         <label for="freezer_meals_and_snacks" required>How many freezer meals would you like? <span style="color: red;">*</span></label>
         <ul>
             <li><input type="radio" id="freezer_2" name="freezer_meals" value="2" required> 2 Meals per month (Free)</li>
             <li><input type="radio" id="freezer_4" name="freezer_meals" value="4"> 4 meals per month (2 points)</li>
             <li><input type="radio" id="freezer_6" name="freezer_meals" value="6"> 6 meals per month (3 points)</li>
             <li><input type="radio" id="freezer_8" name="freezer_meals" value="8"> 8 meals per month (4 points)</li>
             <li><input type="radio" id="freezer_0" name="freezer_meals" value="0"> We do not want ANY freezer meals</li>
         </ul>
         HTML;
     }

    /**
     * [2]... (Allergies)
     * Renders a form section for selecting food allergies.
     *
     * Displays a list of common food allergies as checkboxes. Includes an option 
     * to select "Other" with a corresponding text input for custom allergy entries. 
     * Also allows users to indicate no known allergies.
     *
     * No parameters.
     * @return void
     */
     function renderAllergySection() {
         echo <<<HTML
         <label for="allergies">Are there any food allergies that we need to be aware of? <span style="color: red;">*</span></label>
         <ul>
             <li><input type="checkbox" name="allergies[]" value="peanuts"> Peanuts</li>
             <li><input type="checkbox" name="allergies[]" value="tree nuts"> Tree Nuts</li>
             <li><input type="checkbox" name="allergies[]" value="gluten"> Gluten</li>
             <li><input type="checkbox" name="allergies[]" value="soy"> Soy</li>
             <li><input type="checkbox" name="allergies[]" value="egg"> Egg</li>
             <li><input type="checkbox" name="allergies[]" value="dairy"> Dairy</li>
             <li><input type="checkbox" name="allergies[]" value="no allergies"> No Known Allergies</li>
             <li><input type="checkbox" name="otherAllergy" value="other"> Other:</li>
             <li><input type="text" name="otherAllergyText" placeholder="Enter other allergy"></li>
         </ul>
         HTML;
     }

    /**
     * [3]... (House Cleaning)
     * Renders a form section for selecting house cleaning frequency.
     *
     * Displays radio buttons for choosing monthly house cleaning frequency.
     * Each option corresponds to a different number of points used per month.
     *
     * No parameters.
     * @return void
     */
     function renderHouseCleaningSection() {
         echo <<<HTML
         <label for="house_cleaning" required>Would you like house cleaning? <span style="color: red;">*</span></label>
         <ul>
             <li><input type="radio" name="house_cleaning" value="1" required> Once a month (7 points)</li>
             <li><input type="radio" name="house_cleaning" value="2"> Twice a month (14 points)</li>
             <li><input type="radio" name="house_cleaning" value="0"> We do not want house cleaning</li>
         </ul>
         HTML;
     }

    /**
     * [4]... (Lawn Care)
     * Renders a form section for selecting lawn care preference.
     *
     * Displays radio buttons for opting into monthly lawn care.
     * Option includes point value or opt-out.
     *
     * No parameters.
     * @return void
     */
     function renderLawnCareSection() {
         echo <<<HTML
         <label for="lawn_care" required>Would you like lawn care? <span style="color: red;">*</span></label>
         <ul>
             <li><input type="radio" name="lawn_care" value="1" required> Yes (3 points per month)</li>
             <li><input type="radio" name="lawn_care" value="0"> We do not want lawn care</li>
         </ul>
         HTML;
     }

   /**
    * [5]... (AAA Membership)
    * Renders a form section for selecting AAA Plus Membership status.
    *
    * Includes radio buttons for opting in, out, or indicating an existing membership.
    * Also collects the responsible party's name and date of birth if selected.
    *
    * No parameters.
    * @return void
    */
     function renderAAAMembershipSection() {
         $maxDate = date('Y-m-d');
         echo <<<HTML
         <label for="aaa_membership" required>Would you like a AAA Plus Membership? <span style="color: red;">*</span></label>
         <ul>
             <li><input type="radio" id="aaa_yes" name="aaa_membership" value="1" required> Yes</li>
             <li><input type="radio" id="aaa_no" name="aaa_membership" value="0"> No</li>
             <li><input type="radio" id="aaa_already_have_it" name="aaa_membership" value="2"> Already Have It</li>
         </ul>
 
         <p>If yes to AAA Membership, please provide the responsible party's name and date of birth.</p>
 
         <label for="aaa_membership_name">Responsible Party's Name</label>
         <input type="text" id="aaa_membership_name" name="aaa_membership_name" placeholder="Enter name">
 
         <label for="aaa_membership_dob">Responsible Party's Date of Birth</label>
         <input type="date" id="aaa_membership_dob" name="aaa_membership_dob" placeholder="Date of birth" max="$maxDate">
         HTML;
     }

/**
 * ------------------------------------------------------------------------
 *  DYNAMIC DATA RENDERING
 *  These functions create or populate form fields using data from the system.
 * 
 *    [6]   →   Render a family dropdown list for admin users
 *    [7]   →   Auto-fill form fields for selected family member
 *    [8]   →   Render vendor gift card dropdowns based on database results
 * 
 *  Enables dynamic interaction and convenience within the form UI.
 * ------------------------------------------------------------------------
 */

    /**
     * [6]... (Dropdown Menu)
     * Renders a dropdown menu of families for admin-level users (access level >= 2).
     *
     * Each option includes embedded data attributes such as name, email, address,
     * and AAA membership info to support client-side autofill functionality.
     *
     * Only includes users with lower access levels than the current session user.
     *
     * No parameters.
     * @return void
     */
     function renderFamilyDropdown() {
        global $db, $_SESSION;

        // Only allow users with access level 2 or higher to see the dropdown
        if ($_SESSION['access_level'] >= 2) {

            // Get a list of all families
            $people = getall_families();

            // Sort the list alphabetically by full name
            usort($people, function($a, $b) {
                $nameA = $a->get_first_name() . $a->get_last_name();
                $nameB = $b->get_first_name() . $b->get_last_name();
                return strcasecmp($nameA, $nameB);
            });

            // Start the dropdown
            echo '<select id="familyDropdown" name="family" onchange="fillForm()">';

            // Add the default option
            echo '<option value="" data-name="" data-email="" data-address="" data-aaa-name="" data-aaa-dob="">Select a person</option>';

            // Loop through each person and create an option if their access level is lower
            foreach ($people as $person) {
                if ($person->get_access_level() < $_SESSION['access_level']) {

                    // Get extra details for this person
                    $email = $person->get_email();
                    $aaaName = retrieve_aaa_name_by_email($email);
                    $aaaDob = retrieve_aaa_dob_by_email($email);

                    // Create the option tag with data attributes
                    echo "<option value='" . $person->get_id() . "' 
                        data-name='" . $person->get_first_name() . " " . $person->get_last_name() . "'
                        data-email='" . $email . "'
                        data-address='" . $person->get_address() . "'
                        data-aaa-name='" . $aaaName . "'
                        data-aaa-dob='" . $aaaDob . "'>";

                    // Display text for the option
                    echo $person->get_first_name() . " (" . $person->get_last_name() . ") -- " . $email;

                    echo "</option>";
                }
            }

            // End the dropdown
            echo '</select>';
        }
    }

    /**
     * [7]...      (Auto-fill form fields)
     * Retrieves and injects the user's name, email, address, and AAA membership info 
     * into the form using inline JavaScript, based on their session ID and access level.
     *
     * @param string $userID        The current user's session ID (usually their email).
     * @return void                          Outputs a <script> block to populate form fields on page load.
     */
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

   /**
    * [8]... (Gift card dropdowns)
    * Renders dropdown menus for selecting gift card amounts from available vendors.
    *
    * This function dynamically builds <select> inputs for either grocery or gas vendors,
    * allowing users to choose gift card amounts. Each dropdown includes values representing
    * the vendor name and a quantity-based value used for point calculations.
    *
    * @param mysqli_result $vendors        List of gift card vendors from the database.
    * @param string $type                           Field name prefix used for form input names (e.g., "grocery" or "gas").
    */
     function renderGiftCardSelect($vendors, $type) {
        if (mysqli_num_rows($vendors) > 0) {
 
            // Loop through each vendor
            while ($vendor = mysqli_fetch_assoc($vendors)) {
 
                $vendorName = htmlspecialchars($vendor['vendorName']);
 
                // Label for the vendor's gift card
                echo "<label for='$vendorName'>$vendorName <span style='color: red;'>*</span></label>";
 
                // Start the dropdown
                echo "<select name='{$type}[]' id='$vendorName'>";
                echo "<option value='none'>No $vendorName Gift Cards</option>";
 
                // Add options from $25 to $475 in $25 steps
                for ($amount = 25, $count = 1; $amount <= 475; $amount += 25, $count++) {
                    $optionValue = "$vendorName-$count";
                    $points = $amount / 25;
                    echo "<option value='$optionValue'>\$$amount $vendorName Gift Card ($points points)</option>";
                }
 
                // End the dropdown
                echo "</select>";
            }
        } else {
            // Show message if no vendors found
            echo "<p>No vendors found.</p>";
        }
     }   

/**
 * ------------------------------------------------------------------------
 *  POINTS PROGRAM FORM BUILD HELPERS
 *  These functions work together to prepare and assemble form data:
 * 
 *    [9]   →   Extract and structure submitted input
 *    [10] →   Collect POST-based extras like allergies and snacks
 *    [11]  →   Build and return a fully populated PointsProg object
 * 
 *  Used during form submission to create a complete and validated entry.
 * ------------------------------------------------------------------------
 */

    /**
     * [9]... (Extract Data)
     * Extracts and processes submitted form data into a structured array.
     *
     * This function organizes both required and optional fields submitted from the
     * Points Program form. It ensures defaults are set when optional fields are missing,
     * and formats multi-select inputs (e.g., grocery/gas) as comma-separated strings.
     *
     * @param array $args           Form input data from $_POST.
     * @return array $form          Data Structured and sanitized form data for further processing or storage.
     */
    function extractBasicFormData($args): array {
        $formData = [];

        // Set a unique ID
        $formData['id'] = find_next_id() + 1;

        // Required fields
        $formData['name'] = $args['name'];

        $formData['email'] = $args['email'];
        $formData['address'] = $args['address'];
        $formData['freezer_meals'] = $args['freezer_meals'];
        $formData['house_cleaning'] = $args['house_cleaning'];
        $formData['lawn_care'] = $args['lawn_care'];
        $formData['aaa_membership'] = $args['aaa_membership'];

        // Optional grocery field
        if (isset($args['grocery'])) {
            $formData['grocery'] = implode(",", $args['grocery']);
        } else {
            $formData['grocery'] = '';
        }

        // Optional gas field
        if (isset($args['gas'])) {
            $formData['gas'] = implode(",", $args['gas']);
        } else {
            $formData['gas'] = '';
        }

        // Optional monthly package
        if (isset($args['monthly_community_package'])) {
            $formData['monthly_package'] = $args['monthly_community_package'];
        } else {
            $formData['monthly_package'] = '';
        }

        // Optional AAA name
        if (isset($args['aaa_membership_name'])) {
            $formData['aaa_name'] = $args['aaa_membership_name'];
        } else {
            $formData['aaa_name'] = '';
        }

        // Optional AAA date of birth
        if (isset($args['aaa_membership_dob'])) {
            $formData['aaa_dob'] = validateDate($args['aaa_membership_dob']);
        } else {
            $formData['aaa_dob'] = '';
        }

        return $formData;
    }

    /**
     * [10]...      (Allergies and Snacks)
     * Extracts and formats allergy and snack selections from the submitted POST data.
     * 
     * Combines both predefined checkbox selections and custom text input into comma-separated strings.
     * Used to populate the corresponding fields in the Points Program submission.
     *
     * @return array {
     *     @type string $allergies          Combined allergy string (e.g., "peanuts, dairy, Other: sesame").
     *     @type string $snacks            Combined snack string (e.g., "crackers, fruit, Other: popcorn").
     * }
     */
     function collectExtrasFromPost(): array {
         $allergies = '';
     
         // Check if any allergy checkboxes were selected
         if (isset($_POST["allergies"])) {
             $allergies = implode(",", $_POST["allergies"]);
         }
     
         // Check if the user typed in a custom allergy
         if (!empty($_POST["otherAllergyText"])) {
             if (!empty($allergies)) {
                 $allergies .= ",";
             }
             $allergies .= "Other: " . $_POST["otherAllergyText"];
         }
     
         $snacks = '';
     
         // Check if any snack checkboxes were selected
         if (isset($_POST["snacks"])) {
             $snacks = implode(",", $_POST["snacks"]);
         }
     
         // Check if the user typed in a custom snack
         if (!empty($_POST["otherSnackText"])) {
             if (!empty($snacks)) {
                 $snacks .= ",";
             }
             $snacks .= "Other: " . $_POST["otherSnackText"];
         }
     
         return [
             'allergies' => $allergies,
             'snacks' => $snacks
         ];
     }
 
    /**
    * [11]...      (Build Points Program Form) 
    * Builds and returns a fully populated PointsProg domain object using submitted form data.
    *
    * Combines core input fields, allergy/snack selections, and calculates total points
    * to generate a complete and ready-to-save PointsProg entry.
    *
    * @param array $args        Raw sanitized form input ($_POST) data.
    * @return PointsProg         Fully constructed PointsProg instance for database insertion or comparison.
    */
     function buildPointsProgFromForm($args): PointsProg {
         // Get main form data
         $data = extractBasicFormData($args);
     
         // Get allergy and snack data
         $extras = collectExtrasFromPost();
     
         // Calculate total points used based on selected services
         $points_used = calculatePointsUsed(
             $data['freezer_meals'],
             $data['house_cleaning'],
             $data['lawn_care'],
             $data['grocery'],
             $data['gas']
         );
     
         // Create and return a new PointsProg object with all the collected data
         return new PointsProg(
             $data['id'],
             $data['name'],
             $data['email'],
             $data['address'],
             $data['freezer_meals'],
             $extras['allergies'],
             $extras['snacks'],
             $data['grocery'],
             $data['gas'],
             $data['house_cleaning'],
             $data['lawn_care'],
             $data['aaa_membership'],
             $data['aaa_name'],
             $data['aaa_dob'],
             $points_used,
             '',
             $data['monthly_package']
         );
     }

/**
 * ------------------------------------------------------------------------
 *  AAA MEMBERSHIP HELPERS
 *  These functions retrieve AAA membership information from the database:
 * 
 *    [12]  →   retrieve_aaa_name_by_email() — Get member name by email
 *    [13]  →   retrieve_aaa_dob_by_email() — Get member DOB by email
 * ------------------------------------------------------------------------
 */

    /**
     * [12]...  (AAA Name by Email)
     * Retrieves the most recent AAA membership name for a user by email.
     *
     * Looks up the `AAA_membership_name` field from the latest Points Program
     * form submission in the `dbPointsProg` table using the provided email.
     *
     * @param string $email      The user's email address used to find the record.
     * @return string                   The AAA membership name, or an empty string if not found.
     */
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

    /**
     * [13]...  (AAA DOB by Email)
     * Fetches the most recent AAA membership date of birth for a user by email.
     *
     * Queries the `dbPointsProg` table and retrieves the `AAA_membership_dob`
     * field for the latest submission matching the given email address.
     *
     * @param string $email     The user's email address to search by.
     * @return string                  The AAA date of birth as a string, or an empty string if not found.
     */
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
 * ------------------------------------------------------------------------
 *  POINTS PROGRAM UTILITIES & COMPARISON HELPERS
 *  These functions are used to:
 * 
 *    [14] → Fetch the most recent submission by email
 *    [15] → Compare old and new form data for changes
 *    [16] → Calculate total points based on user selections
 * 
 *  Useful during form submission review, auditing, and validation.
 * ------------------------------------------------------------------------
 */

    /**
     * [14]...  (Get Submission by Email)
     * Fetch the most recent Points Program submission for a given email.
     *
     * Used to retrieve the latest form entry submitted by a user, primarily
     * for comparison against a new submission to detect changes or updates.
     *
     * @param string $email   The user's email address used to look up the form.
     * @return array                 An associative array of the latest form data,
     *                                          or an empty array if no records are found.
     */
     function get_points_form($email) {
             $con = connect();
             $email = mysqli_real_escape_string($con, $email);
     
             // query for the form attributes
             $query = "SELECT * FROM dbPointsProg WHERE email = '$email' ORDER BY id DESC LIMIT 1";
             $result = mysqli_query($con, $query);       
     
             // Return an array of form attributes 
             if ($result && mysqli_num_rows($result) > 0) {
                 return mysqli_fetch_assoc($result);
             }       
             
             return [];
     }

    /**
     * [15]...      (Compare Old/New Form)
     * Compares two sets of form data to determine if any changes were made.
     *
     * This function is used to detect differences between a new form submission
     * and a previously stored one. It checks key fields and accounts for both
     * scalar values and arrays by normalizing them for comparison.
     *
     * @param array $newData        The new form submission data.
     * @param array $oldData          The previously saved form data.
     * @return bool                             True if any values differ; false if identical.
     */
     function hasFormChanged(array $newData, array $oldData): bool {
         $keysToCheck = [
             'name', 'email', 'address', 'freezer_meals',
             'grocery', 'gas', 'house_cleaning', 'lawn_care',
             'aaa_membership', 'aaa_membership_name', 'aaa_membership_dob',
             'monthly_community_package'
         ];
     
         foreach ($keysToCheck as $key) {
             $new = $newData[$key] ?? '';
             $old = $oldData[$key] ?? '';
         
             // Normalize arrays to strings
             if (is_array($new)) $new = implode(',', $new);
             if (is_array($old)) $old = implode(',', $old);
         
             if ($new != $old) {
                 return true;
             }
         }
         return false;
     }

    /**
     * [16]...      (Calculate Points)
     * Calculates the total number of points used based on selected services and gift card choices.
     *
     * This function sums up the cost in points of freezer meals, house cleaning,
     * lawn care, grocery cards, and gas cards. Grocery and gas values are extracted
     * from strings formatted like "VendorName-#".
     *
     * @param int $freezer_meals        Number of freezer meals selected.
     * @param int $house_cleaning     Number of house cleaning services selected.
     * @param int $lawn_care               Number of lawn care services selected.
     * @param string $grocery             Comma-separated grocery selections (e.g., "Vendor-2").
     * @param string $gas                    Comma-separated gas selections (e.g., "Vendor-1").
     * @return int                                    Total calculated points used.
     */
     function calculatePointsUsed($freezer_meals, $house_cleaning, $lawn_care, $grocery, $gas): int {
         $points = 0;
     
         // Add 1 point for every 2 freezer meals, but only if more than 2 meals were made
         if ($freezer_meals > 2) {
             $points += ($freezer_meals / 2);
         }
     
         // Add points for house cleaning (7 points each time)
         $points += $house_cleaning * 7;
     
         // Add points for lawn care (3 points each time)
         $points += $lawn_care * 3;
     
         // Find all numbers preceded by a dash in the grocery string (e.g., "-5")
         preg_match_all('/-(\d+)/', $grocery, $grocery_matches);
     
         // Find all numbers preceded by a dash in the gas string (e.g., "-10")
         preg_match_all('/-(\d+)/', $gas, $gas_matches);
     
         // Add all extracted grocery numbers to points
         foreach ($grocery_matches[1] as $value) {
             $points += intval($value);
         }
     
         // Add all extracted gas numbers to points
         foreach ($gas_matches[1] as $value) {
             $points += intval($value);
         }
     
         return $points;
     }
?>
