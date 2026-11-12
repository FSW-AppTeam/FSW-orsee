<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="sub_subjectpools";
include("header.php");

if ($proceed) {
    $allow=check_allow('subjectpool_edit','options_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';
    if (check_allow('subjectpool_add')) {
        echo button_link('subpool_edit.php?addit=true',lang('create_new'),'plus-circle');
    }
    echo '</div>';

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('id').'</div>';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('description').'</div>';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('subjects').'</div>';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('action').'</div>';
    echo '</div>';

    $part_counts=array();
    $query="SELECT count(*) as part_count, subpool_id FROM ".table('participants')." GROUP BY subpool_id";
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        $part_counts[$line['subpool_id']]=$line['part_count'];
    }

    $query="SELECT * FROM ".table('subpools')." ORDER BY subpool_id";
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
        echo '<div class="orsee-table-cell" data-label="'.lang('id').'" style="white-space: nowrap;">'.$line['subpool_id'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('name').'" style="white-space: nowrap;">'.$line['subpool_name'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('description').'">'.$line['subpool_description'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('subjects').'" style="white-space: nowrap;">';
        if (isset($part_counts[$line['subpool_id']])) {
            echo $part_counts[$line['subpool_id']];
        } else {
            echo '0';
        }
        echo '</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'" style="white-space: nowrap;">';
        echo button_link('subpool_edit.php?subpool_id='.$line['subpool_id'],lang('edit'),'pencil-square-o');
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
