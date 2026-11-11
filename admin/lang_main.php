<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="languages";
$lang_icons_prepare=true;
include("header.php");

if ($proceed) {
    // load languages
    $languages=get_languages();
    $lang_names=lang__get_language_names();

    if (isset($settings['language_enabled_participants']) && $settings['language_enabled_participants']) {
        $enabled_part=explode(",",$settings['language_enabled_participants']);
    } else {
        $enabled_part=array();
    }
    if (isset($settings['language_enabled_public']) && $settings['language_enabled_public']) {
        $enabled_pub=explode(",",$settings['language_enabled_public']);
    } else {
        $enabled_pub=array();
    }


    if (isset($_REQUEST['change_def']) && $_REQUEST['change_def']) {
        $allow=check_allow('lang_avail_edit','lang_main.php');
        if (!csrf__validate_request_message()) {
            redirect("admin/lang_main.php");
        }

        if ($proceed) {
            $parts=array();
            $pubs=array();
            foreach ($languages as $language) {
                if ((isset($_REQUEST['enabled_public'][$language]) && $_REQUEST['enabled_public'][$language]) || $language==$settings['public_standard_language']) {
                    $pubs[]=$language;
                }
                if ((isset($_REQUEST['enabled_participants'][$language]) && $_REQUEST['enabled_participants'][$language]) || $language==$settings['public_standard_language']) {
                    $parts[]=$language;
                }
            }
            $pubs_string=implode(",",$pubs);
            $parts_string=implode(",",$parts);

            $query="SELECT * FROM ".table('options')."
                    WHERE option_type='general' AND option_name='language_enabled_public'";
            $result=orsee_query($query);
            $now=time();
            if (isset($result['option_id'])) {
                $pars=array(':pubs_string'=>$pubs_string);
                $query="UPDATE ".table('options')." SET option_value= :pubs_string
                        WHERE option_type='general' AND option_name='language_enabled_public'";
                $done=or_query($query,$pars);
            } else {
                $pars=array(':pubs_string'=>$pubs_string,
                            ':option_id'=>$now+1);
                $query="INSERT INTO ".table('options')."
                        SET option_id=:option_id,
                        option_type='general',
                        option_name='language_enabled_public',
                        option_value= :pubs_string";
                $done=or_query($query,$pars);
            }

            $query="SELECT * FROM ".table('options')."
                    WHERE option_type='general' AND option_name='language_enabled_participants'";
            $result2=orsee_query($query);
            if (isset($result2['option_id'])) {
                $pars=array(':parts_string'=>$parts_string);
                $query="UPDATE ".table('options')." SET option_value= :parts_string
                        WHERE option_type='general' AND option_name='language_enabled_participants'";
                $done=or_query($query,$pars);
            } else {
                $pars=array(':parts_string'=>$parts_string,
                            ':option_id'=>$now+2);
                $query="INSERT INTO ".table('options')."
                        SET option_id=:option_id,
                        option_type='general',
                        option_name='language_enabled_participants',
                        option_value= :parts_string";
                $done=or_query($query,$pars);
            }
            log__admin("language_availability_edit");
            message(lang('changes_saved'));
            redirect("admin/lang_main.php");
        }
    }
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';
    if (check_allow('lang_symbol_add')) {
        echo button_link('lang_symbol_edit.php?go=true',lang('add_symbol'),'plus-circle').' ';
    }
    if (check_allow('lang_lang_add')) {
        echo button_link('lang_lang_add.php',lang('add_language'),'plus').' ';
    }
    if (check_allow('lang_lang_delete')) {
        echo button_link('lang_lang_delete.php',lang('delete_language'),'times');
    }
    echo '</div>';


    // show languages

    echo '<FORM action="'.thisdoc().'" method="POST">'.csrf__field();
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('installed_languages').'</div>';
    echo '<div class="orsee-table-cell">'.lang('default').'</div>';
    echo '<div class="orsee-table-cell">'.lang('available_in_public_area').'</div>';
    echo '<div class="orsee-table-cell">'.lang('available_for_participants').'</div>';
    echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    echo '</div>';

    $shade=false;
    foreach ($languages as $language) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        echo '<div class="'.$row_class.'">';
        echo '<div class="orsee-table-cell" data-label="'.lang('installed_languages').'"><span class="languageicon langicon-'.$language.'">'.$lang_names[$language].'</span> - '.$language.'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('default').'">';
        if ($language==$settings['admin_standard_language']) {
            echo '[default admin] ';
        }
        if ($language==$settings['public_standard_language']) {
            echo '[default public] ';
        }
        echo '</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('available_in_public_area').'">';
        echo '<INPUT type=checkbox name="enabled_public['.$language.']" value="'.$language.'"';
        if ($language==$settings['public_standard_language'] || !check_allow('lang_avail_edit')) {
            echo ' DISABLED';
        }
        if (in_array($language,$enabled_pub)) {
            echo ' CHECKED';
        }
        echo '>';
        echo '</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('available_for_participants').'">';
        echo '<INPUT type=checkbox name="enabled_participants['.$language.']" value="'.$language.'"';
        if ($language==$settings['public_standard_language'] || !check_allow('lang_avail_edit')) {
            echo ' DISABLED';
        }
        if (in_array($language,$enabled_part)) {
            echo ' CHECKED';
        }
        echo '>';
        echo '</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">';
        if (check_allow('lang_lang_edit')) {
            echo button_link('lang_lang_edit.php?elang='.$language,lang('edit_basic_data'),'pencil-square-o');
        }
        echo '</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">';
        if (check_allow('lang_symbol_edit')) {
            echo button_link('lang_edit.php?el='.$language,lang('edit_words_for').' "'.$language.'"','pencil-square-o');
        }
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    if (check_allow('lang_avail_edit')) {
        echo '<div class="orsee-options-actions-center" style="margin-top: 0.84rem;">';
        echo '<INPUT class="button orsee-btn" type=submit name="change_def" value="'.lang('change').'">';
        echo '</div>';
    }

    echo '</FORM>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
