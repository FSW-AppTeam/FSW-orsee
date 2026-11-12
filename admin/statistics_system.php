<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="statistics";
$title="system_statistics";
include("header.php");

if ($proceed) {
    $allow=check_allow('statistics_system_show','statistics_main.php');
}

if ($proceed) {
    $data['participant_actions']=stats__get_participant_action_data();
    $_SESSION['stats_data']=$data;

    $out=stats__stats_display_table($data['participant_actions']);
    echo '<div class="orsee-options-list-panel">';

    echo '<div class="orsee-panel orsee-option-section">';
    echo '<div class="orsee-panel-title"><div>'.lang('system_statistics').'</div></div>';
    echo '</div>';

    echo '<div class="orsee-panel orsee-option-section">';
    echo '<div class="orsee-panel-title"><div>'.$data['participant_actions']['title'].'</div></div>';
    echo '<div class="orsee-panel-split">';
    echo '<div class="orsee-panel-split-main">'.$out.'</div>';
    echo '<div class="orsee-panel-split-actions" style="text-align: center;">';
    echo '<img border="0" src="statistics_graph.php?stype=participant_actions" style="max-width: 100%; height: auto;">';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-stat-actions">';
    echo button_back('statistics_main.php');
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
