<?php
/**
 * ============================================================================
 *  pointsReviewHandler.php
 *
 *  Contains logic for rendering submitted Points Program review details
 *  and calculating total points used based on submitted data.
 *
 *  Provides:
 *    - Rendering of review rows for each selected option
 *    - Point calculation per service
 *    - Centralized function for total point aggregation
 *
 *  Used in:
 *    - reviewPointsProg.php
 *
 * @author   Josh Williams
 * ============================================================================
 */

/**
 *  [1]... (FREEZER MEALS REVIEW)
 * Displays selected freezer meals and calculates associated points.
 *
 * @param array $data Form submission data
 * @return float Points earned from freezer meals
 */
function renderFreezerMeals($data) {
    $meals = (int)($data['freezer_meals'] ?? 0);
    if ($meals > 2) {
        $points = $meals / 2;
        echo "<div class='review-row'><span>Freezer Meals ($meals meals)</span><span>$points pts</span></div>";
        return $points;
    }
    return 0;
}

/**
 *  [2]... (HOUSE CLEANING REVIEW)
 * Displays house cleaning selection and calculates points.
 *
 * @param array $data Form submission data
 * @return int Points earned from house cleaning
 */
function renderHouseCleaning($data) {
    $cleaning = (int)($data['house_cleaning'] ?? 0);
    if ($cleaning > 0) {
        $points = $cleaning * 7;
        echo "<div class='review-row'><span>House Cleaning ($cleaning time(s))</span><span>$points pts</span></div>";
        return $points;
    }
    return 0;
}

/**
 *  [3]... (LAWN CARE REVIEW)
 * Displays lawn care selection and calculates points.
 *
 * @param array $data Form submission data
 * @return int Points earned from lawn care
 */
function renderLawnCare($data) {
    $lawn = (int)($data['lawn_care'] ?? 0);
    if ($lawn > 0) {
        $points = $lawn * 3;
        echo "<div class='review-row'><span>Lawn Care</span><span>$points pts</span></div>";
        return $points;
    }
    return 0;
}

/**
 *  [4]... (GROCERY GIFT CARDS REVIEW)
 * Displays selected grocery gift cards and calculates points.
 *
 * @param array $data Form submission data
 * @return int Total points from grocery cards
 */
function renderGrocery($data) {
    $total = 0;
    $items = $data['grocery'] ?? [];
    foreach ($items as $item) {
        if ($item !== 'none' && preg_match('/^(.*)-(\d+)$/', $item, $matches)) {
            $label = htmlspecialchars($matches[1]);
            $qty = (int)$matches[2];
            $amount = $qty * 25;
            echo "<div class='review-row'><span>Grocery: $label ($$amount)</span><span>$qty pts</span></div>";
            $total += $qty;
        }
    }
    return $total;
}

/**
 *  [5]... (GAS GIFT CARDS REVIEW)
 * Displays selected gas gift cards and calculates points.
 *
 * @param array $data Form submission data
 * @return int Total points from gas cards
 */
function renderGas($data) {
    $total = 0;
    $items = $data['gas'] ?? [];
    foreach ($items as $item) {
        if ($item !== 'none' && preg_match('/^(.*)-(\d+)$/', $item, $matches)) {
            $label = htmlspecialchars($matches[1]);
            $qty = (int)$matches[2];
            $amount = $qty * 25;
            echo "<div class='review-row'><span>Gas: $label ($$amount)</span><span>$qty pts</span></div>";
            $total += $qty;
        }
    }
    return $total;
}

/**
 *  [6]... (AAA MEMBERSHIP REVIEW)
 * Displays AAA membership details and calculates points.
 *
 * @param array $data Form submission data
 * @return int Points earned from AAA membership
 */
function renderAAA($data) {
    $aaa = (int)($data['aaa_membership'] ?? 0);
    if ($aaa === 1) {
        echo "<div class='review-row'><span>AAA Membership</span><span>5 pts</span></div>";
        echo "<div class='review-row'><span>AAA Name</span><span>" . safe('aaa_membership_name') . "</span></div>";
        echo "<div class='review-row'><span>AAA DOB</span><span>" . safe('aaa_membership_dob') . "</span></div>";
        return 5;
    }
    return 0;
}

/**
 *  [7]... (TOTAL POINTS AGGREGATOR)
 * Calls each service-specific render function and sums all point values.
 *
 * @param array $data Form submission data
 * @return int Final total points used
 */
function renderPointsAndCalculateTotal(array $data): int {
    $total = 0;
    $total += renderFreezerMeals($data);
    $total += renderHouseCleaning($data);
    $total += renderLawnCare($data);
    $total += renderGrocery($data);
    $total += renderGas($data);
    $total += renderAAA($data);
    return $total;
}

/**
 *  [8]... (OVER LIMIT WARNING DISPLAY)
 * Displays a warning and disables form submission if point limit is exceeded.
 *
 * @param int $totalPoints Calculated total points
 * @param int $limit Max allowed points (default 19)
 * @return void
 */
function renderPointLimitWarning(int $totalPoints, int $limit = 19): void {
    if ($totalPoints > $limit) {
        echo "<div class='error-toast' style='color: red; font-weight: bold;'>
                You have selected $totalPoints points. Please reduce your selection to $limit or fewer before submitting.
              </div>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const submitBtn = document.querySelector('input[type=submit]');
                if (submitBtn) submitBtn.disabled = true;
            });
        </script>";
    }
}

/**
 *  [9]... (HIDDEN INPUT FIELD RENDERER)
 * Outputs hidden inputs for all submitted form data to retain user selections.
 *
 * @param array $data Form data (typically from $_SESSION or $_POST)
 * @return void
 */
function renderHiddenFormFields(array $data): void {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $subValue) {
                echo '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($subValue) . '">' . PHP_EOL;
            }
        } else {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . PHP_EOL;
        }
    }
}

/**
 *  [10]... (SAFE DATA OUTPUT HELPER)
 * Safely accesses a form field and returns a sanitized string for display.
 *
 * @param string $key Field name
 * @param string $default Fallback if value is missing
 * @return string Sanitized value
 */
function safe(string $key, string $default = 'Not provided'): string {
    global $data;
    if (!isset($data[$key])) return $default;
    if (is_array($data[$key])) return implode(', ', $data[$key]);
    return htmlspecialchars($data[$key]);
}
?>
