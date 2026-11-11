<?php
// part of orsee. see orsee.org
ob_start();
$title="edit_session";
$js_modules=array('flatpickr');
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
        $session_id=$_REQUEST['session_id'];
    } else {
        $session_id="";
    }

    if ($session_id) {
        $edit=orsee_db_load_array("sessions",$session_id,"session_id");
    } else {
        $addit=true;
    }
}

if ($proceed) {
    if (isset($_REQUEST['experiment_id'])) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        $experiment_id=$edit['experiment_id'];
    }
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!isset($experiment['experiment_id'])) {
        redirect("admin/");
    }
}

if ($proceed) {
    $allow=check_allow('session_edit','experiment_show.php?experiment_id='.$experiment_id);
}
if ($proceed) {
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}

if ($proceed) {
    if (isset($experiment_id) && $experiment_id) {
        $allow=check_allow('session_edit','experiment_show.php?experiment_id='.$experiment_id);
    }
}

if ($proceed) {
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($_REQUEST['experiment_id'],"admin/experiment_show.php?experiment_id=".$experiment_id);
    }
}


if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if ($settings['enable_payment_module']=='y') {
            if (isset($_REQUEST['payment_types'])) {
                $_REQUEST['payment_types']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_types']));
            }
            if (isset($_REQUEST['payment_budgets'])) {
                $_REQUEST['payment_budgets']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_budgets']));
            }
        }

        $_REQUEST['session_start']=ortime__array_to_sesstime($_REQUEST,'session_start_');

        $registered=experiment__count_participate_at($edit['experiment_id'],$edit['session_id']);
        $time_changed=false;

        if ($edit['session_start'] != $_REQUEST['session_start']) {
            $time_changed=true;
            if ($registered>0) {
                message(lang('session_time_changed'),'warning');
            }
        } else {
            $time_changed=false;
        }

        if (!isset($_REQUEST['addit'])) {
            if ($_REQUEST['registration_end_hours']!=$edit['registration_end_hours'] || $time_changed) {
                $_REQUEST['reg_notice_sent']="n";
                message(lang('reg_time_extended_but_notice_sent'),'warning');
            }
            if (($_REQUEST['session_reminder_hours']!=$edit['session_reminder_hours'] || $time_changed) &&
                    isset($edit['session_reminder_sent']) && $edit['session_reminder_sent']=="y") {
                message(lang('session_reminder_changed_but_notice_sent'),'warning');
            }
        }

        $edit=$_REQUEST;
        $save_allowed_fields=array(
            'session_id','experiment_id','laboratory_id','session_start',
            'session_duration_hour','session_duration_minute',
            'part_needed','part_reserve',
            'registration_end_hours','session_reminder_hours','send_reminder_on',
            'session_remarks','session_status','reg_notice_sent',
            'payment_types','payment_budgets'
        );
        if (or_setting('allow_public_session_note') && check_allow('session_edit_add_public_session_note')) {
            $save_allowed_fields[]='public_session_note';
        }
        $form_fields=array_filter_allowed($edit,$save_allowed_fields);

        $done=orsee_db_save_array($form_fields,"sessions",$form_fields['session_id'],"session_id");

        if ($done) {
            log__admin("session_edit","session:".session__build_name($form_fields,
                $settings['admin_standard_language']).", session_id:".$form_fields['session_id'].", experiment_id:".$form_fields['experiment_id']);
            message(lang('changes_saved'));
            redirect('admin/session_edit.php?session_id='.$form_fields['session_id']);
        } else {
            lang('database_error');
            redirect('admin/session_edit.php?session_id='.$form_fields['session_id']);
        }
    }
}

