<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_symbol";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) {
        $lang_id=$_REQUEST['lang_id'];
    } else {
        $lang_id="";
    }

    if ($lang_id) {
        $allow=check_allow('lang_symbol_edit','lang_main.php');
    } else {
        $allow=check_allow('lang_symbol_add','lang_main.php');
    }
}

if ($proceed) {
    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    $languages=get_languages();

    if ($lang_id) {
        $content=orsee_db_load_array("lang",$lang_id,"lang_id");
    } else {
        $content=array('content_name'=>'');
    }
    if ($lang_id && (!isset($content['lang_id']))) {
        redirect("admin/lang_main.php");
    }

    if (isset($_REQUEST['save']) && $_REQUEST['save']) {
        $continue=true;
        $can_edit_content_name=(!$lang_id || check_allow('lang_symbol_add'));

        if ($can_edit_content_name) {
            if (!isset($_REQUEST['content_name']) || trim((string)$_REQUEST['content_name'])==='') {
                message(lang('error_lang_symbol_name_required'),'error');
                $continue=false;
            } else {
                $_REQUEST['content_name']=trim((string)$_REQUEST['content_name']);
            }
        }

        if ($continue) {
            $_REQUEST['content_type']="lang";
            $allowed_fields=array('content_type');
            if ($can_edit_content_name) {
                $allowed_fields[]='content_name';
            }
            foreach ($languages as $language) {
                $allowed_fields[]=$language;
            }
            $form_fields=array_filter_allowed($_REQUEST,$allowed_fields);

            if ($lang_id) {
                $done=orsee_db_save_array($form_fields,"lang",$lang_id,"lang_id");
            } else {
                $lang_id=lang__insert_to_lang($form_fields);
            }
            message(lang('changes_saved'));

            if (!$can_edit_content_name) {
                $form_fields['content_name']=$content['content_name'];
            }
            log__admin("language_symbol_edit","lang_id:lang,".$form_fields['content_name']);
            redirect("admin/lang_symbol_edit.php?lang_id=".$lang_id);
        } else {
            $content=$_REQUEST;
        }
    }

    show_message();

    echo '<div class="orsee-panel">
            <div class="orsee-panel-title"><div>'.lang('edit_symbol');
    if ($lang_id) {
        echo ' '.$content['content_name'];
    }
    echo    '</div></div>
            <div class="orsee-form-shell">
                <form action="lang_symbol_edit.php" method="post">
                    <input type="hidden" name="lang_id" value="'.$lang_id.'">
                    '.csrf__field().'
                    <div class="field">
                        <label class="label">'.lang('symbol_name').':</label>';
    if (check_allow('lang_symbol_add')) {
        echo '  <p class="help">'.lang('symbol_name_comment').'</p>';
    }
    echo '              <div class="control">';
    if (check_allow('lang_symbol_add')) {
        echo '<input class="input is-primary orsee-input orsee-input-text" type="text" size="50" maxlength="200" name="content_name" dir="ltr" value="'.htmlspecialchars((string)$content['content_name'],ENT_QUOTES).'">';
    } else {
        echo '<div class="orsee-dense-id"><span class="orsee-dense-id-tag">'.htmlspecialchars((string)$content['content_name'],ENT_QUOTES).'</span></div>';
    }
    echo '              </div>
                    </div>';

    $lang_dirs=lang__is_rtl_all_langs();
    foreach ($languages as $language) {
        if (!isset($content[$language])) {
            $content[$language]='';
        }
        $field_dir=(isset($lang_dirs[$language]) && $lang_dirs[$language] ? 'rtl' : 'ltr');
        echo '      <div class="field">
                        <label class="label">'.$language.':</label>
                        <div class="control">
                            <textarea class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="'.$language.'" rows="2" cols="40" wrap="virtual">'.htmlspecialchars((string)stripslashes($content[$language]),ENT_QUOTES).'</textarea>
                        </div>
                    </div>';
    }

    echo '          <div class="orsee-options-actions-center">
                    <input class="button orsee-btn" type="submit" name="save" value="';
    if ($lang_id) {
        echo lang('change');
    } else {
        echo lang('add');
    }
    echo '              ">
                    </div>
                </form>';

    if ($lang_id && check_allow('lang_symbol_delete')) {
        echo '<div class="orsee-options-actions-center">'.
            button_link_delete('lang_symbol_delete.php?lang_id='.urlencode($lang_id).'&csrf_token='.urlencode(csrf__get_token()),
                lang('delete')).'</div>';
    }
    echo '<div class="orsee-options-actions">'.button_back('lang_main.php').'</div>
        </div>
        </div>';
}
include("footer.php");

?>
