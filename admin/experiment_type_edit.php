<?php
// part of orsee. see orsee.org
ob_start();
if (isset($_REQUEST['exptype_id'])) {
    $exptype_id=$_REQUEST['exptype_id'];
} else {
    $exptype_id="";
}
$menu__area="options";
$title="data_for_exptype";
include("header.php");

if ($proceed) {
    // load languages
    $languages=get_languages();

    if ($exptype_id) {
        $allow=check_allow('experimenttype_edit','experiment_type_main.php');
    } else {
        $allow=check_allow('experimenttype_add','experiment_type_main.php');
    }
}

if ($proceed) {
    if ($exptype_id) {
        $exptype=orsee_db_load_array("experiment_types",$exptype_id,"exptype_id");
        $map=explode(",",$exptype['exptype_mapping']);
        foreach ($map as $etype) {
            $exptype['exptype_map'][$etype]=$etype;
        }
        $query="SELECT * from ".table('lang')." WHERE content_type='experiment_type' AND content_name='".$exptype_id."'";
        $selfdesc=orsee_query($query);
    } else {
        $exptype=array('exptype_name'=>'','exptype_description'=>'');
        $selfdesc=array();
    }

    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/experiment_type_edit.php?exptype_id=".$exptype_id);
        }

        if (!$_REQUEST['exptype_name']) {
            message(lang('name_for_exptype_required'),'error');
            $continue=false;
        }

        $map=array();

        $types=$system__experiment_types;
        foreach ($types as $etype) {
            if (isset($_REQUEST['exptype_map'][$etype]) && $_REQUEST['exptype_map'][$etype]) {
                $map[]=$_REQUEST['exptype_map'][$etype];
            }
        }
        if (count($map)==0) {
            message(lang('at_minimum_one_exptype_mapping_required'),'error');
            $continue=false;
        }

        $selfdesc=$_REQUEST['selfdesc'];
        if (!$exptype_id || $exptype_id > 1) {
            foreach ($languages as $language) {
                if (!$selfdesc[$language]) {
                    message(lang('missing_language').': '.$language,'error');
                    $continue=false;
                }
            }
        }

        if ($continue) {
            if (!$exptype_id) {
                $new_entry=true;
                $query="SELECT exptype_id+1 as new_sub FROM ".table('experiment_types')."
                        ORDER BY exptype_id DESC LIMIT 1";
                $line=orsee_query($query);
                $exptype_id=$line['new_sub'];
                $lsub['content_type']="experiment_type";
                $lsub['content_name']=$exptype_id;
            } else {
                $new_entry=false;
                $query="SELECT * from ".table('lang')."
                        WHERE content_type='experiment_type'
                        AND content_name='".$exptype_id."'";
                $lsub=orsee_query($query);
            }

            $exptype=$_REQUEST;
            $exptype['exptype_mapping']=implode(",",$map);

            foreach ($languages as $language) {
                $lsub[$language]=$selfdesc[$language];
            }

            $form_fields=array_filter_allowed($exptype,array(
                    'exptype_id','exptype_name','exptype_description','exptype_mapping'));
            $form_fields['exptype_id']=$exptype_id;
            $done=orsee_db_save_array($form_fields,"experiment_types",$exptype_id,"exptype_id");

            if ($new_entry) {
                $done=lang__insert_to_lang($lsub);
            } else {
                $done=orsee_db_save_array($lsub,"lang",$lsub['lang_id'],"lang_id");
            }
            log__admin("experimenttype_edit",$exptype['exptype_name']);

            message(lang('changes_saved'));
            redirect("admin/experiment_type_edit.php?exptype_id=".$exptype_id);
        } else {
            $exptype=$_REQUEST;
        }
    }
}

if ($proceed) {
    // form

    show_message();

    echo '
            <form action="experiment_type_edit.php" method="POST">
                <input type="hidden" name="exptype_id" value="'.$exptype_id.'">
                '.csrf__field().'
                <div class="orsee-panel">
                    <div class="orsee-panel-title">
                        <div class="orsee-panel-title-main">'.lang('data_for_exptype').'</div>
                    </div>
                    <div class="orsee-form-shell">
                        <div class="field">
                            <div class="control"><span class="orsee-dense-id-tag">'.lang('id').': '.$exptype_id.'</span></div>
                        </div>
                        <div class="field">
                            <label class="label">'.lang('name').':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="exptype_name" type="text" maxlength="100" value="'.htmlspecialchars($exptype['exptype_name']).'">
                            </div>
                        </div>
                        <div class="field">
                            <label class="label">'.lang('description').':</label>
                            <div class="control">
                                <textarea class="textarea is-primary orsee-textarea" name="exptype_description" rows="5" wrap="virtual">'.htmlspecialchars(stripslashes($exptype['exptype_description'])).'</textarea>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label">'.lang('assigned_internal_experiment_types').'</label>
                            <div class="control">';
    $experiment_types=$system__experiment_types;
    foreach ($experiment_types as $etype) {
        echo '<input type="checkbox" name="exptype_map['.$etype.']" value="'.$etype.'"';
        if (isset($exptype['exptype_map'][$etype]) && $exptype['exptype_map'][$etype]) {
            echo ' CHECKED';
        }
        echo '>'.$lang[$etype].'
                    <BR>';
    }

    echo '                  </div>
                        </div>
                        <div class="field">
                            <label class="label">'.lang('public_exptype_description').'</label>
                        </div>';

    foreach ($languages as $language) {
        if (!isset($selfdesc[$language])) {
            $selfdesc[$language]='';
        }
        echo '  <div class="field">
                            <label class="label">'.$language.':</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="selfdesc['.$language.']" type="text" maxlength="200" value="'.htmlspecialchars(stripslashes($selfdesc[$language])).'">
                            </div>
                        </div>';
    }
    echo '
                        <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                            <div class="orsee-form-row-col has-text-left">
                                '.button_back('experiment_type_main.php').'
                            </div>
                            <div class="orsee-form-row-col has-text-centered">
                                <input class="button orsee-btn" name="edit" type="submit" value="';
    if (!$exptype_id) {
        echo lang('add');
    } else {
        echo lang('change');
    }
    echo '">
                            </div>
                            <div class="orsee-form-row-col has-text-right">';

    if ($exptype_id && check_allow('experimenttype_delete')) {
        echo button_link('experiment_type_delete.php?exptype_id='.urlencode($exptype_id),
            lang('delete'),'trash-o','','','orsee-btn--delete');
    }

    echo '              </div>
                        </div>
                    </div>
                </form>
                <br>';
}
include("footer.php");

?>
