<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_participant_status";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['status_id'])) {
        $status_id=$_REQUEST['status_id'];
    } else {
        $status_id="";
    }
    if (!$status_id) {
        redirect('admin/participant_status_main.php');
    }
}

if ($proceed) {
    $status=orsee_db_load_array("participant_statuses",$status_id,"status_id");
    if (!isset($status['status_id'])) {
        redirect('admin/participant_status_main.php');
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect('admin/participant_status_edit.php?status_id='.$status_id);
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    $allow=check_allow('participantstatus_delete','participant_status_edit.php?status_id='.$status_id);
}

if ($proceed) {
    // load status details
    $pars=array(':status_id'=>$status_id);
    $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_name' AND content_name= :status_id";
    $status_name=orsee_query($query,$pars);
    $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_error' AND content_name= :status_id";
    $status_error=orsee_query($query,$pars);

    if ($status['is_default_active']=="y" || $status['is_default_inactive']=="y") {
        message(lang('cannot_delete_participant_status_which_is_default'),'warning');
        redirect('admin/participant_status_edit.php?status_id='.$status_id);
    }
}

if ($proceed) {
    // load languages
    $languages=get_languages();
    foreach ($languages as $language) {
        $status['name_'.$language]=$status_name[$language];
        $status['error_'.$language]=$status_error[$language];
    }

    if ($reallydelete) {
        if (!csrf__validate_request_message()) {
            redirect('admin/participant_status_delete.php?status_id='.$status_id);
        }
        $participant_statuses=participant_status__get_statuses();
        if (!isset($_REQUEST['merge_with']) || !isset($participant_statuses[$_REQUEST['merge_with']])) {
            redirect('admin/participant_status_delete.php?status_id='.$status_id);
        } else {
            $merge_with=$_REQUEST['merge_with'];
            // transaction?
            $pars=array(':status_id'=>$status_id,':merge_with'=>$merge_with);
            $query="UPDATE ".table('participants')."
                    SET status_id= :merge_with
                    WHERE status_id= :status_id";
            $result=or_query($query,$pars);

            $pars=array(':status_id'=>$status_id);
            $query="DELETE FROM ".table('participant_statuses')."
                    WHERE status_id= :status_id";
            $result=or_query($query,$pars);

            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :status_id
                    AND content_type='participant_status_name'";
            $result=or_query($query,$pars);

            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :status_id
                    AND content_type='participant_status_error'";
            $result=or_query($query,$pars);

            log__admin("participant_status_delete","status_id:".$status['status_id']);
            message(lang('participant_status_deleted_part_moved_to').' "'.$participant_statuses[$merge_with]['name'].'".');
            redirect("admin/participant_status_main.php");
        }
    }
}


if ($proceed) {
    // form

    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_participant_status').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('really_delete_participant_status?').'</div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($status['status_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('name').'</label>
                    <div>'.htmlspecialchars($status_name[lang('lang')]).'</div>
                </div>
                <form action="participant_status_delete.php" method="POST">
                    <input type="hidden" name="status_id" value="'.$status_id.'">
                    '.csrf__field().'
                    <div class="field">
                        <label class="label">'.lang('merge_participant_status_with').'</label>
                        <div>'.participant_status__select_field('merge_with','',array(0,$status_id)).'</div>
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
