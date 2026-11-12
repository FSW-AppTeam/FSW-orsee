<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="experiment_types";
include("header.php");

if ($proceed) {
    $allow=check_allow('experimenttype_edit','options_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';
    if (check_allow('experimenttype_add')) {
        echo button_link('experiment_type_edit.php?addit=true',lang('create_new'),'plus-circle');
    }
    echo '</div>';

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('id').'</div>';
    echo '<div class="orsee-table-cell">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('description').'</div>';
    echo '<div class="orsee-table-cell">'.lang('assigned_internal_experiment_types').'</div>';
    echo '<div class="orsee-table-cell">'.lang('registered_for_xxx_experiments_xxx').'</div>';
    echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    echo '</div>';

    $query="SELECT *
            FROM ".table('experiment_types')."
            ORDER BY exptype_id";
    $result=or_query($query);

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        $count=participants__count_participants(" subscriptions LIKE :exptype_id",array(':exptype_id'=>'%|'.$line['exptype_id'].'|%'));
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="'.lang('id').'">'.$line['exptype_id'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('name').'">'.$line['exptype_name'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('description').'">'.$line['exptype_description'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('assigned_internal_experiment_types').'">'.$line['exptype_mapping'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('registered_for_xxx_experiments_xxx').'">'.$count.'</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">'.button_link('experiment_type_edit.php?exptype_id='.$line['exptype_id'],lang('edit'),'pencil-square-o').'</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
