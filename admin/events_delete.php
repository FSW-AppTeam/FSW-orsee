<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiment_calendar";
$title="delete_lab_reservation";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['event_id']) && $_REQUEST['event_id']) {
        $event_id=$_REQUEST['event_id'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            redirect('admin/events_edit.php?event_id='.$event_id);
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    $allow=check_allow('events_delete','events_edit.php?event_id='.$event_id);
}

if ($proceed) {
    $space=orsee_db_load_array("events",$event_id,"event_id");

    if ($reallydelete) {
        $pars=array('event_id'=>$event_id);
        $query="DELETE FROM ".table('events')."
                WHERE event_id= :event_id";
        $result=or_query($query,$pars);
        log__admin("events_delete","event_id:".$event_id);
        message(lang('lab_reservation_deleted'));
        redirect('admin/calendar_main.php');
    }
}

if ($proceed) {
    // form
    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_lab_reservation').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('do_you_really_want_to_delete').'</div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($space['event_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('start_date_and_time').'</label>
                    <div>'.ortime__format(ortime__sesstime_to_unixtime($space['event_start'])).'</div>
                </div>
                <div class="field">
                    <label class="label">'.lang('stop_date_and_time').'</label>
                    <div>'.ortime__format(ortime__sesstime_to_unixtime($space['event_stop'])).'</div>
                </div>
                <div class="field">
                    <label class="label">'.lang('description').'</label>
                    <div>'.htmlspecialchars($space['reason']).'</div>
                </div>
                <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                    <div class="orsee-form-row-col">
                        '.button_link(
        'events_delete.php?event_id='.$event_id.'&reallydelete=true&csrf_token='.urlencode(csrf__get_token()),
        lang('yes_delete'),
        'check-square',
        '',
        '',
        'orsee-btn--delete'
    ).'
                    </div>
                    <div class="orsee-form-row-col has-text-right">
                        '.button_link(
        'events_delete.php?event_id='.$event_id.'&betternot=true&csrf_token='.urlencode(csrf__get_token()),
        lang('no_sorry'),
        'undo'
    ).'
                    </div>
                </div>
            </div>
        </div>';
}
include("footer.php");

?>
