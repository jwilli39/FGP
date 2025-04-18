/***************************************************************************
 * fillForm.js
 * 
 * Description:
 * This script provides functionality for auto-filling and validating 
 * the Points Program Form. When a family is selected from the dropdown, 
 * it auto-populates form fields such as name, email, address, and AAA details. 
 * It also validates the total number of points selected on form submission 
 * and prevents submission if the user exceeds the 19-point limit.
 * 
 * Features:
 * - Auto-fills form fields based on selected family data attributes.
 * - Automatically checks "Already Have It" if AAA info is prefilled.
 * - Calculates and validates total points before allowing submission.
 ****************************************************************************/

/**
 * Auto-fills form fields based on the selected family in the dropdown.
 * 
 * Populates name, email, address, AAA membership name, and date of birth.
 * Also checks the "Already Have It" option if AAA info exists.
 * 
 * Triggered when the family dropdown selection changes.
 */
function fillForm() {
    var dropdown = document.getElementById("familyDropdown");
    var selected = dropdown.options[dropdown.selectedIndex];

    document.getElementById("name").value = selected.getAttribute("data-name") || "";
    document.getElementById("email").value = selected.getAttribute("data-email") || "";
    document.getElementById("address").value = selected.getAttribute("data-address") || "";

    var aaaName = selected.getAttribute("data-aaa-name") || "";
    var aaaDob = selected.getAttribute("data-aaa-dob") || "";

    document.getElementById("aaa_membership_name").value = aaaName;
    document.getElementById("aaa_membership_dob").value = aaaDob;

    if (aaaName.trim() !== "") {
        var already = document.getElementById("aaa_already_have_it");
        if (already) already.checked = true;
    }
}

/**
 * Form Submission Validation
 * 
 * Validates the total number of points selected in the Points Program Form.
 * - Calculates points from all applicable categories (meals, cleaning, lawn care, grocery, gas).
 * - Prevents form submission and alerts the user if the total exceeds 19 points.
 * 
 * Trigger: On form submission.
 */
document.getElementById("new-points-prog-form").addEventListener("submit", function (e) {
    const MAX_POINTS = 19;
    let totalPoints = 0;

    // Freezer Meals
    const freezer = parseInt(document.querySelector('input[name="freezer_meals"]:checked')?.value || 0);
    if (freezer > 2) totalPoints += freezer / 2;

    // House cleaning
    const cleaning = parseInt(document.querySelector('input[name="house_cleaning"]:checked')?.value || 0);
    totalPoints += cleaning * 7;

    // Lawn care
    const lawn = parseInt(document.querySelector('input[name="lawn_care"]:checked')?.value || 0);
    totalPoints += lawn * 3;

    // Grocery cards
    document.querySelectorAll('select[name="grocery[]"]').forEach(sel => {
        const match = sel.value.match(/-(\d+)/);
        if (match) totalPoints += parseInt(match[1]);
    });

    // Gas cards
    document.querySelectorAll('select[name="gas[]"]').forEach(sel => {
        const match = sel.value.match(/-(\d+)/);
        if (match) totalPoints += parseInt(match[1]);
    });

    // BLOCK if over limit
    if (totalPoints > MAX_POINTS) {
        e.preventDefault(); //          <------ THIS STOPS THE REDIRECT
        alert(`Too many points selected: ${totalPoints}. Please reduce your selection to 19 or fewer.`);
        return false;
    }
});

