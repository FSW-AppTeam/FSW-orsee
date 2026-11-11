<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="statistics";
$title="budget_reports";
include("header.php");

if ($proceed) {
    if (!(check_allow('payments_budget_view_my') || check_allow('payments_budget_view_all'))) {
        redirect('admin/statistics_main.php');
    }
}

if ($proceed) {
    if (check_allow('payments_budget_view_all')) {
        $restriction="";
        $pars=array();
    } else {
        $pars=array(':adminname'=>'%|'.$expadmindata['adminname']).'|%';
        $restriction=" experimenter LIKE :adminname ";
    }

    // get budgets
    $query="SELECT * FROM ".table('budgets')." ".$restriction."
            ORDER BY enabled DESC, budget_name";
    $result=or_query($query,$pars);
    $shade=false;
    $budgets=array();
    $budget_ids=array();
    while ($line = pdo_fetch_assoc($result)) {
        $budgets[$line['budget_id']]=$line;
        $budget_ids[]=$line['budget_id'];
    }

    if (count($budgets)==0) {
        message(lang('no_budgets_available_for_view'),'warning');
        redirect('admin/statistics_main.php');
    }
}

if ($proceed) {
    //load summary stats
    $query="SELECT sum(payment_amt) as total_payment,
            payment_budget, payment_type
            FROM ".table('participate_at')."
            WHERE payment_budget IN (".implode(",",$budget_ids).")
            AND session_id IN (
                SELECT session_id FROM ".table('sessions')."
                WHERE session_status='balanced')
            GROUP BY payment_budget, payment_type";
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $budgets[$line['payment_budget']]['payments'][$line['payment_type']]=$line['total_payment'];
    }


    echo '<div class="orsee-options-list-panel">';
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div>'.lang('budget_reports').'</div></div>';
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('id').'</div>';
    echo '<div class="orsee-table-cell">'.lang('enabled?').'</div>';
    echo '<div class="orsee-table-cell">'.lang('name').'</div>';
    echo '<div class="orsee-table-cell">'.lang('experimenter').'</div>';
    echo '<div class="orsee-table-cell">'.lang('budget_limit').'</div>';
    echo '<div class="orsee-table-cell">'.lang('total_payment').'</div>';
    echo '<div class="orsee-table-cell"></div>';
    echo '</div>';

    $payment_types=payments__load_paytypes();
    $shade=false;
    foreach ($budgets as $line) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
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
        echo '<div class="orsee-table-cell" data-label="'.lang('total_payment').'">';
        if (isset($line['payments'])) {
            $paystring=array();
            foreach ($line['payments'] as $paytype=>$payamount) {
                $paystring[]=$payment_types[$paytype].': '.or__format_number($payamount,2);
            }
            echo implode("<BR>",$paystring);
        } else {
            echo '0.00';
        }
        echo '</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">';
        echo button_link('payments_budget_view_details.php?budget_id='.$line['budget_id'],lang('view_details'),'search');
        echo '</div>';
        echo '</div>';
        if ($shade) {
            $shade=false;
        } else {
            $shade=true;
        }
    }
    echo '</div>';
    echo '<div class="orsee-stat-actions">';
    echo button_back('statistics_main.php');
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
