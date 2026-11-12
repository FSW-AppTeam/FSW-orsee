<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiment_calendar";
$title="create_event";
$js_modules=array('flatpickr');
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['event_id']) && $_REQUEST['event_id']) {
        $event_id=$_REQUEST['event_id'];
    } else {
        $event_id="";
    }
    $allow=check_allow('events_edit','calendar_main.php');
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
        $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));

        $_REQUEST['event_start']=ortime__array_to_sesstime($_REQUEST,'event_start_');
        $_REQUEST['event_stop']=ortime__array_to_sesstime($_REQUEST,'event_stop_');

        $edit=$_REQUEST;
        $continue=true;

        if ($edit['event_start']>=$edit['event_stop']) {
            message(lang('start_time_must_be_earlier_than_stop_time'),'error');
            $continue=false;
        }

        if (!trim((string)$edit['reason'])) {
            message(lang('error_event_description_required'),'error');
            $continue=false;
        }


        if ($continue) {
            $form_fields=array_filter_allowed($edit,array(
                    'event_id','laboratory_id','event_category','event_start','event_stop',
                    'experimenter','reason','reason_public','number_of_participants'));
            $done=orsee_db_save_array($form_fields,"events",$form_fields['event_id'],"event_id");
            if ($done) {
                log__admin("events_edit","event_id:".$event_id);
                message(lang('changes_saved'));
                redirect('admin/events_edit.php?event_id='.$edit['event_id']);
            } else {
                lang('database_error');
                redirect('admin/events_edit.php?event_id='.$edit['event_id']);
            }
        }
    }
}

if ($proceed) {
    if ($event_id && !isset($edit['event_id'])) {
        $edit=orsee_db_load_array("events",$event_id,"event_id");
        if (!isset($edit['event_id'])) {
            redirect('admin/calendar_main.php');
        }
    }
}


