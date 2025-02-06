<?php
session_cache_expire(30);
session_start();
require_once "include/input-validation.php";
?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once "universal.inc"; ?>
    <title>FGP|Modify Family Status</title>
</head>

<body>
    <?php
    require_once "header.php";
    require_once "domain/Person.php";
    require_once "database/dbPersons.php";

    // Retrieve family information
    if (isset($_GET["family_id"])) {
        $id = $_GET["family_id"];
        $person = retrieve_person($id);
    }
    if (!$person) {
        die("Database query failed.");
    }

    // Fetch the remission date from the database
    $remissionDateFromDB = $person->get_remission_trans_date();

    // Fetch the remission date from the database
    $remissionEndDateFromDB = $person->get_remission_end_date();

    // Ensure the date is properly formatted for <input type="date">
    $remissionDateFormatted =
        !empty($remissionDateFromDB) && $remissionDateFromDB !== "0000-00-00"
            ? date("Y-m-d", strtotime($remissionDateFromDB))
            : "";

    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $args = sanitize($_POST);

        //update status to selected status
        if ($args["status"]) {
            $status = $args["status"];
            update_status($person->get_id(), $status);
        }

        //if the other fields were filled, update respective fields
        if (isset($args["remission_trans_date"])) {
            $remission_trans_date = $args["remission_trans_date"];
            update_remission_trans_date(
                $person->get_id(),
                $remission_trans_date
            );
        }

        if (isset($args["remission_end_date"])) {
            $remission_end_date = $args["remission_end_date"];
            update_remission_end_date($person->get_id(), $remission_end_date);
        }

        if (isset($args["remembrance_date"])) {
            $remembrance_date = $args["remembrance_date"];
            update_remembrance_date($person->get_id(), $remembrance_date);
        }

        if (isset($args["remembrance_wishes"])) {
            $remembrance_wishes = $args["remembrance_wishes"];
            update_notes($person->get_id(), $remembrance_wishes);
        }

        echo '<script>document.location = "viewFamilyAccounts.php?modifySuccess";</script>';
        exit();
    }
    ?>
    <h1>Modify Family Status</h1>
    <form id="modify-status-form" class="general" method="post">
        <h2>Modify Status</h2>
        <p> Child name is: <?php echo $person->get_first_name() .
            " " .
            $person->get_last_name(); ?></p>
        <p> Current family status is <?php echo $person->get_status(); ?> </p>
        <?php // Fetch the status from the database
        $statusFromDB = $person->get_status();
?>

        <label for="status">Select the family status you want to change to (required)</label>
        <select name="status" id="status" required onchange="toggleFields()">
            <option value="Active" <?php if ($statusFromDB == "Active") {
                echo "selected";
            } ?>>Active</option>
            <option value="Remission" <?php if ($statusFromDB == "Remission") {
                echo "selected";
            } ?>>Remission</option>
            <option value="Survivor" <?php if ($statusFromDB == "Survivor") {
                echo "selected";
            } ?>>Survivor</option>
            <option value="Stargazer" <?php if ($statusFromDB == "Stargazer") {
                echo "selected";
            } ?>>Stargazer</option>
        </select>

        <fieldset id="remissionFields" style="display: none;">
            <legend>Remission Modification Fields</legend>
            <label for="remission_trans_date">Remission Transition Date</label>
            <input type="date" name="remission_trans_date" id="remission_trans_date"
                value="<?php echo !empty($remissionDateFromDB)
                    ? date("Y-m-d", strtotime($remissionDateFromDB))
                    : ""; ?>"
                required>

            <label for="remission_end_date">Remission End Date</label>
            <input type="date" name="remission_end_date" id="remission_end_date"
                value="<?php echo !empty($remissionEndDateFromDB)
                    ? date("Y-m-d", strtotime($remissionEndDateFromDB))
                    : ""; ?>"
                required>

        </fieldset>

        <fieldset id="stargazerFields" style="display: none;">
            <legend>Stargazer Modification Fields</legend>
            <label for="remembrance_date">Remembrance Date</label>
            <input type="date" name="remembrance_date">

            <label for="remembrance_wishes">Remembrance Wishes</label>
            <input type="text" name="remembrance_wishes" placeholder="Enter wishes">
        </fieldset>

        <button type="submit">Submit</button>
        <a class="button cancel" href="viewFamilyAccounts.php">Cancel</a>
    </form>
</body>

</html>

<script>
function toggleFields() {
    var status = document.getElementById("status").value;
    var remissionFields = document.getElementById("remissionFields");
    var stargazerFields = document.getElementById("stargazerFields");

    // Hide all fieldsets by default
    remissionFields.style.display = "none";
    stargazerFields.style.display = "none";

    // Show relevant fieldset based on selected status
    if (status === "Remission") {
        remissionFields.style.display = "block";
    } else if (status === "Stargazer") {
        stargazerFields.style.display = "block";
    }
}

document.addEventListener("DOMContentLoaded", toggleFields);
</script>