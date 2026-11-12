<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="budgets";
include("header.php");

if ($proceed) {
    $allow=check_allow('payments_budget_edit','options_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';
    if (check_allow('payments_budget_add')) {
        echo button_link('payments_budget_edit.php?addit=true',lang('create_new'),'plus-circle');
    }
    echo '</div>';

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('id').'</div>';
    echo '<div class="orsee-table-cell">'.lang('enabled?').'</div>';
    echo '<div class="orsee-table-cell">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('experimenter').'</div>';
    echo '<div class="orsee-table-cell">'.lang('budget_limit').'</div>';
    if (check_allow('payments_budget_edit')) {
        echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    }
    echo '</div>';

    $query="SELECT * FROM ".table('budgets')."
            ORDER BY enabled DESC, budget_name";
    $result=or_query($query);
    $shade=false;
    while ($line = pdo_fetch_assoc($result)) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        if (!$line['enabled']) {
            $row_class.=' orsee-table-row-disabled';
        }

        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="'.lang('id').'">'.$line['budget_id'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('enabled?').'">'.($line['enabled'] ? lang('y') : lang('n')).'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('name').'">'.$line['budget_name'].'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('experimenter').'">'.experiment__list_experimenters($line['experimenter'],false,true).'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('budget_limit').'">'.$line['budget_limit'].'</div>';
        if (check_allow('payments_budget_edit')) {
            echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">';
            echo button_link('payments_budget_edit.php?budget_id='.$line['budget_id'],lang('edit'),'pencil-square-o');
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
