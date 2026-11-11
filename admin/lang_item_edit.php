<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
if (isset($_REQUEST['item'])) {
    $item=$_REQUEST['item'];
} else {
    $item='';
}
$title="options";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['item'])) {
        $item=$_REQUEST['item'];
    } else {
        redirect("admin/");
    }
}

if ($proceed) {
    if (isset($_REQUEST['id'])) {
        $id=$_REQUEST['id'];
    } else {
        $id="";
    }
    $return_to='';
    if ($item==='public_content' && isset($_REQUEST['return_to']) && trim((string)$_REQUEST['return_to'])!=='') {
        $return_to=trim((string)$_REQUEST['return_to']);
    }

    $done=false;
    $formfields=participantform__load('draft');
    $allow_cat=$item;
    foreach ($formfields as $f) {
        if (preg_match("/(select_lang|radioline_lang|checkboxlist_lang)/",$f['type']) && $item==$f['mysql_column_name']) {
            $done=true;
            $header=participant__field_localized_text($f,'name_lang','name_lang');
            if (!$header) {
                $header=$f['mysql_column_name'];
            }
            $new_id='time';
            $inputform='line';
            $check_allow_content_shortcut=false;
            $allow_cat='pform_lang_field';
        }
    }

    if (!$id) {
        $allow=check_allow($allow_cat.'_edit','lang_item_main.php?item='.$item);
    } else {
        $allow=check_allow($allow_cat.'_edit','options_main.php');
    }
}

$content_shortcut_editable=true;
if ($proceed && $item==='public_content' && $id) {
    $content_shortcut_editable=false;
}

if ($proceed) {
    if (!$done) {
        switch ($item) {
            case 'experimentclass':
                if ($id) {
                    $header=lang('edit_experiment_class');
                } else {
                    $header=lang('add_experiment_class');
                }
                $new_id='time';
                $check_allow_content_shortcut=false;
                $inputform='line';
                break;
            case 'public_content':
                if ($id) {
                    $header=lang('edit_public_content');
                } else {
                    $header=lang('add_public_content');
                }
                $new_id='content_shortcut';
                $inputform='area';
                $check_allow_content_shortcut=true;
                break;
            case 'datetime_format':
                if ($id) {
                    $header=lang('edit_datetime_format');
                } else {
                    $header=lang('add_datetime_format');
                }
                $new_id='content_shortcut';
                $inputform='line';
                $check_allow_content_shortcut=true;
                break;
            case 'help':
                if ($id) {
                    $header=lang('edit_help');
                } else {
                    $header=lang('add_help');
                }
                $new_id='content_shortcut';
                $inputform='area';
                $check_allow_content_shortcut=true;
                break;
            case 'mail':
                if ($id) {
                    $header=lang('edit_default_mail');
                } else {
                    $header=lang('add_default_mail');
                }
                $new_id='content_shortcut';
                $inputform='area';
                $check_allow_content_shortcut=true;
                break;
            case 'laboratory':
                if ($id) {
                    $header=lang('edit_laboratory');
                } else {
                    $header=lang('create_new_laboratory');
                }
                $new_id='time';
                $inputform='area';
                $check_allow_content_shortcut=false;
                $extranote_content_shortcut=lang('lab_lists_are_ordered_by_this_name');
                $extranote_lang_field=lang('first_line_is_lab_name_rest_is_address');
                break;
            case 'payments_type':
                if ($id) {
                    $header=lang('edit_payment_type');
                } else {
                    $header=lang('add_payment_type');
                }
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
            case 'file_upload_category':
                if ($id) {
                    $header=lang('edit_upload_category');
                } else {
                    $header=lang('add_upload_category');
                }
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
            case 'events_category':
                if ($id) {
                    $header=lang('edit_event_category');
                } else {
                    $header=lang('add_event_category');
                }
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
            case 'emails_mailbox':
                if ($id) {
                    $header=lang('edit_email_mailbox');
                } else {
                    $header=lang('add_email_mailbox');
                }
                $new_id='time';
                $inputform='line';
                $check_allow_content_shortcut=false;
                break;
        }
    }

    if ($id) {
        $button_title=lang('change');
    } else {
        $button_title=lang('add');
    }

    // load languages
    $languages=get_languages();


    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        $continue=true;

        if ($new_id=='content_shortcut' && $content_shortcut_editable && !$_REQUEST['content_shortcut']) {
            message(lang('you_have_to_give_content_name'),'error');
            $continue=false;
        }

        foreach ($languages as $language) {
            if (trim($_REQUEST[$language])=="") {
                message(lang('missing_language').": ".$language,'error');
                $continue=false;
            } else {
                $_REQUEST[$language]=trim($_REQUEST[$language]);
            }
        }

        if ($continue) {
            $sitem=$_REQUEST;
            $sitem['content_type']=$item;

            if (!$id) {
                $new=true;
            } else {
                $new=false;
            }

            if ($new && $new_id=="time") {
                $sitem['content_name']=time();
            }
            if ($new_id=="content_shortcut") {
                if ($content_shortcut_editable) {
                    $sitem['content_name']=trim($_REQUEST['content_shortcut']);
                } else {
                    $current_item=orsee_db_load_array("lang",$id,"lang_id");
                    if (is_array($current_item) && isset($current_item['content_name'])) {
                        $sitem['content_name']=$current_item['content_name'];
                    }
                }
            }

            $allowed_fields=array('content_type','content_name');
            foreach ($languages as $language) {
                $allowed_fields[]=$language;
            }
            $form_fields=array_filter_allowed($sitem,$allowed_fields);

            if ($new) {
                $id=lang__insert_to_lang($form_fields);
                $done=(bool)$id;
            } else {
                $done=orsee_db_save_array($form_fields,"lang",$id,"lang_id");
            }

            if (!$new && $new_id=="time") {
                $sitem['content_name']=trim($_REQUEST['content_shortcut']);
            }

            if ($done) {
                log__admin($item."_edit","lang_id:".$sitem['content_type'].','.$sitem['content_name']);
                message(lang('changes_saved'));
                if ($new) {
                    redirect('admin/lang_item_main.php?&item='.$item);
                } else {
                    $redirect_url='admin/lang_item_edit.php?id='.$id.'&item='.$item;
                    if ($return_to!=='') {
                        $redirect_url.='&return_to='.urlencode($return_to);
                    }
                    redirect($redirect_url);
                }
            } else {
                message(lang('database_error'),'error');
                $redirect_url='admin/lang_item_edit.php?id='.$id.'&item='.$item;
                if ($return_to!=='') {
                    $redirect_url.='&return_to='.urlencode($return_to);
                }
                redirect($redirect_url);
            }
        } else {
            $titem=$_REQUEST;
            if ($new_id=="content_shortcut" && $content_shortcut_editable) {
                $titem['content_name']=$_REQUEST['content_shortcut'];
            }
        }
    }
}


