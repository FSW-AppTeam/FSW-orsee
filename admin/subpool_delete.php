<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_subpool";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['subpool_id'])) {
        $subpool_id=$_REQUEST['subpool_id'];
    } else {
        $subpool_id="";
    }

    if (!$subpool_id || !$subpool_id>1) {
        redirect('admin/subpool_edit.php?subpool_id='.$subpool_id);
    }

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect('admin/subpool_edit.php?subpool_id='.$subpool_id);
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    $allow=check_allow('subjectpool_delete','subpool_edit.php?subpool_id='.$subpool_id);
}

if ($proceed) {
    // load languages
    $languages=get_languages();
    $exptypes=load_external_experiment_types();

    // load subject pool
    $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
    if (!isset($subpool['subpool_id'])) {
        redirect("admin/subpool_main.php");
    }
}

if ($proceed) {
    $exptype_ids=db_string_to_id_array($subpool['experiment_types']);
    $subpool['exptypes']=array();
    foreach ($exptype_ids as $exptype_id) {
        $subpool['exptypes'][]=$exptypes[$exptype_id][lang('lang')];
    }
    unset($subpool['experiment_types']);
    $pars=array(':subpool_id'=>$subpool_id);
    $query="SELECT * from ".table('lang')." WHERE content_type='subjectpool' AND content_name= :subpool_id";
    $selfdesc=orsee_query($query,$pars);
    foreach ($languages as $language) {
        $subpool['selfdesc_'.$language]=$selfdesc[$language];
    }

    echo '<center>';

    if ($reallydelete) {
        if (!csrf__validate_request_message()) {
            redirect("admin/subpool_delete.php?subpool_id=".$subpool_id);
        }

        if (isset($_REQUEST['merge_with']) && $_REQUEST['merge_with']) {
            $merge_with=$_REQUEST['merge_with'];
        } else {
            $merge_with=1;
        }
        $subpools=subpools__get_subpools();
        if (!isset($subpools[$merge_with])) {
            redirect("admin/subpool_main.php");
        } else {
            // transaction?
            $pars=array(':subpool_id'=>$subpool_id);
            $query="DELETE FROM ".table('subpools')."
                    WHERE subpool_id= :subpool_id";
            $result=or_query($query,$pars);

            $pars=array(':subpool_id'=>$subpool_id);
            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :subpool_id
                    AND content_type='subjectpool'";
            $result=or_query($query,$pars);

            $pars=array(':subpool_id'=>$subpool_id,':merge_with'=>$merge_with);
            $query="UPDATE ".table('participants')."
                    SET subpool_id= :merge_with
                    WHERE subpool_id= :subpool_id";
            $result=or_query($query,$pars);

            log__admin("subjectpool_delete","subjectpool:".$subpool['subpool_name']);
            message(lang('subpool_deleted_part_moved_to').' "'.$subpools[$merge_with]['subpool_name'].'".');
            redirect("admin/subpool_main.php");
        }
    }
}

if ($proceed) {
    // form
    echo '<div class="orsee-panel orsee-form-shell">
                <div class="orsee-panel-title">'.lang('delete_subpool').'</div>
                <div class="orsee-content">
                    <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('really_delete_subpool?').'</div>
                    <div class="field">
                        <label class="label">'.lang('id').'</label>
                        <div><span class="orsee-dense-id-tag">'.htmlspecialchars($subpool['subpool_id']).'</span></div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('name').'</label>
                        <div>'.htmlspecialchars($subpool['subpool_name']).'</div>
                    </div>
                    <form action="subpool_delete.php" method="POST">
                        <input type="hidden" name="subpool_id" value="'.$subpool_id.'">
                        '.csrf__field().'
                        <div class="field">
                            <label class="label">'.lang('merge_subject_pool_with').'</label>
                            <div>'.subpools__select_field("merge_with","1",array($subpool_id)).'</div>
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
