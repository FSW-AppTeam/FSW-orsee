<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="delete_language";
include("header.php");

if ($proceed) {
    $allow=check_allow('lang_lang_delete','lang_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['elang']) && $_REQUEST['elang']) {
        $tlang=$_REQUEST['elang'];
    } else {
        $tlang="";
    }

    if (isset($_REQUEST['nlang']) && $_REQUEST['nlang']) {
        $slang=$_REQUEST['nlang'];
    } else {
        $slang="";
    }

    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) {
        $reallydelete=true;
    } else {
        $reallydelete=false;
    }

    if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
        $delete=true;
    } else {
        $delete=false;
    }

    $languages=get_languages();
    $lang_names=lang__get_language_names();

    if ($delete || $reallydelete) {
        if (($delete || $reallydelete) && !csrf__validate_request_message()) {
            redirect("admin/lang_lang_delete.php");
        }

        if (!$tlang || !in_array($tlang,$languages)) {
            redirect("admin/lang_main.php");
        }

        if ($proceed) {
            if (!$slang || !in_array($slang,$languages)) {
                redirect("admin/lang_main.php");
            }
        }

        if ($proceed) {
            if ($tlang==$slang) {
                message(lang('language_to_be_deleted_cannot_be_language_to_substitute'),'warning');
                redirect('admin/lang_lang_delete.php?elang='.$tlang.'&nlang='.$slang);
            }
        }

        if ($proceed) {
            if ($tlang==lang('lang')) {
                redirect("admin/lang_main.php");
            }
        }

        if ($proceed) {
            if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
                redirect('admin/lang_main.php');
            }
        }

        if ($proceed && $reallydelete) {
            // update participants and admin
            $tables=array('participants','admin');
            foreach ($tables as $table) {
                $pars=array(':slang'=>$slang,':tlang'=>$tlang);
                $query="UPDATE ".table($table)." SET language= :slang WHERE language= :tlang";
                $done=or_query($query,$pars);
            }
            message(lang('updated_language_settings'));

            // delete language column
            $query="ALTER TABLE ".table('lang')."
                    DROP column ".$tlang;
            $done=or_query($query);

            // bye, bye
            message(lang('language_deleted').': '.$tlang);
            log__admin("language_delete","language:".$tlang);
            redirect('admin/lang_main.php');
        }

        if ($proceed) {
            // confirmation form
            echo '<div class="orsee-panel orsee-form-shell">
                    <div class="orsee-panel-title">'.lang('delete_language').'</div>
                    <div class="orsee-content">
                        <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('do_you_really_want_to_delete').'</div>
                        <div class="field">
                            <label class="label">'.lang('language').'</label>
                            <div>'.htmlspecialchars($lang_names[$tlang].' ('.$tlang.')').'</div>
                        </div>
                        <form action="lang_lang_delete.php" method="POST">
                            <input type="hidden" name="elang" value="'.$tlang.'">
                            <input type="hidden" name="nlang" value="'.$slang.'">
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
    } else {
        // delete form

        echo '<div class="orsee-panel orsee-form-shell">
                <div class="orsee-panel-title">'.lang('delete_language').'</div>
                <div class="orsee-content">
                <form action="lang_lang_delete.php" method="POST">
                '.csrf__field().'
                <div class="field">
                    <label class="label">'.lang('delete_language').'</label>
                    <div><span class="select is-primary">';
        echo '<select name="elang">';
        foreach ($languages as $language) {
            if ($language!=lang('lang')) {
                echo '<OPTION value="'.$language.'"';
                if ($language==$tlang) {
                    echo ' SELECTED';
                }
                echo '>'.$lang_names[$language].' ('.$language.')</OPTION>';
            }
        }
        echo '</select></span></div></div>
                <div class="field">
                    <label class="label">'.lang('copy_users_of_this_lang_to').'</label>
                    <div><span class="select is-primary">';
        echo '<select name="nlang">';
        foreach ($languages as $language) {
            echo '<OPTION value="'.$language.'"';
            if ($language==$slang) {
                echo ' SELECTED';
            }
            echo '>'.$lang_names[$language].' ('.$language.')</OPTION>';
        }
        echo '</select></span></div></div>
                <div class="field has-text-centered">
                    <button class="button orsee-btn orsee-btn--delete" type="submit" name="delete" value="1"><i class="fa fa-trash-o"></i> '.lang('delete').'</button>
                </div>
                </form>
                </div>
                </div>';
    }
}
include("footer.php");

?>