if ($proceed) {
    // form

    if (isset($_REQUEST['copy']) && $_REQUEST['copy']) {
        $event_id="";
    }

    if (!$event_id) {
        $addit=true;
        $button_name=lang('add');

        if (isset($_REQUEST['copy']) && $_REQUEST['copy']) {
            $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));
            $_REQUEST['event_start']=ortime__array_to_sesstime($_REQUEST,'event_start_');
            $_REQUEST['event_stop']=ortime__array_to_sesstime($_REQUEST,'event_stop_');
            $edit=$_REQUEST;
            $edit['event_id']=time();
        } else {
            $edit['event_id']=time();

            $today=time();
            $default_start=mktime(
                $settings['laboratory_opening_time_hour'],
                $settings['laboratory_opening_time_minute'],
                0,
                date("n",$today),
                date("j",$today),
                date("Y",$today)
            );
            $default_stop=mktime(
                $settings['laboratory_closing_time_hour'],
                $settings['laboratory_closing_time_minute'],
                0,
                date("n",$today),
                date("j",$today),
                date("Y",$today)
            );
            $edit['event_start']=ortime__unixtime_to_sesstime($default_start);
            $edit['event_stop']=ortime__unixtime_to_sesstime($default_stop);

            $edit['experimenter']='|'.$expadmindata['admin_id'].'|';
            $edit['laboratory_id']="";
            $edit['event_category']="";
            $edit['reason']="";
            $edit['reason_public']="";
            $edit['number_of_participants']="";
        }
    } else {
        session__check_lab_time_clash($edit);
        $button_name=lang('change');
    }

    show_message();
    $event_experimenter_data=experiment__load_experimenters();
    $event_selected_experimenters=db_string_to_id_array($edit['experimenter']);
    $event_experimenter_options=array();
    foreach ($event_experimenter_data as $event_experimenter) {
        if (in_array($event_experimenter['admin_id'],$event_selected_experimenters) || ($event_experimenter['experimenter_list']=='y' && $event_experimenter['disabled']!='y')) {
            $event_experimenter_options[(string)$event_experimenter['admin_id']]=$event_experimenter['lname'].', '.$event_experimenter['fname'];
        }
    }
    asort($event_experimenter_options);

    echo '<form action="events_edit.php" method="POST">
        <input type="hidden" name="event_id" value="'.$edit['event_id'].'">
        '.csrf__field();
    if (isset($addit) && $addit) {
        echo '<input type="hidden" name="addit" value="true">';
    }
    echo '
        <div class="orsee-panel">
            <div class="orsee-form-shell">';
    orsee_callout(
        lang('for_session_time_reservation_please_use_experiments').'<br>'.lang('this_reservation_type_is_for_maintenence_purposes'),
        'warning',
        ''
    );
    echo '      <div class="field">
                    <div class="control">
                        <div class="orsee-dense-id"><span class="orsee-dense-id-tag">'.lang('id').': '.$edit['event_id'].'</span></div>
                    </div>
                </div>
                <div class="field">
                    <label class="label">'.lang('laboratory').':</label>
                    <div class="control">';
    laboratories__select_field("laboratory_id",$edit['laboratory_id']);
    echo '          </div>
                </div>
                <div class="field">
                    <label class="label">'.lang('event_category').':</label>
                    <div class="control">
                        <div class="select is-primary">'.language__selectfield_item('events_category','','event_category',$edit['event_category'],false,'fixed_order').'</div>
                    </div>
                </div>
                <div class="field orsee-form-row-grid orsee-form-row-grid--2">
                    <div class="orsee-form-row-col">
                        <label class="label">'.lang('start_date_and_time').':</label>
                        <div class="control">';
    echo formhelpers__pick_date('event_start',$edit['event_start']);
    echo '&nbsp;&nbsp;';
    echo formhelpers__pick_time('event_start', $edit['event_start']);
    echo '              </div>
                    </div>
                    <div class="orsee-form-row-col">
                        <label class="label">'.lang('stop_date_and_time').':</label>
                        <div class="control">';
    echo formhelpers__pick_date('event_stop',$edit['event_stop']);
    echo '&nbsp;&nbsp;';
    echo formhelpers__pick_time('event_stop', $edit['event_stop']);
    echo '              </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label">'.lang('experimenter').':</label>
                    <div class="control orsee-picker-field">';
    if (!isset($_REQUEST['event_id']) || !$_REQUEST['event_id']) {
        $edit['experimenter']='|'.$expadmindata['admin_id'].'|';
    }
    echo get_tag_picker('experimenter',$event_experimenter_options,db_string_to_id_array($edit['experimenter']),array('tag_bg_color'=>'--color-selector-tag-bg-experimenters'));
    echo '          </div>
                </div>
                <div class="field">
                    <label class="label" for="reason">'.lang('description').':</label>
                    <div class="control">
                        <input id="reason" class="input is-primary orsee-input orsee-input-text" type="text" name="reason" maxlength="200" value="'.$edit['reason'].'">
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="reason_public">'.lang('labspace_public_description').':</label>
                    <p class="help">'.lang('labspace_public_description_note').'</p>
                    <div class="control">
                        <input id="reason_public" class="input is-primary orsee-input orsee-input-text" type="text" name="reason_public" maxlength="200" value="'.$edit['reason_public'].'">
                    </div>
                </div>';
    if ($settings['enable_event_participant_numbers']=='y') {
        echo '  <div class="field">
                    <label class="label" for="number_of_participants">'.lang('number_of_participants').':</label>
                    <div class="control">
                        <input id="number_of_participants" class="input is-primary orsee-input orsee-input-text" type="text" name="number_of_participants" dir="ltr" maxlength="5" value="'.$edit['number_of_participants'].'">
                    </div>
                </div>';
    }
    echo '      <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                    <div class="orsee-form-row-col has-text-left">'.
                        button_back('calendar_main.php')
                .'</div>
                    <div class="orsee-form-row-col has-text-centered">
                        <input class="button orsee-btn" name="edit" type="submit" value="'.$button_name.'">
                    </div>
                    <div class="orsee-form-row-col has-text-right">';
    if ($event_id) {
        echo '          <input class="button orsee-btn" name="copy" type="submit" value="'.lang('copy_as_new_event').'">';
    }
    echo '          </div>
                </div>';

    if ($event_id && check_allow('events_delete')) {
        echo '  <div class="orsee-form-actions has-text-right">'.
                    button_link_delete('events_delete.php?event_id='.$edit['event_id'].'&csrf_token='.urlencode(csrf__get_token()),lang('delete')).
                '</div>';
    }

    echo '      </div>
        </div>
    </form>';
}
include("footer.php");

?>