if ($proceed) {
    if ($id) {
        $titem=orsee_db_load_array("lang",$id,"lang_id");
    } else {
        $titem=array('content_name'=>'');
    }

    $header_display=$header;
    if ($item==='public_content' && $id) {
        $menu_label='';
        $current_content_name=(isset($titem['content_name']) ? trim((string)$titem['content_name']) : '');
        $menu_areas=array('public','admin');
        foreach ($menu_areas as $menu_area_name) {
            $menu_config=html__menu_load_config($menu_area_name);
            if (!is_array($menu_config) || !isset($menu_config['items']) || !is_array($menu_config['items'])) {
                continue;
            }
            foreach ($menu_config['items'] as $menu_item) {
                if (!is_array($menu_item) || !isset($menu_item['content_name'])) {
                    continue;
                }
                if ($current_content_name==='' || trim((string)$menu_item['content_name'])!==$current_content_name) {
                    continue;
                }
                $menu_label=html__menu_text_from_lang_map((isset($menu_item['menu_term_lang']) ? $menu_item['menu_term_lang'] : array()),'');
                if ($menu_label==='') {
                    if (isset($menu_item['id']) && trim((string)$menu_item['id'])!=='') {
                        $menu_label=(string)$menu_item['id'];
                    } elseif (isset($titem['content_name']) && trim((string)$titem['content_name'])!=='') {
                        $menu_label=(string)$titem['content_name'];
                    } else {
                        $menu_label=(string)$id;
                    }
                }
                break 2;
            }
        }
        if ($menu_label==='') {
            if (isset($titem['content_name']) && trim((string)$titem['content_name'])!=='') {
                $menu_label=(string)$titem['content_name'];
            } else {
                $menu_label=(string)$id;
            }
        }
        $header_display=$header.': '.htmlspecialchars($menu_label);
    }

    show_message();

    echo '<form action="lang_item_edit.php" method="POST">
            <input type="hidden" name="id" value="'.$id.'">
            <input type="hidden" name="item" value="'.$item.'">
            <input type="hidden" name="return_to" value="'.htmlspecialchars($return_to).'">
            '.csrf__field().'
            <div class="orsee-panel">
                <div class="orsee-panel-title">
                    <div class="orsee-panel-title-main">'.$header_display.'</div>
                </div>
                <div class="orsee-form-shell">';

    if (!($new_id==='content_shortcut' && !$content_shortcut_editable)) {
        echo '          <div class="field">
                        <label class="label">';
        if ($new_id=='content_shortcut') {
            echo lang('content_name').':';
            if (!$check_allow_content_shortcut || check_allow($allow_cat.'_add')) {
                echo '<p class="help">'.lang('symbol_name_comment').'</p>';
                if (isset($extranote_content_shortcut) && $extranote_content_shortcut) {
                    echo '<p class="help">'.$extranote_content_shortcut.'</p>';
                }
            }
        } else {
            if (!$id) {
                echo lang('id');
            }
        }
        echo '              </label>
                        <div class="control">';
        if ($new_id=='content_shortcut') {
            if (!$check_allow_content_shortcut || check_allow($allow_cat.'_add')) {
                echo '<input class="input is-primary orsee-input orsee-input-text" type="text" name="content_shortcut" dir="ltr" maxlength="50" value="'.htmlspecialchars($titem['content_name']).'">';
            } else {
                echo '<span class="orsee-dense-id-tag">'.lang('id').': '.htmlspecialchars($titem['content_name']).'</span>'.
                    '<input type="hidden" name="content_shortcut" value="'.htmlspecialchars($titem['content_name']).'">';
            }
        } elseif ($id) {
            echo '<span class="orsee-dense-id-tag">'.lang('id').': '.htmlspecialchars($titem['content_name']).'</span>'.
                '<input type="hidden" name="content_shortcut" value="'.htmlspecialchars($titem['content_name']).'">';
        } else {
            echo '???';
        }
        echo '              </div>
                    </div>';
    }

    if ($item=='public_content') {
        echo '<div class="orsee-callout orsee-callout-notice orsee-message-box" style="margin-top:0.45rem;">
                <div class="orsee-message-box-body">
                    <b>Richtext quick reference</b><br>
                    larger heading: = Heading =<br>
                    smaller heading: == Heading ==<br>
                    (headings must be on their own line)<br>
                    italics: \'\'italics\'\'<br>
                    bold: \'\'\'bold\'\'\'<br>
                    underline: __underlined__<br>
                    link: [https://example.org Link-text]<br>
                    image: {{image:https://example.org/pic.jpg}}<br>
                    image with alt text: {{image:https://example.org/pic.jpg|Description}}<br>
                    image with width: {{image:https://example.org/pic.jpg|Description|40%}}<br>
                    centering: {{center:start}} ... {{center:end}}<br><br>

                    (nested) unordered list: *, **, ***, ****<br>
                    (nested) ordered list: #, ##, ###, ####<br>
                    table: | Col1 | Col2 |<br>
                    cell alignment: |: left-aligned |: centered :| right-aligned :|<br><br>
                    line break inside table cell: {{br}}<br><br>

                    Start new paragraph: Empty line<br>
                    vertical space: {{spacer:2}} adds a 2em space<br>
                    Start a new (centered) block: {{page-width:70%}}<br>
                    (on mobile all blocks are 100%)
                </div>
            </div>';
    }

    if ($new_id==='content_shortcut' && !$content_shortcut_editable) {
        echo '<input type="hidden" name="content_shortcut" value="'.htmlspecialchars($titem['content_name']).'">';
    }

    $lang_dirs=lang__is_rtl_all_langs();
    foreach ($languages as $language) {
        if (!isset($titem[$language])) {
            $titem[$language]="";
        }
        $field_dir=(isset($lang_dirs[$language]) && $lang_dirs[$language] ? 'rtl' : 'ltr');
        echo '      <div class="field">
                        <label class="label">'.$language.':';
        if (isset($extranote_lang_field) && $extranote_lang_field) {
            echo '<p class="help">'.$extranote_lang_field.'</p>';
        }
        echo '          </label>
                        <div class="control">';
        if ($inputform=='area') {
            echo '<textarea class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="'.$language.'" rows="20" wrap="virtual">'.stripslashes($titem[$language]).'</textarea>';
        } else {
            echo '<input class="input is-primary orsee-input orsee-input-text" dir="'.$field_dir.'" type="text" name="'.$language.'" maxlength="100" value="'.htmlspecialchars(stripslashes($titem[$language])).'">';
        }
        echo '          </div>
                    </div>';
    }

    echo '          <div class="field orsee-form-row-grid orsee-form-row-grid--3 orsee-form-actions">
                        <div class="orsee-form-row-col has-text-left">
                            '.button_back(($return_to!=='' ? $return_to : 'lang_item_main.php?item='.urlencode($item)), lang('back')).'
                        </div>
                        <div class="orsee-form-row-col has-text-centered">
                            <input class="button orsee-btn" name="edit" type="submit" value="'.$button_title.'">
                        </div>
                        <div class="orsee-form-row-col has-text-right">';
    if ($item!=='public_content' && $id && check_allow($allow_cat.'_delete')) {
        echo button_link('lang_item_delete.php?id='.urlencode($id).'&item='.urlencode($item).'&csrf_token='.urlencode(csrf__get_token()), lang('delete'), 'trash-o', '', '', 'orsee-btn--delete');
    }
    echo '              </div>
                    </div>
                </div>
            </div>
        </form>
        <br>';
}
include("footer.php");

?>
