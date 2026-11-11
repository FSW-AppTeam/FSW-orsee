<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_cronjob";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['job_name'])) {
        $job_name=$_REQUEST['job_name'];
    } else {
        $job_name="";
    }
    if ($job_name) {
        $allow=check_allow('regular_tasks_edit','cronjob_main.php');
    } else {
        $allow=check_allow('regular_tasks_add','cronjob_main.php');
    }
}

if ($proceed) {
    // load languages
    $languages=get_languages();

    if ($job_name) {
        $job=orsee_db_load_array("cron_jobs",$job_name,"job_name");
    } else {
        $job=array('job_name'=>'','enabled'=>'n','job_last_exec'=>0,'job_time'=>'');
    }

    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/cronjob_edit.php?job_name=".$job_name);
        }
        if (!$_REQUEST['job_name']) {
            message(lang('name_for_cronjob_required'),'error');
            $continue=false;
        }
        if ($continue) {
            $form_fields=array_filter_allowed($_REQUEST,array('job_name','enabled','job_time'));
            if (!isset($form_fields['enabled']) || $form_fields['enabled']!=='y') {
                $form_fields['enabled']='n';
            } else {
                $form_fields['enabled']='y';
            }
            $done=orsee_db_save_array($form_fields,"cron_jobs",$form_fields['job_name'],"job_name");
            log__admin("cronjob_edit",$form_fields['job_name']);
            message(lang('changes_saved'));
            redirect("admin/cronjob_edit.php?job_name=".$form_fields['job_name']);
            $proceed=false;
        } else {
            $job=$_REQUEST;
        }
    }
}


if ($proceed) {
    // form
    show_message();

    echo '<form action="cronjob_edit.php" method="POST">
            '.csrf__field().'
            <div class="orsee-panel">
                <div class="orsee-form-shell">';

    echo '          <div class="field">
                        <label class="label">'.lang('name').':</label>
                        <div class="control">';
    if ($job_name) {
        echo '<INPUT type="hidden" name="job_name" value="'.$job['job_name'].'">';
        if (isset($lang['cron_job_'.$job['job_name']])) {
            echo $lang['cron_job_'.$job['job_name']];
        } else {
            echo $job['job_name'];
        }
    } else {
        echo '<input class="input is-primary orsee-input orsee-input-text" type="text" name="job_name" maxlength="200" value="">';
    }
    echo '              </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label">'.lang('enabled?').'</label>
                        <div class="control">
                            <label class="radio"><INPUT type=radio name="enabled" value="y"';
    if ($job['enabled']!="n") {
        echo ' CHECKED';
    }
    echo '>'.lang('yes').'</label>
                            &nbsp;&nbsp;
                            <label class="radio"><INPUT type=radio name="enabled" value="n"';
    if ($job['enabled']=="n") {
        echo ' CHECKED';
    }
    echo '>'.lang('no').'</label>
                        </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label">'.lang('last_execution').':</label>
                        <div class="control">';
    if ($job['job_last_exec']==0) {
        echo lang('never');
    } else {
        ortime__format($job['job_last_exec'],'hide_second:false',lang('lang'));
    }
    echo '              </div>
                    </div>';

    echo '          <div class="field">
                        <label class="label">'.lang('when_executed?').':</label>
                        <div class="control">
                            <div class="select is-primary">';
    cron__job_time_select_field($job['job_time']);
    echo '                  </div>
                        </div>
                    </div>';

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">'.
                            button_back('cronjob_main.php')
                    .'</div>
                        <div class="orsee-form-row-col has-text-centered">
                            <input class="button orsee-btn" name="edit" type="submit" value="';
    if (!$job_name) {
        echo lang('add');
    } else {
        echo lang('change');
    }
    echo '                  ">
                        </div>
                        <div class="orsee-form-row-col has-text-right"></div>
                    </div>
                </div>
            </div>
        </form><br>';
}
include("footer.php");

?>
