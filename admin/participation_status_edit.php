<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_participation_status";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['pstatus_id'])) {
        $pstatus_id=$_REQUEST['pstatus_id'];
    }
    if (isset($pstatus_id)) {
        $allow=check_allow('participationstatus_edit','participation_status_main.php');
    } else {
        $allow=check_allow('participationstatus_add','participation_status_main.php');
    }
}

if ($proceed) {
    if (isset($pstatus_id) && $pstatus_id==0) {
        $not_assigned=false;
    } else {
        $not_assigned=true;
    }

    // load languages
    $languages=get_languages();

    if (isset($pstatus_id)) {
        $pstatus=orsee_db_load_array("participation_statuses",$pstatus_id,"pstatus_id");
        if (!isset($pstatus['pstatus_id'])) {
            redirect('admin/participation_status_main.php');
        }
        if ($proceed) {
            $pars=array(':pstatus_id'=>$pstatus_id);
            $query="SELECT * from ".table('lang')." WHERE content_type='participation_status_internal_name' AND content_name= :pstatus_id";
            $pstatus_internal_name=orsee_query($query,$pars);
            $query="SELECT * from ".table('lang')." WHERE content_type='participation_status_display_name' AND content_name= :pstatus_id";
            $pstatus_display_name=orsee_query($query,$pars);
        }
    } else {
        $pstatus=array('participated'=>0,'noshow'=>0,'participateagain'=>0);
        $pstatus_internal_name=array();
        $pstatus_display_name=array();
    }
}


if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/participation_status_edit.php?pstatus_id=".$pstatus_id);
        }

        $pstatus_internal_name=$_REQUEST['pstatus_internal_name'];
        foreach ($languages as $language) {
            if (!$pstatus_internal_name[$language]) {
                message(lang('missing_language').': "'.lang('internal_name').'" - '.$language,'error');
                $continue=false;
            }
        }
        $pstatus_display_name=$_REQUEST['pstatus_display_name'];
        foreach ($languages as $language) {
            if (!$pstatus_display_name[$language]) {
                message(lang('missing_language').': "'.lang('status_name_displayed_to_participants').'" - '.$language,'error');
                $continue=false;
            }
        }

        if ($continue) {
            $pstatus_internal_name_lang=array();
            $pstatus_display_name_lang=array();
            if (!isset($pstatus_id)) {
                $new=true;
                $query="SELECT pstatus_id+1 as new_pstatus_id FROM ".table('participation_statuses')."
                        ORDER BY pstatus_id DESC LIMIT 1";
                $line=orsee_query($query);
                if (isset($line['new_pstatus_id'])) {
                    $pstatus_id=$line['new_pstatus_id'];
                } else {
                    $pstatus_id=1;
                }
                $pstatus_internal_name_lang['content_type']="participation_status_internal_name";
                $pstatus_internal_name_lang['content_name']=$pstatus_id;
                $pstatus_display_name_lang['content_type']="participation_status_display_name";
                $pstatus_display_name_lang['content_name']=$pstatus_id;
            } else {
                $new=false;
                $pars=array(':pstatus_id'=>$pstatus_id);
                $query="SELECT * from ".table('lang')."
                    WHERE content_type='participation_status_internal_name'
                    AND content_name= :pstatus_id";
                $pstatus_internal_name_lang=orsee_query($query,$pars);
                $query="SELECT * from ".table('lang')."
                    WHERE content_type='participation_status_display_name'
                    AND content_name= :pstatus_id";
                $pstatus_display_name_lang=orsee_query($query,$pars);
            }

            foreach ($languages as $language) {
                $pstatus_internal_name_lang[$language]=$pstatus_internal_name[$language];
                $pstatus_display_name_lang[$language]=$pstatus_display_name[$language];
            }

            if ($new) {
                $pstatus_internal_name['lang_id']=lang__insert_to_lang($pstatus_internal_name_lang);
                $pstatus_display_name['lang_id']=lang__insert_to_lang($pstatus_display_name_lang);
            } else {
                $done=orsee_db_save_array($pstatus_internal_name_lang,"lang",$pstatus_internal_name_lang['lang_id'],"lang_id");
                $done=orsee_db_save_array($pstatus_display_name_lang,"lang",$pstatus_display_name_lang['lang_id'],"lang_id");
            }

            if ($not_assigned) {
                $pstatus=$_REQUEST;
                $pstatus['pstatus_id']=$pstatus_id;
                $form_fields=array_filter_allowed($pstatus,array(
                        'pstatus_id','participated','noshow','participateagain'));
                $form_fields['pstatus_id']=$pstatus_id;
                $done=orsee_db_save_array($form_fields,"participation_statuses",$pstatus_id,"pstatus_id");
            }
            message(lang('changes_saved'));
            log__admin("participation_status_edit","pstatus_id:".$pstatus['pstatus_id']);
            redirect("admin/participation_status_edit.php?pstatus_id=".$pstatus_id);
        } else {
            $pstatus=$_REQUEST;
        }
    }
}


