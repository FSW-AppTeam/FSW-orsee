<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="experiment_participation_statuses";
include("header.php");

if ($proceed) {
    $allow=check_allow('participationstatus_edit','options_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';
    if (check_allow('participationstatus_add')) {
        echo button_link('participation_status_edit.php?addit=true',lang('create_new'),'plus-circle');
    }
    echo '</div>';

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('id').'</div>';
    echo '<div class="orsee-table-cell">'.lang('internal_name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('counts_as_participated').'</div>';
    echo '<div class="orsee-table-cell">'.lang('counts_as_noshow').'</div>';
    echo '<div class="orsee-table-cell">'.lang('cases').'</div>';
    if (check_allow('participationstatus_edit')) {
        echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    }
    echo '</div>';

    // load status names from lang table
    $status_names=lang__load_lang_cat('participation_status_internal_name');

    // load participant numbers
    $query="SELECT count(*) as pstatus_count, pstatus_id
            FROM ".table('participate_at')."
            GROUP BY pstatus_id";
    $result=or_query($query);
    $status_counts=array();
    while ($line=pdo_fetch_assoc($result)) {
        $status_counts[$line['pstatus_id']]=$line['pstatus_count'];
    }

    $query="SELECT *
            FROM ".table('participation_statuses')."
            ORDER BY pstatus_id";
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
        echo '<div class="orsee-table-cell" data-label="'.lang('id').'">'.$line['pstatus_id'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('internal_name').'">'.$status_names[$line['pstatus_id']].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('counts_as_participated').'">'.($line['participated'] ? lang('y') : lang('n')).'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('counts_as_noshow').'">'.($line['noshow'] ? lang('y') : lang('n')).'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('cases').'">';
        if (isset($status_counts[$line['pstatus_id']])) {
            echo $status_counts[$line['pstatus_id']];
        } else {
            echo '0';
        }
        echo '</div>';
        if (check_allow('participationstatus_edit')) {
            echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">';
            echo button_link('participation_status_edit.php?pstatus_id='.$line['pstatus_id'],lang('edit'),'pencil-square-o');
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
