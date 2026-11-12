<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="files";
$title="files";
include("header.php");

if ($proceed) {
    echo '<div class="orsee-options-list-panel">';
    show_message();

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
        if (!check_allow('experiment_restriction_override')) {
            check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
        }
        if ($proceed) {
            $exp=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
            if (!isset($exp['experiment_id'])) {
                redirect('admin/download_main.php');
            }
        }
        if ($proceed) {
            $experimenters=db_string_to_id_array($exp['experimenter']);
            if (! ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_view_experiment_my'))
                    || check_allow('file_view_experiment_all'))) {
                redirect('admin/download_main.php');
            }
        }

        if ($proceed) {
            $thislist_sessions=sessions__get_sessions($experiment_id);
            $first_last=sessions__get_first_last_date($thislist_sessions);
            echo '<div class="orsee-panel orsee-option-section">';
            echo '<div class="orsee-panel-title">';
            echo '<div>';
            echo lang('experiment').' '.$exp['experiment_name'].', ';
            echo lang('from').' '.$first_last['first'].' ';
            echo lang('to').' '.$first_last['last'];
            echo ', '.experiment__list_experimenters($exp['experimenter'],true,true);
            echo '</div>';
            echo '<div class="orsee-panel-actions">';
            if ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_upload_experiment_my'))
                    || check_allow('file_upload_experiment_all')) {
                echo button_link('download_upload.php?experiment_id='.$exp['experiment_id'],lang('upload_file'),'upload');
            }
            echo '</div>';
            echo '</div>';
            echo downloads__list_files_experiment($exp['experiment_id'],true,true,true);
            echo '</div>';
            echo '<div class="orsee-stat-actions">';
            echo button_back('experiment_show.php?experiment_id='.$exp['experiment_id'],
                lang('mainpage_of_this_experiment'));
            echo '</div>';
            echo '<div class="orsee-stat-actions">';
            echo button_back('download_main.php',lang('all_downloads'));
            echo '</div>';
        }
    } else {
        if (check_allow('file_download_general')) {
            echo '<div class="orsee-panel orsee-option-section">';
            echo '<div class="orsee-panel-title">';
            echo '<div>';
            echo lang('general_downloads');
            echo '</div>';
            echo '<div class="orsee-panel-actions">';
            if (check_allow('file_upload_general')) {
                echo button_link('download_upload.php',lang('upload_general_file'),'upload');
            }
            echo '</div>';
            echo '</div>';
            echo downloads__list_files_general(true,true,true);
            echo '</div>';
        }
        if (check_allow('file_view_experiment_all') || check_allow('file_view_experiment_my')) {
            $list=downloads__list_experiments(true,true,true);
            if ($list) {
                echo '<div class="orsee-panel orsee-option-section">';
                echo '<div class="orsee-panel-title"><div>';
                echo lang('downloads_for_experiments');
                echo '</div></div>';
                echo '<div>'.lang('upload_experiment_files_in_exp_sec').'</div>';
                echo $list;
                echo '</div>';
            }
        }
    }
    echo '</div>';
}
include("footer.php");

?>
