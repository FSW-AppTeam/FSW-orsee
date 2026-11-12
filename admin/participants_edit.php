<?php
// part of orsee. see orsee.org
ob_start();
$menu__area= (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) ? "participants_edit" : "participants_create";
$title="edit_participant";
$js_modules=array('flatpickr','switchy','intltelinput');
if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
    $hide_header=true;
} else {
    $hide_header=false;
}
if ($hide_header) {
    include("nonoutputheader.php");
    html__header();
    echo '<div class="orsee"><div class="orsee-panel">';
} else {
    include("header.php");
}

if ($proceed) {
    if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) {
        $participant_id=$_REQUEST['participant_id'];
    } else {
        $participant_id="";
    }

    $allow=check_allow('participants_edit','participants_main.php');
}

if ($proceed) {
    $statuses=participant_status__get_statuses();
    $continue=true;
    $errors__dataform=array();
    $submit_action='';
    $register_session=false;
    $register_session_id=0;
    if (isset($_REQUEST['save_participant_part']) && $_REQUEST['save_participant_part']) {
        $submit_action='public_part';
    } elseif (isset($_REQUEST['save_admin_part']) && $_REQUEST['save_admin_part']) {
        $submit_action='admin_part';
    } elseif (isset($_REQUEST['add']) && $_REQUEST['add']) {
        $submit_action='all';
    }

    if ($submit_action!=='') {
        $register_session=(isset($_REQUEST['register_session']) && $_REQUEST['register_session']=='y');
        $register_session_id=(isset($_REQUEST['session_id']) ? (int)$_REQUEST['session_id'] : 0);
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    $statuses=participant_status__get_statuses();
    $continue=true;
    $errors__dataform=array();

    if ($submit_action!=='') {
        // checks and errors
        $participant=$_REQUEST;
        foreach ($participant as $k=>$v) {
            if (!is_array($v)) {
                $participant[$k]=trim($v);
            }
        }
        if (isset($_POST['subpool_id'])) {
            $participant['subpool_id']=$_POST['subpool_id'];
        }
        $allowed_fields=array();
        if ($submit_action==='public_part') {
            $check_result=participantform__check_fields($participant,'profile_form_public_admin_edit');
            $participant=$check_result['sanitized'];
            $errors__dataform=$check_result['errors'];
            $allowed_fields=$check_result['allowed_fields'];
        } elseif ($submit_action==='admin_part') {
            $check_result=participantform__check_fields($participant,'profile_form_admin_part');
            $participant=$check_result['sanitized'];
            $errors__dataform=$check_result['errors'];
            $allowed_fields=$check_result['allowed_fields'];
        } else {
            $check_result_public=participantform__check_fields($participant,'profile_form_public_admin_edit');
            $participant_public=$check_result_public['sanitized'];
            $errors_public=$check_result_public['errors'];
            $check_result_admin=participantform__check_fields($participant_public,'profile_form_admin_part');
            $participant_admin=$check_result_admin['sanitized'];
            $participant=array_merge($participant_public,$participant_admin);
            $errors_admin=$check_result_admin['errors'];
            $errors__dataform=array_values(array_unique(array_merge($errors_public,$errors_admin)));
            $allowed_fields=array_values(array_unique(array_merge($check_result_public['allowed_fields'],$check_result_admin['allowed_fields'])));
        }
        $form_input=$participant;
        $error_count=count($errors__dataform);
        if ($error_count>0) {
            $continue=false;
        }

        if ($continue) {
            $participant_to_save=array();
            if (!$participant_id) {
                $new_id=participant__create_participant_id($form_input);
                $form_input['participant_id']=$new_id['participant_id'];
                $form_input['participant_id_crypt']=$new_id['participant_id_crypt'];
                $form_input['creation_time']=time();
                if (!isset($form_input['subpool_id']) || !$form_input['subpool_id']) {
                    $form_input['subpool_id']=$settings['subpool_default_registration_id'];
                }
                if (!isset($form_input['language']) || !$form_input['language']) {
                    $form_input['language']=$settings['public_standard_language'];
                }
                $participant_to_save=$form_input;
            } else {
                $participant_to_save=array('participant_id'=>$participant_id);
                $formfields=participantform__load();
                $save_scope_contexts=array();
                if ($submit_action==='public_part') {
                    $save_scope_contexts[]='profile_form_public_admin_edit';
                } elseif ($submit_action==='admin_part') {
                    $save_scope_contexts[]='profile_form_admin_part';
                } else {
                    $save_scope_contexts[]='profile_form_public_admin_edit';
                    $save_scope_contexts[]='profile_form_admin_part';
                }
                $save_subpool_id=(isset($form_input['subpool_id']) ? (int)$form_input['subpool_id'] : 0);
                if ($save_subpool_id<1) {
                    $save_subpool_id=(int)$settings['subpool_default_registration_id'];
                }
                foreach ($formfields as $f) {
                    if (!isset($f['mysql_column_name'])) {
                        continue;
                    }
                    $field_name=(string)$f['mysql_column_name'];
                    if ($field_name==='') {
                        continue;
                    }
                    $save_field=participant__profile_field_is_applicable($f,$save_subpool_id,$save_scope_contexts);
                    if ($save_field && array_key_exists($field_name,$form_input)) {
                        $participant_to_save[$field_name]=$form_input[$field_name];
                    }
                }
                if ($submit_action==='public_part') {
                    if (array_key_exists('language',$form_input)) {
                        $participant_to_save['language']=$form_input['language'];
                    }
                    if (array_key_exists('subscriptions',$form_input)) {
                        $participant_to_save['subscriptions']=$form_input['subscriptions'];
                    }
                } elseif ($submit_action==='admin_part') {
                    foreach (array('subpool_id','status_id','rules_signed','remarks') as $field_name) {
                        if (array_key_exists($field_name,$form_input)) {
                            $participant_to_save[$field_name]=$form_input[$field_name];
                        }
                    }
                } else {
                    foreach (array('subpool_id','status_id','rules_signed','remarks','language','subscriptions') as $field_name) {
                        if (array_key_exists($field_name,$form_input)) {
                            $participant_to_save[$field_name]=$form_input[$field_name];
                        }
                    }
                }
            }

            if (($submit_action==='admin_part' || $submit_action==='all') && isset($participant_to_save['status_id'])) {
                if (isset($participant_to_save['status_id'])) {
                    $sid=$participant_to_save['status_id'];
                } else {
                    $sid='';
                }
                if (isset($form_input['old_status_id'])) {
                    $osid=$form_input['old_status_id'];
                } else {
                    $osid='';
                }
                if ($sid!='' && $osid!='' && $osid!=$sid) {
                    $sid_e=$statuses[$sid]['eligible_for_experiments'];
                    $osid_e=$statuses[$osid]['eligible_for_experiments'];
                    if ($osid_e == 'y' && $sid_e=='n') {
                        $participant_to_save['deletion_time']=time();
                    } elseif ($osid_e == 'n' && $sid_e=='y') {
                        $participant_to_save['deletion_time']=0;
                    }
                }
            }

            $save_allowed_fields=$allowed_fields;
            if ($submit_action==='public_part') {
                $save_allowed_fields=array_merge($save_allowed_fields,array('participant_id','subpool_id','language','subscriptions'));
            } elseif ($submit_action==='admin_part') {
                $save_allowed_fields=array_merge($save_allowed_fields,array('participant_id','subpool_id','status_id','rules_signed','remarks','deletion_time'));
            } else {
                $save_allowed_fields=array_merge($save_allowed_fields,array('participant_id','subpool_id','language','subscriptions','status_id','rules_signed','remarks','deletion_time'));
            }
            if (!$participant_id) {
                $save_allowed_fields=array_merge($save_allowed_fields,array('participant_id_crypt','creation_time'));
            }
            $save_allowed_fields=array_values(array_unique($save_allowed_fields));
            $participant_to_save=array_filter_allowed($participant_to_save,$save_allowed_fields);

            $done=orsee_db_save_array($participant_to_save,"participants",$form_input['participant_id'],"participant_id");
            if ($done) {
                if ($submit_action==='public_part') {
                    message(lang('participant_data_saved'));
                } elseif ($submit_action==='admin_part') {
                    message(lang('participant_admin_data_saved'));
                } else {
                    message(lang('changes_saved'));
                }
            }

            if (($submit_action==='admin_part' || $submit_action==='all') && $register_session) {
                $session=orsee_db_load_array("sessions",$register_session_id,"session_id");
                if ($session['session_id']) {
                    $pars=array(':participant_id'=>$form_input['participant_id'],
                                ':experiment_id'=>$session['experiment_id']);
                    $query="SELECT * FROM ".table('participate_at')."
                            WHERE participant_id= :participant_id
                            AND experiment_id= :experiment_id";
                    $line=orsee_query($query,$pars);
                    if (isset($line['participate_id'])) {
                        if ($line['session_id']>0) {
                            $osession=orsee_db_load_array("sessions",$line['session_id'],"session_id");
                            message(lang('participant_already_enroled_for_experiment').
                            ' <A HREF="experiment_participants_show.php?experiment_id='.
                            $osession['experiment_id'].'&session_id='.$osession['session_id'].'">'.
                            session__build_name($osession).'</A>.','warning');
                        } else {
                            $pars=array(':participant_id'=>$form_input['participant_id'],
                                        ':session_id'=>$session['session_id'],
                                        ':experiment_id'=>$session['experiment_id']);
                            $query="UPDATE ".table('participate_at')."
                                    SET session_id= :session_id,
                                    pstatus_id=0
                                    WHERE participant_id= :participant_id
                                    AND experiment_id= :experiment_id";
                            $done2=or_query($query,$pars);
                        }
                    } else {
                        $pars=array(':participant_id'=>$form_input['participant_id'],
                                    ':session_id'=>$session['session_id'],
                                    ':experiment_id'=>$session['experiment_id']);
                        $query="INSERT into ".table('participate_at')."
                                SET participant_id= :participant_id,
                                session_id= :session_id,
                                experiment_id= :experiment_id,
                                pstatus_id=0";
                        $done2=or_query($query,$pars);
                    }
                    if (isset($done2) && $done2) {
                        message(lang('registered_participant_for').'
                                <A HREF="experiment_participants_show.php?experiment_id='.
                                $session['experiment_id'].'&session_id='.$session['session_id'].'">'.
                                session__build_name($session).'</A>.');
                    }
                } else {
                    message(lang('no_session_selected'),'warning');
                }
            }

            if ($done) {
                if ($participant_id) {
                    log__admin("participant_edit","participant_id:".$form_input['participant_id']);
                } else {
                    log__admin("participant_create","participant_id:".$form_input['participant_id']);
                }
                $form=false;
                $addition = "";
                if ($hide_header) {
                    $addition .= "&hide_header=true";
                }
                redirect("admin/participants_edit.php?participant_id=".$form_input['participant_id'].$addition);
            } else {
                message(lang('database_error'),'error');
            }
        }
    }
}

if ($proceed) {
    if ($participant_id && $continue) {
        $form_input=orsee_db_load_array("participants",$participant_id,"participant_id");
    } elseif (!isset($form_input) || !is_array($form_input)) {
        $form_input=array();
    }

    $button_title = ($participant_id) ? lang('save') : lang('add');

    show_message();
    if (!$hide_header) {
        echo '<div class="orsee-panel">';
    }
    participant__show_admin_form($form_input,$button_title,$errors__dataform,true);
    if (!$hide_header) {
        echo '</div>';
    }
    if ($participant_id) {
        participants__get_statistics($participant_id);
    }

    if ($settings['enable_email_module']=='y' && isset($form_input['participant_id'])) {
        $nums=email__get_privileges('participant',$form_input,'read',true);
        if ($nums['allowed'] && $nums['num_all']>0) {
            echo '<div class="orsee-panel-title" style="margin-top: 1rem;"><div class="orsee-panel-title-main">'.lang('emails').'</div></div>';
            echo javascript__email_popup();
            $url_string='participant_id='.$participant_id;
            if ($hide_header) {
                $url_string.='&hide_header=true';
            }
            email__list_emails('participant',$form_input['participant_id'],$nums['rmode'],$url_string,false);
        }
    }
}
if ($hide_header) {
    debug_output();
    echo '</div></div>';
    html__footer();
} else {
    include("footer.php");
}
if ($hide_header) {
    echo str_ireplace("href=", "target=\"_parent\" href=", ob_get_clean());
}
?>
