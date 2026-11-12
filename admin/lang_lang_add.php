<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="add_language";
include("header.php");

if ($proceed) {
    $allow=check_allow('lang_lang_add','lang_main.php');
}

if ($proceed) {
    // load languages
    $languages=get_languages();

    if (isset($_REQUEST['nlang_sc'])) {
        $nlang_sc=strtolower(trim($_REQUEST['nlang_sc']));
    } else {
        $nlang_sc="";
    }
    if (isset($_REQUEST['nlang_name'])) {
        $nlang_name=trim($_REQUEST['nlang_name']);
    } else {
        $nlang_name="";
    }
    if (isset($_REQUEST['nlang_base'])) {
        $nlang_base=trim($_REQUEST['nlang_base']);
    } else {
        $nlang_base="";
    }

    if (isset($_REQUEST['add']) && $_REQUEST['add']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/lang_lang_add.php");
        }

        // check for errors
        $continue=true;

        if (!$nlang_sc) {
            message(lang('error_no_language_shortcut'),'error');
            $continue=false;
        }

        if (in_array($nlang_sc,$languages)) {
            message(lang('error_language_shortcut_exists'),'error');
            $continue=false;
        }

        if (!preg_match("/^[a-z]{2}$/",$nlang_sc)) {
            message(lang('error_language_shortcut_must_be_two_latin_letters'),'error');
            $continue=false;
        }

        if (!$nlang_name) {
            message(lang('error_no_language_name'),'error');
            $continue=false;
        }

        if (!in_array($nlang_base,$languages)) {
            message(lang('error_base_language_does_not_exist'),'error');
            $continue=false;
        }

        // add language
        if ($continue) {
            // as transaction?
            $query="ALTER TABLE ".table('lang')." ADD COLUMN ".$nlang_sc." text";
            $done=or_query($query);
            if ($done) {
                message(lang('language_created').' '.$nlang_sc);
            }

            $query="UPDATE ".table('lang')." SET ".$nlang_sc."=".$nlang_base." ";
            $done=or_query($query);
            if ($done) {
                message(lang('language_items_copied_from_base_language').' '.$nlang_base);
            }

            $pars=array(':nlang_sc'=>$nlang_sc);
            $query="UPDATE ".table('lang')." SET ".$nlang_sc."= :nlang_sc
                    WHERE content_type='lang' AND content_name='lang'";
            $done=or_query($query,$pars);

            $pars=array(':nlang_name'=>$nlang_name);
            $query="UPDATE ".table('lang')." SET ".$nlang_sc."= :nlang_name
                    WHERE content_type='lang' AND content_name='lang_name'";
            $done=or_query($query,$pars);

            // auto-set flag code for the new language from language code fallback rules
            $guess_iso2=lang__guess_flag_for_language($nlang_sc);
            $pars=array(':lang_flag_iso2'=>$guess_iso2);
            $query="UPDATE ".table('lang')." SET ".$nlang_sc."= :lang_flag_iso2
                    WHERE content_type='lang' AND content_name='lang_flag_iso2'";
            $done=or_query($query,$pars);

            // copy localized menu labels/titles from base language to new language
            foreach (array('public','admin') as $menu_area) {
                $menu_config=html__menu_load_config($menu_area);
                $menu_changed=false;
                foreach ($menu_config['items'] as $menu_id=>$menu_item) {
                    if (!is_array($menu_item)) {
                        continue;
                    }
                    foreach (array('menu_term_lang','page_title_lang') as $lang_key) {
                        if (!isset($menu_item[$lang_key]) || !is_array($menu_item[$lang_key])) {
                            continue;
                        }
                        if (!array_key_exists($nlang_sc,$menu_item[$lang_key])) {
                            $menu_item[$lang_key][$nlang_sc]=(isset($menu_item[$lang_key][$nlang_base]) ? (string)$menu_item[$lang_key][$nlang_base] : '');
                            $menu_changed=true;
                        }
                    }
                    $menu_config['items'][$menu_id]=$menu_item;
                }
                if ($menu_changed) {
                    html__menu_save_config($menu_area,$menu_config);
                }
            }

            // copy localized profile layout block texts from base language to new language
            foreach (array('profile_form_public','profile_form_admin_part') as $layout_context) {
                foreach (array('current','draft') as $layout_state) {
                    $layout=participant__load_profile_layout($layout_context,$layout_state);
                    $layout_changed=false;
                    foreach ($layout['blocks'] as $block_index=>$block) {
                        if (!is_array($block) || !isset($block['type'])) {
                            continue;
                        }
                        if (!in_array($block['type'],array('text','section'),true)) {
                            continue;
                        }
                        if (!isset($block['text_lang']) || !is_array($block['text_lang'])) {
                            continue;
                        }
                        if (!array_key_exists($nlang_sc,$block['text_lang'])) {
                            $block['text_lang'][$nlang_sc]=(isset($block['text_lang'][$nlang_base]) ? (string)$block['text_lang'][$nlang_base] : '');
                            $layout['blocks'][$block_index]=$block;
                            $layout_changed=true;
                        }
                    }
                    if ($layout_changed) {
                        participant__save_profile_layout($layout_context,$layout_state,$layout);
                    }
                }
            }

            // copy localized profile-field baseline and variant override texts
            $profile_field_specs=participant__profile_field_editor_specs();
            $localized_policy_keys=array();
            foreach ($profile_field_specs['fields'] as $field_key=>$field_spec) {
                if (!isset($field_spec['control']['kind'])) {
                    continue;
                }
                if (in_array($field_spec['control']['kind'],array('localized_text','localized_textarea'),true)) {
                    $localized_policy_keys[]=$field_key;
                }
            }
            $query="SELECT * FROM ".table('profile_fields');
            $result=or_query($query);
            while ($line=pdo_fetch_assoc($result)) {
                $policy=participant__profile_field_policy_load($line,$profile_field_specs);
                $policy_changed=false;
                foreach (array('current','draft') as $policy_state) {
                    foreach ($localized_policy_keys as $policy_key) {
                        if (!isset($policy[$policy_state]['baseline'][$policy_key]) || !is_array($policy[$policy_state]['baseline'][$policy_key])) {
                            continue;
                        }
                        if (!array_key_exists($nlang_sc,$policy[$policy_state]['baseline'][$policy_key])) {
                            $policy[$policy_state]['baseline'][$policy_key][$nlang_sc]=(isset($policy[$policy_state]['baseline'][$policy_key][$nlang_base]) ? (string)$policy[$policy_state]['baseline'][$policy_key][$nlang_base] : '');
                            $policy_changed=true;
                        }
                    }
                    foreach ($policy[$policy_state]['variants'] as $variant_id=>$variant) {
                        if (!isset($variant['overrides']) || !is_array($variant['overrides'])) {
                            continue;
                        }
                        foreach ($localized_policy_keys as $policy_key) {
                            if (!isset($variant['overrides'][$policy_key]) || !is_array($variant['overrides'][$policy_key])) {
                                continue;
                            }
                            if (!array_key_exists($nlang_sc,$variant['overrides'][$policy_key])) {
                                $variant['overrides'][$policy_key][$nlang_sc]=(isset($variant['overrides'][$policy_key][$nlang_base]) ? (string)$variant['overrides'][$policy_key][$nlang_base] : '');
                                $policy[$policy_state]['variants'][$variant_id]=$variant;
                                $policy_changed=true;
                            }
                        }
                    }
                }
                if ($policy_changed) {
                    $save_policy=array(
                        'current'=>array(
                            'baseline'=>$policy['current']['baseline'],
                            'variants'=>$policy['current']['variants']
                        ),
                        'draft'=>array(
                            'enabled'=>$policy['draft']['enabled'],
                            'type'=>$policy['draft']['type'],
                            'baseline'=>$policy['draft']['baseline'],
                            'variants'=>$policy['draft']['variants']
                        )
                    );
                    $save_field=array(
                        'mysql_column_name'=>$line['mysql_column_name'],
                        'properties'=>property_array_to_db_string($save_policy)
                    );
                    orsee_db_save_array($save_field,'profile_fields',$line['mysql_column_name'],'mysql_column_name');
                }
            }

            log__admin("language_add","language:".$_REQUEST['nlang_sc']);
            redirect("admin/lang_main.php");
        }
    }
}

