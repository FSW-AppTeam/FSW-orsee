<?php
// part of orsee. see orsee.org
ob_start();
$title="delete_session";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
        $session_id=$_REQUEST['session_id'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            redirect('admin/session_edit.php?session_id='.$session_id);
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

    $session=orsee_db_load_array("sessions",$session_id,"session_id");

    $reg=experiment__count_participate_at($session['experiment_id'],$session_id);

    if ($reg>0) {
        $allow=check_allow('session_nonempty_delete','session_edit.php?session_id='.$session_id);
    } elseif (!check_allow('session_nonempty_delete')) {
        check_allow('session_empty_delete','session_edit?session_id='.$session_id);
    }
}

if ($proceed) {
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($session['experiment_id'],"admin/experiment_show.php?experiment_id=".$session['experiment_id']);
    }
}

if ($proceed) {
    $experiment=orsee_db_load_array("experiments",$session['experiment_id'],"experiment_id");

    if ($reallydelete) {
        // transaction?
        $pars=array(':session_id'=>$session_id);
        $query="UPDATE ".table('participate_at')."
                SET session_id='0', pstatus_id=0
                WHERE session_id= :session_id";
        $result=or_query($query,$pars);

        $pars=array(':session_id'=>$session_id);
        $query="DELETE FROM ".table('sessions')."
                WHERE session_id= :session_id";
        $result=or_query($query,$pars);

        message(lang('session_deleted'));
        log__admin("session_delete","session:".session__build_name($session,$settings['admin_standard_language']).
                ", session_id:".$session_id.", experiment_id:".$session['experiment_id']);
        redirect('admin/experiment_show.php?experiment_id='.$session['experiment_id']);
    }
}

if ($proceed) {
    // form
    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_session').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">
                    '.lang('really_delete_session').'
                </div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($session['session_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('experiment').'</label>
                    <div>'.htmlspecialchars($experiment['experiment_name']).'</div>
                </div>
                <div class="field">
                    <label class="label">'.lang('session').'</label>
                    <div>'.session__build_name($session).'</div>
                </div>
                <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                    <div class="orsee-form-row-col">
                        '.button_link(
        'session_delete.php?session_id='.$session_id.'&reallydelete=true&csrf_token='.urlencode(csrf__get_token()),
        lang('yes_delete'),
        'check-square',
        '',
        '',
        'orsee-btn--delete'
    ).'
                    </div>
                    <div class="orsee-form-row-col has-text-right">
                        '.button_link(
        'session_delete.php?session_id='.$session_id.'&betternot=true&csrf_token='.urlencode(csrf__get_token()),
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
