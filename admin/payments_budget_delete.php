<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_budget";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['budget_id'])) {
        $budget_id=$_REQUEST['budget_id'];
    } else {
        $budget_id="";
    }
    if (!$budget_id) {
        redirect('admin/payments_budget_main.php');
    }
}

if ($proceed) {
    $budget=orsee_db_load_array("budgets",$budget_id,"budget_id");
    if (!isset($budget['budget_id'])) {
        redirect('admin/payments_budget_main.php');
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect('admin/payments_budget_edit.php?budget_id='.$budget_id);
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    $allow=check_allow('payments_budget_delete','payments_budget_edit.php?budget_id='.$budget_id);
}

if ($proceed) {
    if ($reallydelete) {
        if (!csrf__validate_request_message()) {
            redirect('admin/payments_budget_delete.php?budget_id='.$budget_id);
        }
        $budgets=payments__load_budgets();
        if (!isset($_REQUEST['merge_with']) || !isset($budgets[$_REQUEST['merge_with']])) {
            redirect('admin/payments_budget_delete.php?budget_id='.$budget_id);
        } else {
            $merge_with=$_REQUEST['merge_with'];
            // transaction?

            // update paticipate_at
            $pars=array(':budget_id'=>$budget_id,':merge_with'=>$merge_with);
            $query="UPDATE ".table('participate_at')."
                    SET payment_budget= :merge_with
                    WHERE payment_budget= :budget_id";
            $done=or_query($query,$pars);

            // update sessions
            $upars=array();
            $pars=array(':payment_budget'=>'%|'.$budget_id.'|%');
            $query="SELECT session_id, payment_budgets
                    FROM ".table('sessions')."
                    WHERE payment_budgets LIKE :payment_budget";
            $result=or_query($query,$pars);
            while ($line=pdo_fetch_assoc($result)) {
                $ids=db_string_to_id_array($line['payment_budgets']);
                foreach ($ids as $k=>$v) {
                    if ($v==$budget_id) {
                        unset($ids[$k]);
                    }
                }
                if (!in_array($merge_with,$ids)) {
                    $ids[]=$merge_with;
                }
                $upars[]=array(
                            ':session_id'=>$line['session_id'],
                            ':payment_budgets'=>id_array_to_db_string($ids)
                                );
            }
            $query="UPDATE ".table('sessions')."
                    SET payment_budgets= :payment_budgets
                    WHERE session_id= :session_id";
            $done=or_query($query,$upars);

            // update experiments
            $upars=array();
            $pars=array(':payment_budget'=>'%|'.$budget_id.'|%');
            $query="SELECT experiment_id, payment_budgets
                    FROM ".table('experiments')."
                    WHERE payment_budgets LIKE :payment_budget";
            $result=or_query($query,$pars);
            while ($line=pdo_fetch_assoc($result)) {
                $ids=db_string_to_id_array($line['payment_budgets']);
                foreach ($ids as $k=>$v) {
                    if ($v==$budget_id) {
                        unset($ids[$k]);
                    }
                }
                if (!in_array($merge_with,$ids)) {
                    $ids[]=$merge_with;
                }
                $upars[]=array(
                            ':experiment_id'=>$line['experiment_id'],
                            ':payment_budgets'=>id_array_to_db_string($ids)
                                );
            }
            $query="UPDATE ".table('experiments')."
                    SET payment_budgets= :payment_budgets
                    WHERE experiment_id= :experiment_id";
            $done=or_query($query,$upars);

            // delete from budgets
            $pars=array(':budget_id'=>$budget_id);
            $query="DELETE FROM ".table('budgets')."
                    WHERE budget_id= :budget_id";
            $result=or_query($query,$pars);

            log__admin("payments_budget_delete","budget_id:".$budget['budget_id'].", merge_with:".$merge_with);
            message(lang('payments_budget_deleted_exp_sess_part_moved_to').' "'.$budgets[$merge_with]['budget_name'].'".');
            redirect("admin/payments_budget_main.php");
        }
    }
}


if ($proceed) {
    // form

    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_budget').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('really_delete_budget?').'</div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($budget['budget_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('name').'</label>
                    <div>'.htmlspecialchars($budget['budget_name']).'</div>
                </div>
                <form action="payments_budget_delete.php" method="POST">
                    <input type="hidden" name="budget_id" value="'.$budget_id.'">
                    '.csrf__field().'
                    <div class="field">
                        <label class="label">'.lang('merge_budget_with').'</label>
                        <div><span class="select is-primary">'.payments__budget_selectfield('merge_with','',array($budget_id)).'</span></div>
                    </div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                        <div class="orsee-form-row-col">
                            <button class="button orsee-btn orsee-btn--delete" type="submit" name="reallydelete" value="1"><i class="fa fa-check-square"></i> '.lang('yes_delete').'</button>
                        </div>
                        <div class="orsee-form-row-col has-text-right">
                            <button class="button orsee-btn" type="submit" name="betternot" value="1"><i class="fa fa-undo"></i> '.lang('no_sorry').'</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>';
}
include("footer.php");

?>