if ($proceed) {
    show_message();

    echo '<div class="orsee-panel">
            <div class="orsee-panel-title"><div>'.lang('add_language').'</div></div>
            <div class="orsee-form-shell">
                <form action="lang_lang_add.php" method="POST">
                    '.csrf__field().'
                    <div class="field">
                        <label class="label">'.lang('language_shortcut').':</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" type="text" name="nlang_sc" dir="ltr" size="2" maxlength="2" value="'.htmlspecialchars((string)$nlang_sc,ENT_QUOTES).'">
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('language_name_in_lang').':</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" type="text" name="nlang_name" size="20" maxlength="50" value="'.htmlspecialchars((string)$nlang_name,ENT_QUOTES).'">
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('language_based_on').':</label>
                        <div class="control">';
    $lang_names=lang__get_language_names();

    if (!$nlang_base) {
        $nlang_base=$settings['admin_standard_language'];
    }
    echo '<span class="select is-primary"><SELECT name="nlang_base">';
    foreach ($languages as $language) {
        echo '<OPTION value="'.$language.'"';
        if ($language==$nlang_base) {
            echo ' SELECTED';
        }
        echo '>'.$lang_names[$language].'</OPTION>
                ';
    }
    echo '              </SELECT></span>
                        </div>
                    </div>
                    <div class="orsee-options-actions-center">
                        <input class="button orsee-btn" type="submit" name="add" value="'.lang('add').'">
                    </div>
                </form>
                <div class="orsee-options-actions">'.button_back('lang_main.php').'</div>
            </div>
        </div>';
}
include("footer.php");

?>
