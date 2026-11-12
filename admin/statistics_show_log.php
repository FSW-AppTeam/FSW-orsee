<?php
// part of orsee. see orsee.org
ob_start();
$title="log_files";
$menu__area="statistics";
include("header.php");

if ($proceed) {
    $limit=$settings['stats_logs_results_per_page'];
    if ($_REQUEST['log']) {
        $log=$_REQUEST['log'];
    } else {
        redirect("admin/statistics.php");
    }
}

if ($proceed) {
    $allow=check_allow('log_file_'.$log.'_show','statistics_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel orsee-stat-log-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('log_files').' '.lang($log).'</div></div>';

    $num_rows=log__show_log($log);

    echo '<div class="orsee-stat-actions">';
    echo button_back('statistics_main.php');
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
