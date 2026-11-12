<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiments_new";
$title="edit_experiment";
$js_modules=array('flatpickr');
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $allow=check_allow('experiment_edit','experiment_show.php?experiment_id='.$_REQUEST['experiment_id']);
        if ($proceed) {
            $edit=orsee_db_load_array("experiments",$_REQUEST['experiment_id'],"experiment_id");
            $edit['experiment_show_type']=$edit['experiment_type'].','.$edit['experiment_ext_type'];
            if (!check_allow('experiment_restriction_override')) {
                check_experiment_allowed($edit,"admin/experiment_show.php?experiment_id=".$edit['experiment_id']);
            }
        }
    } else {
        $allow=check_allow('experiment_edit','experiment_main.php');
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/experiment_edit.php?experiment_id=".$_REQUEST['experiment_id']);
        }
        $_REQUEST['experiment_class']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experiment_class']));
        $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));
        $_REQUEST['experimenter_mail']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter_mail']));

        if ($settings['enable_ethics_approval_module']=='y' && check_allow('experiment_edit_ethics_approval_details')) {
            if (isset($_REQUEST['ethics_expire_date_d']) && isset($_REQUEST['ethics_expire_date_m']) && isset($_REQUEST['ethics_expire_date_y']) &&
                $_REQUEST['ethics_expire_date_d']!=='' && $_REQUEST['ethics_expire_date_m']!=='' && $_REQUEST['ethics_expire_date_y']!=='') {
                $_REQUEST['ethics_expire_date']=ortime__array_to_sesstime($_REQUEST,'ethics_expire_date_');
            } else {
                $_REQUEST['ethics_expire_date']=0;
            }
        }

        if ($settings['enable_payment_module']=='y') {
            if (isset($_REQUEST['payment_types'])) {
                $_REQUEST['payment_types']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_types']));
            }
            if (isset($_REQUEST['payment_budgets'])) {
                $_REQUEST['payment_budgets']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_budgets']));
            }
        }

        if (!$_REQUEST['experiment_public_name']) {
            message(lang('error_you_have_to_give_public_name'),'error');
            $continue=false;
        }

        if (!$_REQUEST['experiment_name']) {
            message(lang('error_you_have_to_give_internal_name'),'error');
            $continue=false;
        }

        if ($settings['enable_editing_of_experiment_sender_email']=='y' && check_allow('experiment_change_sender_address')) {
            if (!preg_match("/^[^@ \t\r\n]+@[-_0-9a-zA-Z]+\.[^@ \t\r\n]+$/",$_REQUEST['sender_mail'])) {
                message(lang('error_no_valid_sender_mail'),'error');
                $continue=false;
            }
        } else {
            unset($_REQUEST['sender_mail']);
        }

        if (!$_REQUEST['experimenter']) {
            message(lang('error_at_least_one_experimenter_required'),'error');
            $continue=false;
        }

        if (!$_REQUEST['experimenter_mail']) {
            message(lang('error_at_least_one_experimenter_mail_required'),'error');
            $continue=false;
        }


        if ($continue) {
            if (!isset($_REQUEST['experiment_finished']) ||!$_REQUEST['experiment_finished']) {
                $_REQUEST['experiment_finished']="n";
            }

            if (!isset($_REQUEST['hide_in_stats']) ||!$_REQUEST['hide_in_stats']) {
                $_REQUEST['hide_in_stats']="n";
            }

            if (!isset($_REQUEST['hide_in_cal']) ||!$_REQUEST['hide_in_cal']) {
                $_REQUEST['hide_in_cal']="n";
            }

            if (!isset($_REQUEST['access_restricted']) ||!$_REQUEST['access_restricted']) {
                $_REQUEST['access_restricted']='n';
            }



            $exptypes=explode(",",$_REQUEST['experiment_show_type']);
            $_REQUEST['experiment_type']=trim($exptypes[0]);
            $_REQUEST['experiment_ext_type']=trim($exptypes[1]);

            $edit=$_REQUEST;
            $save_allowed_fields=array(
                    'experiment_id','experiment_name','experiment_public_name',
                    'experiment_description','experiment_link_to_paper',
                    'experiment_class','experimenter','experimenter_mail',
                    'experiment_type','experiment_ext_type','experiment_finished',
                    'hide_in_stats','hide_in_cal','access_restricted',
                    'payment_types','payment_budgets');
            if (or_setting('allow_public_experiment_note') &&
                    check_allow('experiment_edit_add_public_experiment_note')) {
                $save_allowed_fields[]='public_experiment_note';
            }
            if ($settings['enable_editing_of_experiment_sender_email']=='y' &&
                    check_allow('experiment_change_sender_address')) {
                $save_allowed_fields[]='sender_mail';
            }
            if ($settings['enable_ethics_approval_module']=='y' &&
                    check_allow('experiment_edit_ethics_approval_details')) {
                $save_allowed_fields[]='ethics_by';
                $save_allowed_fields[]='ethics_number';
                $save_allowed_fields[]='ethics_exempt';
                $save_allowed_fields[]='ethics_expire_date';
            }
            $form_fields=array_filter_allowed($edit,$save_allowed_fields);

            $done=orsee_db_save_array($form_fields,"experiments",
                $form_fields['experiment_id'],"experiment_id");

            if ($done) {
                message(lang('changes_saved'));
                redirect("admin/experiment_edit.php?experiment_id=".$edit['experiment_id']);
            } else {
                message(lang('database_error'),'error');
                redirect("admin/experiment_edit.php?experiment_id=".$edit['experiment_id']);
            }
        }

        $edit=$_REQUEST;
    }
}

