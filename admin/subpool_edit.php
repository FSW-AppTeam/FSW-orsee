<?php
// part of orsee. see orsee.org
ob_start();
if (isset($_REQUEST['subpool_id'])) {
    $subpool_id=$_REQUEST['subpool_id'];
} else {
    $subpool_id="";
}
$menu__area="options";
$title="data_for_subpool";
include("header.php");

if ($proceed) {
    if ($subpool_id) {
        $allow=check_allow('subjectpool_edit','subpool_main.php');
    } else {
        $allow=check_allow('subjectpool_add','subpool_main.php');
    }
}

if ($proceed) {
    // load languages
    $languages=get_languages();
    $exptypes=load_external_experiment_types();

    if ($subpool_id) {
        $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
        if (!isset($subpool['subpool_id'])) {
            redirect("admin/subpool_main.php");
        } else {
            $exptype_ids=db_string_to_id_array($subpool['experiment_types']);
            $subpool['exptypes']=array();
            foreach ($exptype_ids as $exptype_id) {
                $subpool['exptypes'][$exptype_id]=$exptype_id;
            }
            $pars=array(':subpool_id'=>$subpool_id);
            $query="SELECT * from ".table('lang')." WHERE content_type='subjectpool' AND content_name= :subpool_id";
            $selfdesc=orsee_query($query,$pars);
        }
    } else {
        $subpool=array('subpool_name'=>'','subpool_description'=>'','subpool_type'=>'','exptypes'=>array(),'show_at_registration_page'=>'');
        $selfdesc=array();
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/subpool_edit.php?subpool_id=".$subpool_id);
        }

        if (!$_REQUEST['subpool_name']) {
            message(lang('name_for_subpool_required'),'error');
            $continue=false;
        }

        $exptype_ids=array();
        foreach ($exptypes as $exptype_id=>$exptype) {
            if (isset($_REQUEST['exptypes'][$exptype_id]) && $_REQUEST['exptypes'][$exptype_id]) {
                $exptype_ids[]=$exptype_id;
            }
        }
        if (count($exptype_ids)==0) {
            message(lang('at_minimum_one_exptype_mapping_required'),'error');
            $continue=false;
        }

        $selfdesc=$_REQUEST['selfdesc'];
        if (!$subpool_id || $subpool_id > 1) {
            foreach ($languages as $language) {
                if (!(isset($selfdesc[$language]) && $selfdesc[$language])) {
                    message(lang('missing_language').': '.$language,'error');
                    $continue=false;
                }
            }
        }

        if ($subpool_id==1) {
            $_REQUEST['show_at_registration_page']='n';
            foreach ($languages as $language) {
                $selfdesc[$language]='';
            }
        }

        if ($continue) {
            if (!$subpool_id) {
                $new=true;
                $query="SELECT subpool_id+1 as new_sub FROM ".table('subpools')."
                        ORDER BY subpool_id DESC LIMIT 1";
                $line=orsee_query($query);
                $subpool_id=$line['new_sub'];
                $lsub['content_type']="subjectpool";
                $lsub['content_name']=$subpool_id;
            } else {
                $new=false;
                $pars=array(':subpool_id'=>$subpool_id);
                $query="SELECT * from ".table('lang')."
                        WHERE content_type='subjectpool'
                        AND content_name= :subpool_id";
                $lsub=orsee_query($query,$pars);
            }

            $subpool=$_REQUEST;
            $subpool['experiment_types']=id_array_to_db_string($exptype_ids);
            foreach ($languages as $language) {
                $lsub[$language]=$selfdesc[$language];
            }
            $form_fields=array_filter_allowed($subpool,array(
                    'subpool_id','subpool_name','subpool_description',
                    'experiment_types','show_at_registration_page'));
            $form_fields['subpool_id']=$subpool_id;
            $done=orsee_db_save_array($form_fields,"subpools",$subpool_id,"subpool_id");
            if ($new) {
                $lsub['lang_id']=lang__insert_to_lang($lsub);
            } else {
                $done=orsee_db_save_array($lsub,"lang",$lsub['lang_id'],"lang_id");
            }

            message(lang('changes_saved'));
            log__admin("subjectpool_edit","subjectpool:".$subpool['subpool_name']."\nsubpool_id:".$subpool['subpool_id']);
            redirect("admin/subpool_edit.php?subpool_id=".$subpool_id);
        } else {
            $subpool=$_REQUEST;
            $subpool['exptypes']=array();
            foreach ($exptype_ids as $exptype_id) {
                $subpool['exptypes'][$exptype_id]=$exptype_id;
            }
        }
    }
}

