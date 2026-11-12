<?php
// part of orsee. see orsee.org
ob_start();
$title="import_language";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['lang_id']) && $_REQUEST['lang_id']) {
        $lang_id=$_REQUEST['lang_id'];
    } else {
        $lang_id='';
    }
    $languages=get_languages();
    if (!$lang_id || !in_array($lang_id,$languages)) {
        redirect("admin/lang_main.php");
    }
}
if ($proceed) {
    $allow=check_allow('lang_lang_import','lang_lang_edit.php?elang='.$lang_id);
}

if ($proceed) {
    $tlang_name=load_language_symbol('lang_name',$lang_id);

    if (isset($_REQUEST['upload']) && $_REQUEST['upload']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    $tlang_name=load_language_symbol('lang_name',$lang_id);

    if (isset($_REQUEST['upload']) && $_REQUEST['upload']) {
        if (!isset($_REQUEST['action'])) {
            $_REQUEST['action']="";
        }
        switch ($_REQUEST['action']) {
            case 'upgrade': $do_upgrade=true;
                $do_update=false;
                break;
            case 'update': $do_upgrade=false;
                $do_update=true;
                break;
            case 'both':    $do_upgrade=true;
                $do_update=true;
                break;
            default:    $do_upgrade=false;
                $do_update=false;
        }

        $file=$_FILES['contents'];
        if ($file['size']>$settings['upload_max_size'] || $file['error']>0) {
            message(lang('error_not_uploaded'),'error');
            redirect("admin/lang_lang_import.php?lang_id=".$lang_id);
        } else {
            $upload=array();
            $handle = fopen($file['tmp_name'], "r");
            $upload_contents = fread($handle, filesize($file['tmp_name']));
            fclose($handle);

            if ($proceed) {
                // load old lang
                $old_lang=array();
                $query="SELECT content_type, content_name, ".$lang_id."
                        as content_value FROM ".table('lang');
                $result=or_query($query);
                while ($line = pdo_fetch_assoc($result)) {
                    if ($line['content_value']==null) {
                        $line['content_value']="";
                    }
                    $old_lang[$line['content_type']][$line['content_name']]=$line['content_value'];
                }


                $update=array();
                $upgrade=array();
                $parsed_items=array();
                $error=false;

                // try JSON export format first
                $json_data=json_decode($upload_contents,true);
                if (is_array($json_data) && isset($json_data['items']) && is_array($json_data['items'])) {
                    foreach ($json_data['items'] as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        if (!isset($row['content_type']) || !isset($row['content_name']) || !array_key_exists('content_value',$row)) {
                            continue;
                        }
                        $content_type=trim((string)$row['content_type']);
                        $content_name=trim((string)$row['content_name']);
                        if ($content_type==='' || $content_type==='default_text' || $content_name==='') {
                            continue;
                        }
                        $parsed_items[]=array(
                            $content_type,
                            $content_name,
                            (string)$row['content_value']
                        );
                    }
                } else {
                    // legacy ORL format: base64 + custom delimiters
                    $langtext=base64_decode($upload_contents);
                    $item_array=explode('--:orsee_line:--',$langtext);
                    foreach ($item_array as $item) {
                        if (!trim($item)) {
                            continue;
                        }
                        $tarr=explode('--:orsee_next:--',$item);
                        if (count($tarr)==3) {
                            $tarr[0]=trim((string)$tarr[0]);
                            $tarr[1]=trim((string)$tarr[1]);
                            if ($tarr[0]==='' || $tarr[0]==='default_text' || $tarr[1]==='') {
                                continue;
                            }
                            $parsed_items[]=$tarr;
                        } else {
                            $error=true;
                        }
                    }
                }

                if (count($parsed_items)<1 || $error) {
                    message(lang('error_uploaded_file_not_orsee_lang_file'),'error');
                    redirect("admin/lang_lang_import.php?lang_id=".$lang_id);
                }

                foreach ($parsed_items as $tarr) {
                    if (isset($old_lang[$tarr[0]][$tarr[1]]) && $old_lang[$tarr[0]][$tarr[1]]) {
                        $update[$tarr[0]][$tarr[1]]=$tarr[2];
                    } else {
                        $upgrade[$tarr[0]][$tarr[1]]=$tarr[2];
                    }
                }
            }

            if ($proceed) {
                $ignored=0;
                $errors=0;

                if ($do_update) {
                    $imported=array();
                    foreach ($update as $type=>$item) {
                        $count=0;
                        $pars=array();
                        foreach ($item as $name=>$value) {
                            if ($name=='lang' || $name=='lang_name' || $name=='lang_icon_base64' || $name=='lang_flag_iso2' || $name=='lang_is_rtl') {
                                continue;
                            } else {
                                $pars[]=array(':value'=>$value,':type'=>$type,':name'=>$name);
                            }
                        }
                        $query="UPDATE ".table('lang')."
                                SET ".$lang_id."= :value
                                WHERE content_type= :type
                                AND content_name= :name";
                        $done=or_query($query,$pars);
                        $imported[]=count($pars).' '.$type;
                    }
                    $impstring=implode(", ",$imported);
                    if ($impstring) {
                        message($impstring.' '.lang('xxx_language_items_updated').' '.$tlang_name.' ('.$lang_id.')');
                    }
                } else {
                    foreach ($update as $item) {
                        $ignored=$ignored+count($item);
                    }
                }

                // add new items
                if ($do_upgrade) {
                    $query="SELECT max(lang_id) as max_id FROM ".table('lang');
                    $line=orsee_query($query);
                    $new_id=$line['max_id'];

                    $created=array();
                    foreach ($upgrade as $type=>$item) {
                        $count=0;
                        $upars=array();
                        $ipars=array();
                        foreach ($item as $name=>$value) {
                            if ($name=='lang' || $name=='lang_name' || $name=='lang_icon_base64' || $name=='lang_flag_iso2' || $name=='lang_is_rtl') {
                                continue;
                            } else {
                                if (isset($old_lang[$type][$name])) {
                                    $upars[]=array(':value'=>$value,':type'=>$type,':name'=>$name);
                                } else {
                                    $new_id++;
                                    $ipars[]=array(':id'=>$new_id,':value'=>$value,':type'=>$type,':name'=>$name);
                                }
                            }
                        }
                        if (count($upars)>0) {
                            $query="UPDATE ".table('lang')."
                                    SET ".$lang_id."= :value
                                    WHERE content_type= :type
                                    AND content_name= :name";
                            $done=or_query($query,$upars);
                        }
                        if (count($ipars)>0) {
                            $query="INSERT INTO ".table('lang')."
                                        SET lang_id= :id,
                                        ".$lang_id."= :value,
                                        content_type= :type,
                                        content_name= :name";
                            $done=or_query($query,$ipars);
                        }
                        $created[]=(count($upars)+count($ipars)).' '.$type;
                    }
                    $crstring=implode(", ",$created);
                    if ($crstring) {
                        message($crstring.' '.lang('xxx_language_items_created').' '.$tlang_name.' ('.$lang_id.')');
                    }
                } else {
                    foreach ($upgrade as $item) {
                        $ignored=$ignored+count($item);
                    }
                }

                if ($ignored>0) {
                    message($ignored.' '.lang('xxx_language_items_in_file_ignored'),'warning');
                }
                message(lang('please_check_language_symbols').' '.$tlang_name.' ('.$lang_id.')');
                redirect("admin/lang_edit.php?el=".$lang_id);
            }
        }
    }
}

if ($proceed) {
    //form for uploading file

    show_message();

    echo '<div class="orsee-panel">
            <div class="orsee-panel-title"><div>'.lang('import_language').' '.$tlang_name.' ('.$lang_id.')</div></div>
            <div class="orsee-form-shell">
                <form method="post" enctype="multipart/form-data" action="lang_lang_import.php">
                    <input type="hidden" name="lang_id" value="'.$lang_id.'">
                    '.csrf__field().'
                    <div class="field">
                        <div class="control"><strong>Language symbols, default email texts, and default texts.<br><br>Do you want to</strong></div>
                    </div>

                    <div class="orsee-table" style="width: 100%; max-width: 100%;">
                        <div class="orsee-table-row">
                            <div class="orsee-table-cell" data-label="'.lang('action').'" style="white-space: nowrap; vertical-align: top;">
                                <label class="radio">
                                    <input type="radio" name="action" value="update" checked>
                                    update this language
                                </label>
                            </div>
                            <div class="orsee-table-cell">
                                This means that only language symbols already defined in the system
                                will be imported. Existing terms will be overwritten. Use this to
                                install a new language on this system.
                            </div>
                        </div>
                        <div class="orsee-table-row is-alt">
                            <div class="orsee-table-cell" data-label="'.lang('action').'" style="white-space: nowrap; vertical-align: top;">
                                <label class="radio">
                                    <input type="radio" name="action" value="upgrade">
                                    upgrade this language
                                </label>
                            </div>
                            <div class="orsee-table-cell">
                                Symbols not existing or empty on your system will be installed.
                                Use this when you just have upgraded to a new version of ORSEE and
                                want to install the new symbols needed by the new version.
                            </div>
                        </div>
                        <div class="orsee-table-row">
                            <div class="orsee-table-cell" data-label="'.lang('action').'" style="white-space: nowrap; vertical-align: top;">
                                <label class="radio">
                                    <input type="radio" name="action" value="both">
                                    both at once
                                </label>
                            </div>
                            <div class="orsee-table-cell">
                                This will update all your already defined language symbols with the
                                ones found in this file, and will also add all language symbols that
                                exist in this file but do not exist on your system. Any symbols that
                                exist on your system but are not found in this file will be kept as they are.
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">'.lang('file').':</label>
                        <div class="control">
                            <input class="input is-primary orsee-input orsee-input-text" name="contents" type="file" size="30" accept="*/*">
                        </div>
                    </div>

                    <div class="orsee-options-actions-center">
                        <input class="button orsee-btn" type="submit" name="upload" value="'.lang('upload').'">
                    </div>
                </form>
                <div class="orsee-options-actions">'.button_back('lang_lang_edit.php?elang='.$lang_id).'</div>
            </div>
        </div>';
}
include("footer.php");

?>
