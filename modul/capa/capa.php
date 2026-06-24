<?php
if (isset($_GET['act'])) {
    include "capa_action.php";
} else {
    include "capa_view.php";
}
?>
