<?php
// part of orsee. see orsee.org
ob_start();
if (isset($_REQUEST['faq_id'])) {
    $faq_id=$_REQUEST['faq_id'];
} else {
    $faq_id="";
}
$title="edit_faq";
$menu__area="options";
include("header.php");

if ($proceed) {
    if ($faq_id) {
        $allow=check_allow('faq_edit','faq_main.php');
    } else {
        $allow=check_allow('faq_add','faq_main.php');
    }
}

if ($proceed) {
    // load faq question and answer from lang table
    if ($faq_id) {
        $faq=orsee_db_load_array("faqs",$faq_id,"faq_id");
        $question=faq__load_question($faq_id);
        $answer=faq__load_answer($faq_id);
    } else {
        $faq=array('evaluation'=>0);
        $question=array();
        $answer=array();
    }

    // load languages
    $languages=get_languages();
    $lang_dirs=lang__is_rtl_all_langs();

    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        $rquestion=$_REQUEST['question'];
        $ranswer=$_REQUEST['answer'];

        foreach ($languages as $language) {
            if (!$rquestion[$language]) {
                message(lang('missing_question_in_language').": ".$language,'error');
                $continue=false;
            }
            if (!$ranswer[$language]) {
                message(lang('missing_answer_in_language').": ".$language,'error');
                $continue=false;
            }
        }

        foreach ($languages as $language) {
            $question[$language]=$rquestion[$language];
            $answer[$language]=$ranswer[$language];
        }

        if ($continue) {
            if (isset($_REQUEST['evaluation']) && $_REQUEST['evaluation']) {
                $faq['evaluation']=$_REQUEST['evaluation'];
            } else {
                $faq['evaluation']=0;
            }
            if (!$faq_id) {
                $new_faq_id=time();
                $faq['faq_id']=$new_faq_id;

                $done=orsee_db_save_array($faq,"faqs",$faq['faq_id'],"faq_id");
                $question['content_name']=$new_faq_id;
                $question['content_type']="faq_question";
                $done=lang__insert_to_lang($question);

                $answer['content_name']=$new_faq_id;
                $answer['content_type']="faq_answer";
                $done=lang__insert_to_lang($answer);

                log__admin("faq_create","faq_id:".$new_faq_id);
            } else {
                $faq['faq_id']=$faq_id;
                $done=orsee_db_save_array($faq,"faqs",$faq['faq_id'],"faq_id");
                $done=orsee_db_save_array($question,"lang",$question['lang_id'],"lang_id");
                $done=orsee_db_save_array($answer,"lang",$answer['lang_id'],"lang_id");
                log__admin("faq_edit","faq_id:".$faq_id);
            }

            message(lang('changes_saved'));
            redirect('admin/faq_edit.php?faq_id='.$question['content_name']);
        }
    }
}

if ($proceed) {
    show_message();
    // form
    echo '  <form action="faq_edit.php" method="POST">
                <input type="hidden" name="faq_id" value="'.$faq_id.'">
                '.csrf__field().'
                <div class="orsee-panel">
                    <div class="orsee-panel-title">
                        <div class="orsee-panel-title-main">';
    if ($faq_id) {
        echo lang('edit_faq');
    } else {
        echo lang('add_faq');
    }
    echo '                  </div>
                    </div>
                    <div class="orsee-form-shell">';
    if ($faq_id) {
        echo '          <div class="field">
                            <div class="control"><span class="orsee-dense-id-tag">'.lang('id').': '.$faq_id.'</span></div>
                        </div>';
    }
    echo '          <div class="field">
                            <label class="label">'.lang('this_faq_answered_questions_of_xxx').'</label>
                            <div class="control">
                                <input class="input is-primary orsee-input orsee-input-text" name="evaluation" type="text" dir="ltr" maxlength="5" value="'.htmlspecialchars($faq['evaluation']).'"> '.lang('persons').'
                            </div>
                        </div>';

    foreach ($languages as $language) {
        if (!isset($question[$language])) {
            $question[$language]="";
        }
        if (!isset($answer[$language])) {
            $answer[$language]="";
        }
        $field_dir=(isset($lang_dirs[$language]) && $lang_dirs[$language] ? 'rtl' : 'ltr');
        echo '  <div class="field">
                    <label class="label">'.$language.':</label>
                </div>
                <div class="field">
                    <label class="label">'.lang('question_in_xxxlang').' '.$language.'</label>
                    <div class="control">
                        <textarea class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="question['.$language.']" rows="3" wrap="virtual">'.htmlspecialchars(stripslashes($question[$language])).'</textarea>
                    </div>
                </div>
                <div class="field">
                    <label class="label">'.lang('answer_in_xxxlang').' '.$language.'</label>
                    <div class="control">
                        <textarea class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="answer['.$language.']" rows="20" wrap="virtual">'.htmlspecialchars(stripslashes($answer[$language])).'</textarea>
                    </div>
                </div>';
    }

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                                <div class="orsee-form-row-col has-text-left">
                                    '.button_back('faq_main.php').'
                                </div>
                                <div class="orsee-form-row-col has-text-centered">
                                    <input class="button orsee-btn" name="edit" type="submit" value="';
    if ($faq_id) {
        echo lang('change');
    } else {
        echo lang('add');
    }
    echo '                      ">
                                </div>
                                <div class="orsee-form-row-col has-text-right">';

    if ($faq_id && check_allow('faq_delete')) {
        echo button_link('faq_delete.php?faq_id='.urlencode($faq_id).'&csrf_token='.urlencode(csrf__get_token()),
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
