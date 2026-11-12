<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiment_calendar";
$title="experiment_calendar";
include("header.php");

if ($proceed) {
    echo '<div class="orsee-panel">';
    $done=calendar__display_calendar(true);
    echo '</div>';
}
include("footer.php");

?>
