<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="calendar";
$menu_item_id='calendar';
$title="experiment_calendar";
include("header.php");

if ($proceed) {
    echo '<div class="orsee-panel">';
    $done=calendar__display_calendar(0);
    echo '</div>';
}
include("footer.php");

?>
