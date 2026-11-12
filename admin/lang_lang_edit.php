<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_language";
include("header.php");

if ($proceed) {
    $allow=check_allow('lang_lang_edit','lang_main.php');
}

if ($proceed) {
    $languages=get_languages();

    if (isset($_REQUEST['elang']) && $_REQUEST['elang'] && in_array(trim($_REQUEST['elang']),$languages)) {
        $tlang=trim($_REQUEST['elang']);
        $tlang_name=load_language_symbol('lang_name',$tlang);
        $tlang_flag_iso2=strtolower(trim((string)load_language_symbol('lang_flag_iso2',$tlang)));
        if ($tlang_flag_iso2==='lang_flag_iso2') {
            $tlang_flag_iso2='';
        }
        if ($tlang_flag_iso2==='') {
            $tlang_flag_iso2=lang__guess_flag_for_language($tlang);
        }
        $tlang_is_rtl=load_language_symbol('lang_is_rtl',$tlang);
        if ($tlang_is_rtl!=='y') {
            $tlang_is_rtl='n';
        }
    } else {
        redirect("admin/lang_main.php");
    }
}

if ($proceed) {
    if (isset($_REQUEST['add']) && $_REQUEST['add']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/lang_lang_edit.php?elang=".$tlang);
        }

        // check for errors
        $continue=true;

        if (!$_REQUEST['lang_name']) {
            message(lang('error_no_language_name'),'error');
            $continue=false;
        }

        // add language
        if ($continue) {
            $pars=array(':lang_name'=>$_REQUEST['lang_name']);
            $query="UPDATE ".table('lang')." SET ".$tlang."= :lang_name
                    WHERE content_type='lang' AND content_name='lang_name'";
            $done=or_query($query,$pars);

            $tlang_flag_iso2=strtolower(trim((string)$_REQUEST['lang_flag_iso2']));
            if ($tlang_flag_iso2!=='none' && !preg_match('/^[a-z]{2}$/',$tlang_flag_iso2)) {
                $tlang_flag_iso2='';
            }
            $pars=array(':lang_flag_iso2'=>$tlang_flag_iso2);
            $query="UPDATE ".table('lang')." SET ".$tlang."= :lang_flag_iso2
                    WHERE content_type='lang' AND content_name='lang_flag_iso2'";
            $done=or_query($query,$pars);

            $tlang_is_rtl=(isset($_REQUEST['lang_is_rtl']) && $_REQUEST['lang_is_rtl']==='y' ? 'y' : 'n');
            $pars=array(':lang_is_rtl'=>$tlang_is_rtl);
            $query="UPDATE ".table('lang')." SET ".$tlang."= :lang_is_rtl
                    WHERE content_type='lang' AND content_name='lang_is_rtl'";
            $done=or_query($query,$pars);

            message(lang('changes_saved'));
            log__admin("language_edit","language:".$tlang);
            redirect("admin/lang_lang_edit.php?elang=".$tlang);
        }
        $tlang_name=$_REQUEST['lang_name'];
        $tlang_flag_iso2=strtolower(trim((string)$_REQUEST['lang_flag_iso2']));
        if ($tlang_flag_iso2!=='none' && !preg_match('/^[a-z]{2}$/',$tlang_flag_iso2)) {
            $tlang_flag_iso2='';
        }
        $tlang_is_rtl=(isset($_REQUEST['lang_is_rtl']) && $_REQUEST['lang_is_rtl']==='y' ? 'y' : 'n');
    }
}

if ($proceed) {
    show_message();
    $rtl_options=array(
        array('value'=>'n','label'=>lang('n')),
        array('value'=>'y','label'=>lang('y'))
    );

    echo '<div class="orsee-panel">
            <div class="orsee-panel-title"><div>'.lang('edit_language').' '.$tlang_name.' ('.$tlang.')</div></div>
            <div class="orsee-form-shell">
                <form action="lang_lang_edit.php" method="POST">
                    '.csrf__field().'
                    <input type="hidden" name="elang" value="'.$tlang.'">
                    <div class="field">
                        <label class="label">'.lang('language_name_in_lang').':</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" type="text" name="lang_name" size="20" maxlength="50" value="'.htmlspecialchars((string)$tlang_name,ENT_QUOTES).'">
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('language_is_right_to_left_lang').':</label>
                        <div class="control"><div style="display: inline-flex; gap: 0.8rem; flex-wrap: wrap;">';
    foreach ($rtl_options as $rtl_option) {
        echo '<label class="checkbox orsee-checkline"><input type="radio" name="lang_is_rtl" value="'.$rtl_option['value'].'"'.($tlang_is_rtl===$rtl_option['value'] ? ' CHECKED' : '').'> '.$rtl_option['label'].'</label>';
    }
    echo '              </div></div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('flag_for_language').':</label>
                        <div class="control"><span class="select is-primary"><select name="lang_flag_iso2">';
    echo '<option value="none"';
    if ($tlang_flag_iso2==='none') {
        echo ' selected';
    }
    echo '>No flag</option>';
    $country_options=pform_options_phone_country_options();
    if ($tlang_flag_iso2!=='' && $tlang_flag_iso2!=='none' && !isset($country_options[$tlang_flag_iso2])) {
        $country_options[$tlang_flag_iso2]=$tlang_flag_iso2.' ('.$tlang_flag_iso2.')';
    }
    foreach ($country_options as $iso2=>$label) {
        echo '<option value="'.htmlspecialchars((string)$iso2,ENT_QUOTES).'"';
        if ($tlang_flag_iso2===(string)$iso2) {
            echo ' selected';
        }
        echo '>'.htmlspecialchars((string)$label,ENT_QUOTES).'</option>';
    }
    echo '                  </select></span>
                        </div>
                    </div>
                    <div class="orsee-options-actions-center">
                        <input class="button orsee-btn" type="submit" name="add" value="'.lang('change').'">
                    </div>
                </form>
                <div class="orsee-form-row-grid orsee-form-row-grid--2">
                    <div class="orsee-form-row-col has-text-centered">';
    if (check_allow('lang_lang_export')) {
        echo button_link('lang_lang_export.php?lang_id='.urlencode($tlang), lang('export_language'),'cloud-upload');
    }
    echo '          </div>
                    <div class="orsee-form-row-col has-text-centered">';
    if (check_allow('lang_lang_import')) {
        echo button_link('lang_lang_import.php?lang_id='.urlencode($tlang), lang('import_language'),'cloud-download');
    }
    echo '          </div>
                </div>
                <div class="orsee-options-actions">'.button_back('lang_main.php').'</div>
            </div>
        </div>';
}
include("footer.php");

?>