if ($proceed) {
    // form

    if (isset($_REQUEST['copy']) && $_REQUEST['copy']) {
        $session_id="";
    }

    if (!$session_id) {
        $addit=true;
        $button_name=lang('add');

        if (isset($_REQUEST['copy']) && $_REQUEST['copy']) {
            if ($settings['enable_payment_module']=='y') {
                if (isset($_REQUEST['payment_types'])) {
                    $_REQUEST['payment_types']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_types']));
                }
                if (isset($_REQUEST['payment_budgets'])) {
                    $_REQUEST['payment_budgets']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_budgets']));
                }
            }
            $_REQUEST['session_start']=ortime__array_to_sesstime($_REQUEST,'session_start_');
            $edit=$_REQUEST;
            $edit['session_id']=time();
            $edit['session_status']='planned';
            $session_time=0;
        } else {
            $edit['experiment_id']=$_REQUEST['experiment_id'];
            $edit['session_id']=time();

            $edit['laboratory_id']="";
            $edit['session_remarks']="";
            $edit['public_session_note']="";

            $now=time();
            $next_quarter_hour=$now+((15*60)-($now%(15*60)))%(15*60);
            $edit['session_start']=ortime__unixtime_to_sesstime($next_quarter_hour);

            $edit['session_duration_hour']=$settings['session_duration_hour_default'];
            $edit['session_duration_minute']=$settings['session_duration_minute_default'];

            $edit['session_reminder_hours']=$settings['session_reminder_hours_default'];
            $edit['send_reminder_on']=$settings['session_reminder_send_on_default'];
            $edit['registration_end_hours']=$settings['session_registration_end_hours_default'];
            $session_time=0;

            $edit['part_needed']=$settings['lab_participants_default'];
            $edit['part_reserve']=$settings['reserve_participants_default'];

            $edit['session_status']='planned';

            $edit['payment_types']="";
            $edit['payment_budgets']="";
        }
    } else {
        $session_time=ortime__sesstime_to_unixtime($edit['session_start']);
        $button_name=lang('change');
        session__check_lab_time_clash($edit);
    }

    show_message();

    echo '<form action="session_edit.php" method="POST">
            <input type="hidden" name="session_id" value="'.$edit['session_id'].'">
            <input type="hidden" name="experiment_id" value="'.$edit['experiment_id'].'">
            '.csrf__field();
    if (isset($addit) && $addit) {
        echo '<input type="hidden" name="addit" value="true">';
    }
    echo '  <div class="orsee-panel">
                <div class="orsee-form-shell">';

    echo '          <div class="field">
                        <div class="control">
                            <div class="orsee-dense-id"><span class="orsee-dense-id-tag">'.lang('id').': '.$edit['session_id'].'</span></div>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label">'.lang('laboratory').':</label>
                        <div class="control">';
    laboratories__select_field("laboratory_id",$edit['laboratory_id']);
    echo '              </div>
                    </div>';

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('date').':</label>
                            <div class="control">';
    $session_start_default=(isset($edit['session_start']) && $edit['session_start']) ? $edit['session_start'] : ortime__unixtime_to_sesstime();
    echo formhelpers__pick_date('session_start',$session_start_default);
    echo '              </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('time').':</label>
                            <div class="control">';
    echo formhelpers__pick_time('session_start', $edit['session_start']);
    echo '              </div>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label">'.lang('experiment_duration').':</label>
                        <div class="control">
                            <div class="select is-primary is-inline-block">'.helpers__select_number("session_duration_hour",$edit['session_duration_hour'],0,$settings['session_duration_hour_max'],2,1,false).'</div>
                            :
                            <div class="select is-primary is-inline-block">'.helpers__select_number("session_duration_minute",$edit['session_duration_minute'],0,59,2,$settings['session_duration_minute_steps'],false).'</div>
                        </div>
                    </div>';

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('needed_participants').':</label>
                            <div class="control">';
    helpers__select_numbers("part_needed",$edit['part_needed'],0,$settings['lab_participants_max']);
    echo '              </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('reserve_participants').':</label>
                            <div class="control">';
    helpers__select_numbers("part_reserve",$edit['part_reserve'],0,$settings['reserve_participants_max']);
    echo '              </div>
                        </div>
                    </div>';

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('registration_end_hours_before').':</label>
                            <div class="control">';
    helpers__select_numbers_relative("registration_end_hours",$edit['registration_end_hours'],0,
        $settings['session_registration_end_hours_max'],2,
        $settings['session_registration_end_hours_steps'],$session_time);
    echo '              </div>
                        </div>
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('session_reminder_hours_before').':</label>
                            <div class="control">';
    if (isset($edit['session_reminder_sent']) && $edit['session_reminder_sent']=="y") {
        echo $edit['session_reminder_hours'].' ('.lang('session_reminder_already_sent').')';
    } else {
        helpers__select_numbers_relative("session_reminder_hours",$edit['session_reminder_hours'],0,
            $settings['session_reminder_hours_max'],2,$settings['session_reminder_hours_steps'],
            $session_time);
    }
    echo '              </div>
                        </div>
                    </div>';

    if ($settings['enable_payment_module']=='y') {
        $payment_types=payments__load_paytypes();
        $selected_payment_types=db_string_to_id_array($edit['payment_types']);
        $experiment_payment_types=db_string_to_id_array($experiment['payment_types']);
        $show_payment_types=($edit['payment_types'] || (is_array($experiment_payment_types) && count($experiment_payment_types)>1));

        $payment_budgets=payments__load_budgets(true);
        $selected_payment_budgets=db_string_to_id_array($edit['payment_budgets']);
        $experiment_payment_budgets=db_string_to_id_array($experiment['payment_budgets']);
        $show_payment_budgets=($edit['payment_budgets'] || (is_array($experiment_payment_budgets) && count($experiment_payment_budgets)>1));

        if ($show_payment_budgets || $show_payment_types) {
            echo '      <div class="field orsee-form-row-grid orsee-form-row-grid--2">';

            if ($show_payment_budgets) {
                $payment_budget_options=array();
                foreach ($payment_budgets as $budget_id=>$budget) {
                    if ($budget['enabled'] || in_array($budget_id,$selected_payment_budgets)) {
                        $payment_budget_options[(string)$budget_id]=$budget['budget_name'];
                    }
                }
                asort($payment_budget_options);
                echo '      <div class="orsee-form-row-col">
                                    <label class="label"><i class="fa fa-credit-card fa-fw"></i>'.lang('possible_budgets').':</label>
                                    <div class="control orsee-picker-field">
                                        '.get_tag_picker('payment_budgets',$payment_budget_options,$selected_payment_budgets,array('tag_bg_color'=>'--color-selector-tag-bg-class')).'
                                    </div>
                                </div>';
            }

            if ($show_payment_types) {
                asort($payment_types);
                echo '      <div class="orsee-form-row-col">
                                    <label class="label"><i class="fa fa-money fa-fw"></i>'.lang('possible_payment_types').':</label>
                                    <div class="control orsee-picker-field">
                                        '.get_tag_picker('payment_types',$payment_types,$selected_payment_types,array('tag_bg_color'=>'--color-selector-tag-bg-paymenttypes')).'
                                    </div>
                                </div>';
            }

            echo '      </div>';
        }
    }

    echo '          <div class="field">
                        <label class="label">'.lang('send_reminder_on').':</label>
                        <div class="control">';
    $oparray=array('enough_participants_needed_plus_reserve'=>'enough_participants_needed_plus_reserve',
                        'enough_participants_needed'=>'enough_participants_needed',
                        'in_any_case_dont_ask'=>'in_any_case_dont_ask');
    echo '<div class="select is-primary">'.helpers__select_text($oparray,"send_reminder_on",$edit['send_reminder_on']).'</div>';
    echo '              </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label" for="session_remarks">'.lang('remarks').':</label>
                        <p class="help">'.lang('session_remarks_note').'</p>
                        <div class="control">
                            <textarea id="session_remarks" class="textarea is-primary orsee-textarea" name="session_remarks" rows="3" wrap="virtual">'.$edit['session_remarks'].'</textarea>
                        </div>
                    </div>';

    if (or_setting('allow_public_session_note') && check_allow('session_edit_add_public_session_note')) {
        echo '      <div class="field">
                        <label class="label" for="public_session_note">'.lang('public_session_note').':</label>
                        <p class="help">'.lang('public_session_note_note').'</p>
                        <div class="control">
                            <textarea id="public_session_note" class="textarea is-primary orsee-textarea" name="public_session_note" rows="3" wrap="virtual">'.$edit['public_session_note'].'</textarea>
                        </div>
                    </div>';
    }

    echo '          <div class="field orsee-status-field">
                        <label class="label">'.lang('session_status').':</label>
                        <div class="control">'.session__session_status_select('session_status',$edit['session_status']).'</div>
                    </div>';

    if ($session_id) {
        $experiment_id=$edit['experiment_id'];
    } else {
        $experiment_id=$_REQUEST['experiment_id'];
    }

    $show_delete=false;
    if ($session_id) {
        $reg=experiment__count_participate_at($edit['experiment_id'],$session_id);
        if (($reg==0 && check_allow('session_empty_delete')) || check_allow('session_nonempty_delete')) {
            $show_delete=true;
        }
    }

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">'.
                            button_back('experiment_show.php?experiment_id='.$experiment_id)
                    .'</div>
                        <div class="orsee-form-row-col has-text-centered">
                            <input class="button orsee-btn" name="edit" type="submit" value="'.$button_name.'">
                        </div>
                        <div class="orsee-form-row-col has-text-right">';
    if ($session_id) {
        echo '              <input class="button orsee-btn" name="copy" type="submit" value="'.lang('copy_as_new_session').'"> ';
    }
    echo '              </div>
                    </div>';

    if ($show_delete) {
        echo '      <div class="orsee-form-actions has-text-right">'.
                    button_link_delete('session_delete.php?session_id='.$edit['session_id'].'&csrf_token='.urlencode(csrf__get_token()),lang('delete')).
                '</div>';
    }

    echo '          </div>
            </div>
        </form>';
}
include("footer.php");

?>