if ($proceed) {
    // form

    show_message();

    echo '
            <form action="participation_status_edit.php" method="POST">'.csrf__field();
    if (isset($pstatus_id)) {
        echo '<input type="hidden" name="pstatus_id" value="'.$pstatus_id.'">';
    }

    echo '          <div class="orsee-panel">
                        <div class="orsee-panel-title">
                            <div class="orsee-panel-title-main">'.lang('edit_participation_status').'</div>
                        </div>
                        <div class="orsee-form-shell">';
    if (isset($pstatus_id)) {
        echo '              <div class="field">
                                <div class="control"><span class="orsee-dense-id-tag">'.lang('id').': '.$pstatus_id.'</span></div>
                            </div>';
    }

    echo '              <div class="field">
                                <label class="label">'.lang('internal_name').':</label>
                                <div class="control">';
    foreach ($languages as $language) {
        if (!isset($pstatus_internal_name[$language])) {
            $pstatus_internal_name[$language]='';
        }
        echo '                      <div class="field">
                                            <label class="label">'.$language.':</label>
                                            <div class="control">
                                                <input class="input is-primary orsee-input orsee-input-text" name="pstatus_internal_name['.$language.']" type="text" maxlength="200" value="'.htmlspecialchars(stripslashes($pstatus_internal_name[$language])).'">
                                            </div>
                                        </div>';
    }
    echo '                      </div>
                            </div>';

    echo '              <div class="field">
                                <label class="label">'.lang('status_name_displayed_to_participants').'</label>
                                <div class="control">';
    foreach ($languages as $language) {
        if (!isset($pstatus_display_name[$language])) {
            $pstatus_display_name[$language]='';
        }
        echo '                      <div class="field">
                                            <label class="label">'.$language.':</label>
                                            <div class="control">
                                                <input class="input is-primary orsee-input orsee-input-text" name="pstatus_display_name['.$language.']" type="text" maxlength="200" value="'.htmlspecialchars(stripslashes($pstatus_display_name[$language])).'">
                                            </div>
                                        </div>';
    }
    echo '                      </div>
                            </div>';

    if ($not_assigned) {
        echo '          <div class="field">
                                <label class="label">'.lang('counts_as_participated').'</label>
                                <div class="control">
                                    <label class="radio"><input type="radio" name="participated" value="1"';
        if ($pstatus['participated']) {
            echo ' CHECKED';
        }
        echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                    <label class="radio"><input type="radio" name="participated" value="0"';
        if (!$pstatus['participated']) {
            echo ' CHECKED';
        }
        echo '>'.lang('no').'</label>
                                </div>
                            </div>';

        echo '          <div class="field">
                                <label class="label">'.lang('counts_as_noshow').'</label>
                                <div class="control">
                                    <label class="radio"><input type="radio" name="noshow" value="1"';
        if ($pstatus['noshow']) {
            echo ' CHECKED';
        }
        echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                    <label class="radio"><input type="radio" name="noshow" value="0"';
        if (!$pstatus['noshow']) {
            echo ' CHECKED';
        }
        echo '>'.lang('no').'</label>
                                </div>
                            </div>';

        echo '          <div class="field">
                                <label class="label">'.lang('allows_to_participate_again').'</label>
                                <div class="control">
                                    <label class="radio"><input type="radio" name="participateagain" value="1"';
        if ($pstatus['participateagain']) {
            echo ' CHECKED';
        }
        echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                                    <label class="radio"><input type="radio" name="participateagain" value="0"';
        if (!$pstatus['participateagain']) {
            echo ' CHECKED';
        }
        echo '>'.lang('no').'</label>
                                </div>
                            </div>';
    }

    echo '              <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                                <div class="orsee-form-row-col has-text-left">
                                    '.button_back('participation_status_main.php').'
                                </div>
                                <div class="orsee-form-row-col has-text-centered">
                                    <input class="button orsee-btn" name="edit" type="submit" value="';
    if (!isset($pstatus_id)) {
        echo lang('add');
    } else {
        echo lang('change');
    }
    echo '                          ">
                                </div>
                                <div class="orsee-form-row-col has-text-right">';

    if (isset($pstatus_id) && check_allow('participationstatus_delete') && $not_assigned) {
        echo button_link('participation_status_delete.php?pstatus_id='.urlencode($pstatus_id),
            lang('delete'),'trash-o','','','orsee-btn--delete');
    }

    echo '                      </div>
                            </div>
                        </div>
                    </div>
            </form>
            <br>';
}
include("footer.php");

?>
