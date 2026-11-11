<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_symbol";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) {
        $lang_id=$_REQUEST['lang_id'];
    } else {
        $lang_id="";
    }
    if (!$lang_id) {
        redirect("admin/lang_main.php");
    }
}

if ($proceed) {
    $allow=check_allow('lang_symbol_delete','lang_symbol_edit.php?lang_id='.$lang_id);
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            redirect('admin/lang_symbol_edit.php?lang_id='.$lang_id);
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

    $symbol=orsee_db_load_array("lang",$lang_id,"lang_id");
    if (!isset($symbol['lang_id'])) {
        redirect("admin/lang_main.php");
    }
}

if ($proceed) {
    if ($reallydelete) {
        $pars=array(':lang_id'=>$lang_id);
        $query="DELETE FROM ".table('lang')."
                WHERE lang_id= :lang_id";
        $result=or_query($query,$pars);

        message(lang('symbol_deleted'));
        log__admin("language_symbol_delete","lang_id:lang,".$symbol['content_name']);
        redirect('admin/lang_edit.php');
    }
}

if ($proceed) {
    // form
    echo '<div class="orsee-panel orsee-form-shell">
            <div class="orsee-panel-title">'.lang('delete_symbol').'</div>
            <div class="orsee-content">
                <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('do_you_really_want_to_delete').'</div>
                <div class="field">
                    <label class="label">'.lang('id').'</label>
                    <div><span class="orsee-dense-id-tag">'.htmlspecialchars($symbol['lang_id']).'</span></div>
                </div>
                <div class="field">
                    <label class="label">'.lang('symbol').'</label>
                    <div>'.htmlspecialchars($symbol['content_name']).'</div>
                </div>
                <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                    <div class="orsee-form-row-col">
                        '.button_link(
        'lang_symbol_delete.php?lang_id='.urlencode($lang_id).'&reallydelete=true&csrf_token='.urlencode(csrf__get_token()),
        lang('yes_delete'),
        'check-square',
        '',
        '',
        'orsee-btn--delete'
    ).'
                    </div>
                    <div class="orsee-form-row-col has-text-right">
                        '.button_link(
        'lang_symbol_delete.php?lang_id='.urlencode($lang_id).'&betternot=true&csrf_token='.urlencode(csrf__get_token()),
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
