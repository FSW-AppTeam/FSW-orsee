<?php
// part of orsee. see orsee.org
ob_start();
$title="statistics";
$menu__area="statistics";
include("header.php");

if ($proceed) {
    echo '<div class="orsee-options-list-panel">';

    $optionlist=array();
    if (check_allow('statistics_system_show')) {
        $optionlist[]='<A HREF="statistics_system.php" class="option">'.oicon('chart-bar').lang('system_statistics').'</A>';
    }
    if (check_allow('statistics_participants_show')) {
        $optionlist[]='<A HREF="statistics_participants.php" class="option">'.oicon('chart-pie').lang('subject_pool_statistics').'</A>';
    }
    if (check_allow('statistics_server_usage_show')) {
        $optionlist[]='<A HREF="../usage/index.php" class="option">'.oicon('chart-line').lang('server_usage_statistics').'</A>';
    }
    options__show_main_section(lang('statistics'),$optionlist);

    $optionlist=array();
    if (check_allow('log_file_participant_actions_show')) {
        $optionlist[]='<A HREF="statistics_show_log.php?log=participant_actions" class="option">'.oicon('users').lang('participant_actions').'</A>';
    }
    if (check_allow('log_file_experimenter_actions_show')) {
        $optionlist[]='<A HREF="statistics_show_log.php?log=experimenter_actions" class="option">'.oicon('user-shield').lang('experimenter_actions').'</A>';
    }
    if (check_allow('log_file_regular_tasks_show')) {
        $optionlist[]='<A HREF="statistics_show_log.php?log=regular_tasks" class="option">'.oicon('history').lang('regular_tasks').'</A>';
    }
    options__show_main_section(lang('log_files'),$optionlist);

    $optionlist=array();
    if (check_allow('mailqueue_show_all')) {
        $optionlist[]='<A HREF="mailqueue_show.php" class="option">'.oicon('mail-bulk').lang('monitor_mail_queue').'</A>';
    }
    if ($settings['enable_payment_module']=='y' && (check_allow('payments_budget_view_my') || check_allow('payments_budget_view_all'))) {
        $optionlist[]='<A HREF="payments_budget_view.php" class="option">'.oicon('money-check-alt').lang('budget_reports').'</A>';
    }
    options__show_main_section(lang('monitoring'),$optionlist);

    echo '</div>';
}
include("footer.php");

?>
