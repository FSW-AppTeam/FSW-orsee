<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_budget";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['budget_id'])) {
        $budget_id=$_REQUEST['budget_id'];
    }

    if (isset($budget_id)) {
        $allow=check_allow('payments_budget_edit','payments_budget_main.php');
    } else {
        $allow=check_allow('payments_budget_add','payments_budget_main.php');
    }
}

if ($proceed) {
    if (isset($budget_id)) {
        $budget=orsee_db_load_array("budgets",$budget_id,"budget_id");
        if (!isset($budget['budget_id'])) {
            redirect('admin/payments_budget_main.php');
        }
    } else {
        $budget=array('budget_name'=>'','budget_limit'=>'','enabled'=>0,'experimenter'=>'');
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/payments_budget_edit.php?budget_id=".$budget_id);
        }

        if (!isset($_REQUEST['budget_name']) || !$_REQUEST['budget_name']) {
            message(lang('error_you_have_to_provide_budget_name'),'error');
            $continue=false;
        }

        if ($continue) {
            $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));

            if (!isset($budget_id)) {
                $new=true;
                $query="SELECT max(budget_id)+1 as new_budget_id FROM ".table('budgets');
                $line=orsee_query($query);
                if (isset($line['new_budget_id'])) {
                    $budget_id=$line['new_budget_id'];
                } else {
                    $budget_id=1;
                }
            } else {
                $new=false;
            }

            $budget=$_REQUEST;
            $budget['budget_id']=$budget_id;
            $form_fields=array_filter_allowed($budget,array(
                    'budget_id','budget_name','budget_limit','experimenter','enabled'));
            $form_fields['budget_id']=$budget_id;
            if (!$form_fields['budget_limit']) {
                $form_fields['budget_limit']=null;
            }
            $done=orsee_db_save_array($form_fields,"budgets",$budget_id,"budget_id");

            message(lang('changes_saved'));
            log__admin("payments_budget_edit","budget_id:".$budget['budget_id']);
            //redirect ("admin/payments_budget_edit.php?budget_id=".$budget_id);
        } else {
            $budget=$_REQUEST;
        }
    }
}

if ($proceed) {
    // form

    $experimenter_data=experiment__load_experimenters();
    $experimenter_options=array();
    if (isset($budget['experimenter']) && strpos((string)$budget['experimenter'],'|')!==false) {
        $selected_experimenters=db_string_to_id_array($budget['experimenter']);
    } else {
        $selected_experimenters=multipicker_json_to_array((isset($budget['experimenter']) ? $budget['experimenter'] : ''));
    }
    foreach ($experimenter_data as $e) {
        if (in_array($e['admin_id'],$selected_experimenters) || ($e['experimenter_list']=='y' && $e['disabled']!='y')) {
            $experimenter_options[(string)$e['admin_id']]=$e['lname'].', '.$e['fname'];
        }
    }
    asort($experimenter_options);

    show_message();
    echo '
            <form action="payments_budget_edit.php" method="POST">'.csrf__field();
    if (isset($budget_id)) {
        echo '<input type="hidden" name="budget_id" value="'.$budget_id.'">';
    }

    echo '      <div class="orsee-panel">
                    <div class="orsee-panel-title">
                        <div class="orsee-panel-title-main">'.lang('edit_budget');
    if (isset($budget_id)) {
        echo ' '.$budget['budget_name'];
    }
    echo '                  </div>
                    </div>
                    <div class="orsee-form-shell">';
    if (isset($budget_id)) {
        echo '          <div class="field">
                            <div class="control"><span class="orsee-dense-id-tag">'.lang('id').': '.$budget_id.'</span></div>
                        </div>';
    }

    echo '          <div class="field">
                            <label class="label">'.lang('name').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="budget_name" type="text" maxlength="200" value="'.htmlspecialchars($budget['budget_name']).'">
                            </div>
                        </div>';

    echo '          <div class="field">
                            <label class="label">'.lang('budget_limit').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="budget_limit" type="text" dir="ltr" maxlength="200" value="'.htmlspecialchars($budget['budget_limit']).'">
                            </div>
                        </div>';

    echo '          <div class="field">
                            <label class="label">'.lang('experimenter').':</label>
                            <div class="control">';
    echo get_tag_picker('experimenter',$experimenter_options,$selected_experimenters,array('tag_bg_color'=>'--color-selector-tag-bg-experimenters'));
    echo '                  </div>
                        </div>';

    echo '          <div class="field">
                            <label class="label">'.lang('enabled?').'</label>
                            <div class="control">
                                <label class="radio"><input type="radio" name="enabled" value="1"';
    if ($budget['enabled']) {
        echo ' CHECKED';
    }
    echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                <label class="radio"><input type="radio" name="enabled" value="0"';
    if (!$budget['enabled']) {
        echo ' CHECKED';
    }
    echo '>'.lang('no').'</label>
                            </div>
                        </div>';

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                            <div class="orsee-form-row-col has-text-left">
                                '.button_back('payments_budget_main.php').'
                            </div>
                            <div class="orsee-form-row-col has-text-centered">
                                <input class="button orsee-btn" name="edit" type="submit" value="';
    if (!isset($budget_id)) {
        echo lang('add');
    } else {
        echo lang('change');
    }
    echo '">
                            </div>
                            <div class="orsee-form-row-col has-text-right">';

    $payment_budgets=payments__load_budgets();
    if (isset($budget_id) && check_allow('payments_budget_delete') && count($payment_budgets)>1) {
        echo button_link('payments_budget_delete.php?budget_id='.urlencode($budget_id),
            lang('delete'),'trash-o','','','orsee-btn--delete');
    }

    echo '              </div>
                        </div>
                    </div>
                </form>
                <br>';
}
include("footer.php");

?>
