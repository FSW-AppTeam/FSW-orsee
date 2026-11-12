<?php
// part of orsee. see orsee.org
ob_start();
$title="mailqueue";
$menu__area="statistics";
include("header.php");

if ($proceed) {
    if (!$_REQUEST['experiment_id']) {
        redirect("admin/");
    } else {
        $experiment_id=$_REQUEST['experiment_id'];
    }
}

if ($proceed) {
    $allow=check_allow('mailqueue_show_experiment','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    // load experiment data into array experiment
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    mailqueue__show_mailqueue($experiment_id);

    echo '<div class="orsee-stat-actions">';
    echo button_back('experiment_show.php?experiment_id='.$experiment_id);
    echo '</div>';
}
include("footer.php");

?>
