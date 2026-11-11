<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="participant_statuses";
include("header.php");

if ($proceed) {
    $allow=check_allow('participantstatus_edit','options_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';

    if (check_allow('participantstatus_add')) {
        echo button_link('participant_status_edit.php?addit=true',lang('create_new'),'plus-circle');
    }
    echo '</div>';

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('id').'</div>';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('access_to_profile').'</div>';
    echo '<div class="orsee-table-cell">'.lang('eligible_for_experiments').'</div>';
    echo '<div class="orsee-table-cell">'.lang('default_for_active_participants').'</div>';
    echo '<div class="orsee-table-cell">'.lang('default_for_inactive_participants').'</div>';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('subjects').'</div>';
    if (check_allow('participantstatus_edit')) {
        echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('action').'</div>';
    }
    echo '</div>';

    // load status names from lang table
    $status_names=lang__load_lang_cat('participant_status_name');

    // load participant numbers
    $query="SELECT count(*) as status_count, status_id
            FROM ".table('participants')."
            GROUP BY status_id";
    $result=or_query($query);
    $status_counts=array();
    while ($line=pdo_fetch_assoc($result)) {
        $status_counts[$line['status_id']]=$line['status_count'];
    }

    $query="SELECT *
            FROM ".table('participant_statuses')."
            ORDER BY status_id";
    $result=or_query($query);
    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }

        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="'.lang('id').'" style="white-space: nowrap;">'.$line['status_id'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('name').'" style="white-space: nowrap;">'.$status_names[$line['status_id']].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('access_to_profile').'">'.$line['access_to_profile'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('eligible_for_experiments').'">'.$line['eligible_for_experiments'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('default_for_active_participants').'">';
        if ($line['is_default_active']=='y') {
            echo '<strong>'.lang('y').'</strong>';
        }
        echo '</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('default_for_inactive_participants').'">';
        if ($line['is_default_inactive']=='y') {
            echo '<strong>'.lang('y').'</strong>';
        }
        echo '</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('subjects').'" style="white-space: nowrap;">';
        if (isset($status_counts[$line['status_id']])) {
            echo $status_counts[$line['status_id']];
        } else {
            echo '0';
        }
        echo '</div>';
        if (check_allow('participantstatus_edit')) {
            echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'" style="white-space: nowrap;">';
            echo button_link('participant_status_edit.php?status_id='.$line['status_id'],lang('edit'),'pencil-square-o');
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';

    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
