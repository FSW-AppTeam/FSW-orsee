<?php
// part of orsee. see orsee.org
ob_start();
$title="mailqueue";
$menu__area="statistics";
include("header.php");

if ($proceed) {
    $allow=check_allow('mailqueue_show_all','statistics_main.php');
}

if ($proceed) {
    mailqueue__show_mailqueue();

    echo '<div class="orsee-stat-actions">';
    echo button_back('statistics_main.php');
    echo '</div>';
}
include("footer.php");

?>
