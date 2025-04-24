<?php
session_start();
require_once('header.php');
require_once('database/dbinfo.php');

$con = connect();

// Function to update family information (location removed)
function update_family_info($id, $start_date, $lead_volunteer, $gift_card_delivery_method) {
    global $con;
    $query = "UPDATE dbPersons SET start_date = '$start_date', leadVolunteer = '$lead_volunteer', gift_card_delivery_method = '$gift_card_delivery_method' WHERE id = '$id'";
    $result = mysqli_query($con, $query);
    if (!$result) {
        die("Update failed: " . mysqli_error($con));
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $start_date = $_POST['start_date'];
    $lead_volunteer = $_POST['lead_volunteer'];
    $gift_card_delivery_method = $_POST['gift_card_delivery_method'];

    // Update family information
    update_family_info($id, $start_date, $lead_volunteer, $gift_card_delivery_method);

    echo "Update successful.";
    echo "<script>document.location = 'index.php';</script>";
}

// Retrieve family information
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM dbPersons WHERE id = '$id'";
    $result = mysqli_query($con, $query);
    if (!$result) {
        die("Database query failed.");
    }
    $row = mysqli_fetch_assoc($result);
    $start_date = $row['start_date'];
    $lead_volunteer = $row['leadVolunteer'];
    $gift_card_delivery_method = $row['gift_card_delivery_method'];
}

$sql = "SELECT firstName, lastName FROM dbVolunteer";
$result = mysqli_query($con, $sql);
$leadVolunteers = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $leadVolunteers[] = $row;
    }
}
?>

<!-- Form to edit family information (location removed) -->
<form method="POST" action="">
    <input type="hidden" name="id" value="<?php echo $id; ?>">

    <label for="start_date">Start Date:</label>
    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" required><br>

    <label for="lead_volunteer">Lead Volunteer:</label>
    <select name="lead_volunteer" id="lead_volunteer" required>
        <?php
        foreach ($leadVolunteers as $volunteer) {
            $fullName = $volunteer['firstName'] . ' ' . $volunteer['lastName'];
            $selected = ($lead_volunteer == $fullName) ? 'selected' : '';
            echo "<option value='$fullName' $selected>$fullName</option>";
        }
        ?>
    </select><br>
    
    <label for="gift_card_delivery_method">Gift Card Delivery Method:</label>
    <select name="gift_card_delivery_method" id="gift_card_delivery_method" required>
        <option value="Mail" <?php if ($gift_card_delivery_method == 'Mail') echo 'selected'; ?>>Mail</option>
        <option value="In Person" <?php if ($gift_card_delivery_method == 'In Person') echo 'selected'; ?>>In Person</option>
    </select><br>

    <button type="submit">Submit</button>
</form>

<?php require_once('universal.inc'); ?>