if ($proceed) {
    // form
    show_message();
    echo '
        <form action="subpool_edit.php" method="POST">
        <input type="hidden" name="subpool_id" value="'.$subpool_id.'">
        '.csrf__field().'
        <div class="orsee-panel">
            <div class="orsee-panel-title">
                <div class="orsee-panel-title-main">'.lang('data_for_subpool').'</div>
            </div>
            <div class="orsee-form-shell">
                <div class="field">
                    <div class="control"><span class="orsee-dense-id-tag">'.lang('id').': '.$subpool_id.'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('name').':</label>
                    <div class="control">
                        <input class="input is-primary orsee-input orsee-input-text" name="subpool_name" type="text" maxlength="100" value="'.htmlspecialchars($subpool['subpool_name']).'">
                    </div>
                </div>
                <div class="field">
                    <label class="label">'.lang('description').':</label>
                    <div class="control">
                        <textarea class="textarea is-primary orsee-textarea" name="subpool_description" rows="5" wrap="virtual">'.htmlspecialchars($subpool['subpool_description']).'</textarea>
                    </div>
                </div>
                <div class="field">
                    <label class="label">'.lang('can_request_invitations_for').'</label>
                    <div class="control">';
    experiment_ext_types__checkboxes('exptypes',lang('lang'),$subpool['exptypes']);
    echo '          </div>
                </div>';

    if (!$subpool_id || $subpool_id>1) {
        echo '<div class="field">
                <label class="label">'.lang('show_at_registration_page?').'</label>
                <div class="control">
                    <label class="radio"><input type="radio" name="show_at_registration_page" value="y"';
        if ($subpool['show_at_registration_page']=="y") {
            echo ' CHECKED';
        }
        echo '>'.lang('yes').'</label>&nbsp;&nbsp;
                    <label class="radio"><input type="radio" name="show_at_registration_page" value="n"';
        if ($subpool['show_at_registration_page']!="y") {
            echo ' CHECKED';
        }
        echo '>'.lang('no').'</label>
                </div>
            </div>';
        echo '<div class="field">
                <label class="label">'.lang('registration_page_options').'</label>
            </div>';
        foreach ($languages as $language) {
            if (!isset($selfdesc[$language])) {
                $selfdesc[$language]='';
            }
            echo '  <div class="field">
                        <label class="label">'.$language.':</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" style="width: 100%; max-width: 100%;" name="selfdesc['.$language.']" type="text" maxlength="200" value="'.htmlspecialchars($selfdesc[$language]).'">
                        </div>
                    </div>';
        }
    }
    echo '      <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                    <div class="orsee-form-row-col has-text-left">
                        '.button_back('subpool_main.php').'
                    </div>
                    <div class="orsee-form-row-col has-text-centered">
                        <input class="button orsee-btn" name="edit" type="submit" value="';
    if (!$subpool_id) {
        echo lang('add');
    } else {
        echo lang('change');
    }
    echo '          ">
                    </div>
                    <div class="orsee-form-row-col has-text-right">';

    if ($subpool_id && $subpool_id>1 && check_allow('subjectpool_delete')) {
        echo button_link('subpool_delete.php?subpool_id='.urlencode($subpool_id),
            lang('delete'),'trash-o','','','orsee-btn--delete');
    }

    echo '          </div>
                </div>
            </div>
        </form><br>';
}
include("footer.php");

?>
