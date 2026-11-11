<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="statistics";
$title="budget_report";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['budget_id'])) {
        $budget_id=$_REQUEST['budget_id'];
    } else {
        redirect('admin/statistics_main.php');
    }
}

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

    if (!in_array($budget_id,$budget_ids)) {
        redirect('admin/payments_budget_view.php');
    }
}

if ($proceed) {
    $budget=$budgets[$budget_id];

    //load data
    $pars=array(':budget_id'=>$budget_id);
    $query="SELECT * FROM ".table('participate_at')." as p,
            ".table('sessions')." as s, ".table('experiments')." as e
            WHERE p.payment_budget = :budget_id
            AND p.session_id=s.session_id
            AND s.session_status='balanced'
            AND p.experiment_id=e.experiment_id
            ORDER BY s.session_start, p.payment_type";
    $result=or_query($query,$pars);
    $payments=array();
    while ($line = pdo_fetch_assoc($result)) {
        $payments[$line['experiment_id']][$line['session_id']][$line['payment_type']][]=$line;
    }


    echo '<div class="orsee-options-list-panel">';
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title">';
    echo '<div>'.$budget['budget_name'].'</div>';
    echo '<div class="orsee-panel-actions">'.button_back('payments_budget_view.php').'</div>';
    echo '</div>';
    echo '<table class="orsee-table orsee-table-no-hover" style="width: auto; min-width: 50%; margin: 0 auto;">';

    $payment_types=payments__load_paytypes();
    $cexp_id='';
    $csess_id='';
    $cpaytype_id='';
    $sum_exp=0;
    $sum_sess=0;
    $sum_paytype=0;
    $pid=0;
    foreach ($payments as $exp_id=>$exp) {
        $csess_id='';
        foreach ($exp as $sess_id=>$sess) {
            $cpaytype_id='';
            foreach ($sess as $paytype_id=>$paytype) {
                foreach ($paytype as $p) {
                    $pid++;
                    if ($cexp_id!=$exp_id) {
                        if ($cexp_id!=='') {
                            echo '<tr class="orsee-table-row orsee-table-row-spacer"><td class="orsee-table-cell" colspan="8"></td></tr>';
                        }
                        echo '<tr class="orsee-table-row orsee-table-head">
                                <td class="orsee-table-cell" colspan="8">'.lang('experiment').': '.$p['experiment_name'].'</td>
                                </tr>';
                        $cexp_id=$exp_id;
                    }
                    if ($csess_id!=$sess_id) {
                        echo '<tr class="orsee-table-row">
                                <td class="orsee-table-cell">&nbsp;&nbsp;</td>
                                <td class="orsee-table-cell" colspan="6">'.session__build_name($p).'</td>
                                <td class="orsee-table-cell">&nbsp;</td>
                                </tr>';
                        $csess_id=$sess_id;
                    }
                    if ($cpaytype_id!=$paytype_id) {
                        echo '<tr class="orsee-table-row">
                                <td class="orsee-table-cell">&nbsp;</td>
                                <td class="orsee-table-cell">&nbsp;&nbsp;</td>
                                <td class="orsee-table-cell" colspan="4">'.$payment_types[$p['payment_type']].'</td>
                                <td class="orsee-table-cell">&nbsp;</td>
                                <td class="orsee-table-cell">&nbsp;</td>
                                </tr>';
                        $cpaytype_id=$paytype_id;
                    }
                    echo '<tr class="orsee-table-row">
                                <td class="orsee-table-cell" style="font-size: var(--font-size-compact);" colspan="2"></td>
                                <td class="orsee-table-cell" style="font-size: var(--font-size-compact);">&nbsp;&nbsp;</td>
                                <td class="orsee-table-cell" style="font-size: var(--font-size-compact);">'.lang('participant').' '.$pid.'</td>
                                <td class="orsee-table-cell" style="font-size: var(--font-size-compact); text-align:end;">'.or__format_number($p['payment_amt'],2).'</td>
                                <td class="orsee-table-cell" style="font-size: var(--font-size-compact);">&nbsp;</td>
                                <td class="orsee-table-cell" style="font-size: var(--font-size-compact);">&nbsp;</td>
                                <td class="orsee-table-cell" style="font-size: var(--font-size-compact);">&nbsp;</td>
                                </tr>';
                    $sum_exp+=$p['payment_amt'];
                    $sum_sess+=$p['payment_amt'];
                    $sum_paytype+=$p['payment_amt'];
                }
                echo '<tr class="orsee-table-row">
                        <td class="orsee-table-cell" colspan="2"></td>
                        <td class="orsee-table-cell" colspan="3" style="border-bottom: 2px solid var(--color-body-text);">&nbsp;</td>
                        <td class="orsee-table-cell" style="text-align:end; border-bottom: 2px solid var(--color-body-text);"><strong>'.or__format_number($sum_paytype,2).'</strong></td>
                        <td class="orsee-table-cell">&nbsp;</td>
                        <td class="orsee-table-cell">&nbsp;</td>
                        </tr>';
                $sum_paytype=0;
            }
            echo '<tr class="orsee-table-row">
                    <td class="orsee-table-cell"></td>
                    <td class="orsee-table-cell" colspan="5" style="border-bottom: 2px solid var(--color-body-text);">&nbsp;</td>
                    <td class="orsee-table-cell" style="text-align:end; border-bottom: 2px solid var(--color-body-text);"><strong>'.or__format_number($sum_sess,2).'</strong></td>
                    <td class="orsee-table-cell">&nbsp;</td>
                    </tr>';
            $sum_sess=0;
        }
        echo '<tr class="orsee-table-row">
                <td class="orsee-table-cell" colspan="7" style="border-bottom: 2px solid var(--color-body-text);">&nbsp;</td>
                <td class="orsee-table-cell" style="text-align:end; border-bottom: 2px solid var(--color-body-text);"><strong>'.or__format_number($sum_exp,2).'</strong></td>
                </tr>';
        $sum_exp=0;
    }
    echo '</table>';
    echo '</div>';
    echo '</div>';
}
include("footer.php");

?>
