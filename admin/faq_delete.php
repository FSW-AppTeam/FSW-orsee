<?php
// part of orsee. see orsee.org
ob_start();
$title="delete_faq";
$menu__area="options";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['faq_id'])) {
        $faq_id=$_REQUEST['faq_id'];
    } else {
        $faq_id="";
    }

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect('admin/faq_edit.php?faq_id='.$faq_id);
    }
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    $allow=check_allow('faq_delete','faq_edit.php?faq_id='.$faq_id);
}

if ($proceed) {
    $question=faq__load_question($faq_id);
    $answer=faq__load_answer($faq_id);

    // load languages
    $languages=get_languages();

    if ($reallydelete) {
        if (!csrf__validate_request_message()) {
            redirect('admin/faq_delete.php?faq_id='.$faq_id);
        }

        $pars=array(':faq_id'=>$faq_id);
        $query="DELETE FROM ".table('lang')."
                WHERE content_type='faq_question'
                AND content_name= :faq_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('lang')."
                WHERE content_type='faq_answer'
                AND content_name= :faq_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('faqs')."
                WHERE faq_id= :faq_id";
        $result=or_query($query,$pars);

        message(lang('faq_deleted'));
        log__admin("faq_delete","faq_id:".$faq_id);
        redirect('admin/faq_main.php');
    }
}

if ($proceed) {
    // form

    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_faq').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning"><b>'.lang('do_you_really_want_to_delete').'</b></div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($faq_id).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('question').'</label>
                    <div>'.stripslashes($question[lang('lang')]).'</div>
                </div>
                <form action="faq_delete.php" method="POST">
                    <input type="hidden" name="faq_id" value="'.$faq_id.'">
                    '.csrf__field().'
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
