<?php
if (isset($_GET['act'])) {
    include "usage_decision_action.php";
} else {
    include "usage_decision_view.php";
}
?>