if ($proceed) {
    // form

    // initialize if empty
    if (!isset($edit)) {
        $edit=array();
        $formvarnames=array('experiment_name','experiment_public_name','experiment_description',
                'public_experiment_note','experiment_link_to_paper','experiment_class',
                'experiment_id','sender_mail','experiment_show_type','access_restricted',
                'experiment_finished','hide_in_stats','hide_in_cal',
                'ethics_by','ethics_number','ethics_exempt','ethics_expire_date',
                'payment_types','payment_budgets');
        foreach ($formvarnames as $fvn) {
            if (!isset($edit[$fvn])) {
                $edit[$fvn]="";
            }
        }
        $edit['access_restricted']=$settings['default_experiment_restriction'];
    }
    show_message();

    if (!isset($edit['experiment_id']) || !$edit['experiment_id']) {
        $edit['experiment_id']=time();
    }

    if (!isset($_REQUEST['experiment_id']) || !$_REQUEST['experiment_id']) {
        if (!$edit['experimenter']) {
            $edit['experimenter']='|'.$expadmindata['admin_id'].'|';
        }
        if (!$edit['experimenter_mail']) {
            $edit['experimenter_mail']='|'.$expadmindata['admin_id'].'|';
        }
    }

    $experiment_class_options=experiment__load_experimentclassnames();
    asort($experiment_class_options);
    $experimenter_data=experiment__load_experimenters();
    $experimenter_options=array();
    $selected_experimenters=db_string_to_id_array($edit['experimenter']);
    $selected_experimenters_mail=db_string_to_id_array($edit['experimenter_mail']);
    foreach ($experimenter_data as $e) {
        if (in_array($e['admin_id'],$selected_experimenters) || in_array($e['admin_id'],$selected_experimenters_mail) || ($e['experimenter_list']=='y' && $e['disabled']!='y')) {
            $experimenter_options[(string)$e['admin_id']]=$e['lname'].', '.$e['fname'];
        }
    }
    asort($experimenter_options);

    echo '<form action="experiment_edit.php" method="POST" class="orsee-experiment-edit-form">
            '.csrf__field().'
            <input type="hidden" name="experiment_id" value="'.$edit['experiment_id'].'">
            <div class="orsee-panel">
                <div class="orsee-form-shell">';

    echo '          <div class="field">
                        <div class="control">
                            <div class="orsee-dense-id orsee-form-experiment-id"><span class="orsee-dense-id-tag">'.lang('id').': '.$edit['experiment_id'].'</span></div>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label" for="experiment_name">'.lang('internal_name').':</label>
                        <div class="control">
                            <input id="experiment_name" class="input is-primary orsee-input orsee-input-text" name="experiment_name" type="text" maxlength="100" value="'.stripslashes($edit['experiment_name']).'">
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label" for="experiment_public_name">'.lang('public_name').':</label>
                        <div class="control">
                            <input id="experiment_public_name" class="input is-primary orsee-input orsee-input-text" name="experiment_public_name" type="text" maxlength="100" value="'.stripslashes($edit['experiment_public_name']).'">
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label" for="experiment_description">'.lang('internal_description').':</label>
                        <div class="control">
                            <textarea id="experiment_description" class="textarea is-primary orsee-textarea" name="experiment_description" rows="2" wrap="virtual">'.stripslashes($edit['experiment_description']).'</textarea>
                        </div>
                    </div>';

    if (or_setting('allow_public_experiment_note') && check_allow('experiment_edit_add_public_experiment_note')) {
        echo '      <div class="field">
                        <label class="label" for="public_experiment_note">'.lang('public_experiment_note').':</label>
                        <p class="help">'.lang('public_experiment_note_note').'</p>
                        <div class="control">
                            <textarea id="public_experiment_note" class="textarea is-primary orsee-textarea" name="public_experiment_note" rows="2" wrap="virtual">'.$edit['public_experiment_note'].'</textarea>
                        </div>
                    </div>';
    }

    echo '          <div class="field">
                        <label class="label" for="experiment_show_type">'.lang('type').':</label>
                        <div class="control">
                            <div class="select is-primary">
                                <select id="experiment_show_type" name="experiment_show_type">';
    $experiment_internal_types=$system__experiment_types;
    foreach ($experiment_internal_types as $inttype) {
        $expexttypes=load_external_experiment_types($inttype);
        foreach ($expexttypes as $exttype) {
            $value=$inttype.','.$exttype['exptype_id'];
            $show=$lang[$inttype].' ("'.$exttype[lang('lang')].'")';
            echo '<option value="'.$value.'"';
            if ($value==$edit['experiment_show_type']) {
                echo ' selected';
            }
            echo '>'.$show.'</option>';
        }
    }
    echo '                  </select>
                            </div>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label"><i class="fa fa-tag fa-fw"></i>'.lang('class').':</label>
                        <div class="control orsee-picker-field">
                            '.get_tag_picker('experiment_class',$experiment_class_options,db_string_to_id_array($edit['experiment_class']),array('tag_bg_color'=>'--color-selector-tag-bg-class')).'
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label"><i class="fa fa-user fa-fw"></i>'.lang('experimenter').':</label>
                        <div class="control orsee-picker-field">
                            '.get_tag_picker('experimenter',$experimenter_options,$selected_experimenters,array('tag_bg_color'=>'--color-selector-tag-bg-experimenters')).'
                        </div>
                    </div>';

    if ($settings['allow_experiment_restriction']=='y') {
        echo '      <div class="field">
                        <div class="control">
                            <label class="checkbox orsee-checkline">
                                <input name="access_restricted" type="checkbox" value="y"';
        if ($edit['access_restricted']=="y") {
            echo ' checked';
        }
        echo '              >
                                <span>'.lang('experiment_access_restricted').'</span>
                            </label>
                        </div>
                    </div>';
    }

    echo '          <div class="field">
                        <label class="label"><i class="fa fa-envelope fa-fw"></i>'.lang('get_emails').':</label>
                        <div class="control orsee-picker-field">
                            '.get_tag_picker('experimenter_mail',$experimenter_options,$selected_experimenters_mail,array('tag_bg_color'=>'--color-selector-tag-bg-emails')).'
                        </div>
                    </div>';

    if ($settings['enable_editing_of_experiment_sender_email']=='y' && check_allow('experiment_change_sender_address')) {
        $sender_mail=$edit['sender_mail'] ? stripslashes($edit['sender_mail']) : $settings['support_mail'];
        echo '      <div class="field">
                        <label class="label" for="sender_mail">'.lang('email_sender_address').':</label>
                        <div class="control">
                            <input id="sender_mail" class="input is-primary orsee-input orsee-input-text" name="sender_mail" type="text" dir="ltr" maxlength="60" value="'.$sender_mail.'">
                        </div>
                    </div>';
    }

    if ($settings['enable_ethics_approval_module']=='y' && check_allow('experiment_edit_ethics_approval_details')) {
        echo '      <div class="field">
                        <label class="label">'.lang('human_subjects_ethics_approval').':</label>
                        <div class="orsee-ethics-grid">
                            <div class="control">
                                <label class="label" for="ethics_by">'.lang('ethics_by').'</label>
                                <input id="ethics_by" name="ethics_by" class="input is-primary orsee-input" type="text" maxlength="60" value="'.$edit['ethics_by'].'">
                            </div>
                            <div class="control">
                                <label class="label" for="ethics_number">'.lang('ethics_number').'</label>
                                <input id="ethics_number" name="ethics_number" class="input is-primary orsee-input" type="text" dir="ltr" maxlength="50" value="'.$edit['ethics_number'].'">
                            </div>
                        </div>
                        <div class="control orsee-ethics-choice-row">
                            <label class="radio"><input name="ethics_exempt" type="radio" value="y"';
        if ($edit['ethics_exempt']=='y') {
            echo ' checked';
        }
        echo '                  > '.lang('ethics_exempt_or').'</label>
                            <label class="radio"><input name="ethics_exempt" type="radio" value="n"';
        if ($edit['ethics_exempt']!='y') {
            echo ' checked';
        }
        echo '                  > '.lang('ethics_expires_on').'</label>
                            <span class="orsee-ethics-date">'.formhelpers__pick_date('ethics_expire_date',$edit['ethics_expire_date'],0,0,false,true).'</span>
                        </div>
                    </div>';
    }

    if ($settings['enable_payment_module']=='y') {
        $payment_types=payments__load_paytypes();
        $selected_payment_types=db_string_to_id_array($edit['payment_types']);
        $show_payment_types=($edit['payment_types'] || (is_array($payment_types) && count($payment_types)>1));

        $payment_budgets=payments__load_budgets(true);
        $selected_payment_budgets=db_string_to_id_array($edit['payment_budgets']);
        $show_payment_budgets=($edit['payment_budgets'] || (is_array($payment_budgets) && count($payment_budgets)>1));

        if ($show_payment_budgets) {
            $payment_budget_options=array();
            foreach ($payment_budgets as $budget_id=>$budget) {
                if ($budget['enabled'] || in_array($budget_id,$selected_payment_budgets)) {
                    $payment_budget_options[(string)$budget_id]=$budget['budget_name'];
                }
            }
            asort($payment_budget_options);
            echo '  <div class="field">
                        <label class="label"><i class="fa fa-credit-card fa-fw"></i>'.lang('possible_budgets').':</label>
                        <div class="control orsee-picker-field">
                            '.get_tag_picker('payment_budgets',$payment_budget_options,$selected_payment_budgets,array('tag_bg_color'=>'--color-selector-tag-bg-class')).'
                        </div>
                    </div>';
        }
        if ($show_payment_types) {
            asort($payment_types);
            echo '  <div class="field">
                        <label class="label"><i class="fa fa-money fa-fw"></i>'.lang('possible_payment_types').':</label>
                        <div class="control orsee-picker-field">
                            '.get_tag_picker('payment_types',$payment_types,$selected_payment_types,array('tag_bg_color'=>'--color-selector-tag-bg-paymenttypes')).'
                        </div>
                    </div>';
        }
    }

    echo '          <div class="field">
                        <div class="control">
                            <label class="checkbox orsee-checkline">
                                <input name="experiment_finished" type="checkbox" value="y"';
    if ($edit['experiment_finished']=="y") {
        echo ' checked';
    }
    echo '                      >
                                <span>'.lang('experiment_finished?').'</span>
                            </label>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <div class="control">
                            <label class="checkbox orsee-checkline">
                                <input name="hide_in_stats" type="checkbox" value="y"';
    if ($edit['hide_in_stats']=="y") {
        echo ' checked';
    }
    echo '                      >
                                <span>'.lang('hide_in_stats?').'</span>
                            </label>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <div class="control">
                            <label class="checkbox orsee-checkline">
                                <input name="hide_in_cal" type="checkbox" value="y"';
    if ($edit['hide_in_cal']=="y") {
        echo ' checked';
    }
    echo '                      >
                                <span>'.lang('hide_in_cal?').'</span>
                            </label>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label" for="experiment_link_to_paper">'.lang('experiment_link_to_paper').':</label>
                        <div class="control">
                            <input id="experiment_link_to_paper" class="input is-primary orsee-input orsee-input-text" name="experiment_link_to_paper" type="text" dir="ltr" maxlength="200" value="'.stripslashes($edit['experiment_link_to_paper']).'">';
    if (trim($edit['experiment_link_to_paper'])) {
        echo '              <a class="orsee-inline-link" target="_blank" href="'.trim($edit['experiment_link_to_paper']).'">'.lang('link').'</a>';
    }
    echo '              </div>
                    </div>';

    echo '          <div class="field is-grouped is-justify-content-center orsee-form-actions">
                        <div class="control">
                            <input name="edit" type="submit" class="button orsee-btn" value="';
    if (!isset($_REQUEST['experiment_id']) || !$_REQUEST['experiment_id']) {
        echo lang('add');
    } else {
        echo lang('change');
    }
    echo '                  ">
                        </div>
                    </div>';

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id'] && check_allow('experiment_delete')) {
        echo '      <div class="orsee-form-actions orsee-options-actions-center">'.
                    button_link('experiment_delete.php?experiment_id='.$edit['experiment_id'].'&csrf_token='.urlencode(csrf__get_token()),lang('delete'),'trash-o','','',' orsee-btn--delete')
                    .'</div>';
    }

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        echo '      <div class="orsee-options-actions">'.
                        button_back('experiment_show.php?experiment_id='.$_REQUEST['experiment_id'],lang('mainpage_of_this_experiment'))
                    .'</div>';
    }

    echo '          </div>
            </div>
        </form><br>';
}
include("footer.php");

?>
