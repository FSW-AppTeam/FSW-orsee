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
$js_modules=array('listtool');
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['item'])) {
        $sent_item=$_REQUEST['item'];
    } else {
        $sent_item="";
    }

    $done=false;
    $formfields=participantform__load('draft');
    $allow_cat=$sent_item;
    foreach ($formfields as $f) {
        if (preg_match("/(select_lang|radioline_lang|checkboxlist_lang)/",$f['type']) && $sent_item==$f['mysql_column_name']) {
            $done=true;
            $item=$sent_item;
            $header=participant__field_localized_text($f,'name_lang','name_lang');
            if (!$header) {
                $header=$f['mysql_column_name'];
            }
            $where="";
            $order=" order_number ";
            $allow_order=true;
            $show_part_stats=true;
            $show_id=true;
            $allow_cat='pform_lang_field';
        }
    }

    $allow=check_allow($allow_cat.'_edit','options_main.php');
}

if ($proceed) {
    $id_nowrap=false;

    if (!$done) {
        switch ($sent_item) {
            case 'public_content':
                $item=$sent_item;
                $header=lang('public_content');
                $where="";
                $order=" content_name ";
                $allow_order=false;
                $show_part_stats=false;
                $show_id=true;
                $id_nowrap=true;
                break;
            case 'datetime_format':
                $item=$sent_item;
                $header=lang('datetime_format');
                $where="";
                $order=" content_name ";
                $allow_order=false;
                $show_part_stats=false;
                $show_id=true;
                $id_nowrap=true;
                break;
                //          case 'help':
                //              $item=$sent_item;
                //              $header=lang('help');
                //              $where="";
                //              $order=" content_name ";
                //              $show_part_stats=false;
                //              $show_id=true;
                //              $chnl2br=true;
                //              break;
            case 'mail':
                $item=$sent_item;
                $header=lang('default_mails');
                $where="";
                $order=" content_name ";
                $allow_order=false;
                $show_part_stats=false;
                $show_id=true;
                $chnl2br=true;
                $id_nowrap=true;
                break;
            case 'laboratory':
                $item=$sent_item;
                $header=lang('laboratories');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                $chnl2br=true;
                break;
            case 'experimentclass':
                $item=$sent_item;
                $header=lang('experiment_classes');
                $where="";
                $order=lang('lang');
                $allow_order=false;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'payments_type':
                $item=$sent_item;
                $header=lang('payment_types');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'file_upload_category':
                $item=$sent_item;
                $header=lang('upload_file_categories');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'events_category':
                $item=$sent_item;
                $header=lang('event_categories');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
            case 'emails_mailbox':
                $item=$sent_item;
                $header=lang('email_mailboxes');
                $where="";
                $order=" order_number ";
                $allow_order=true;
                $show_part_stats=false;
                $show_id=false;
                break;
        }
    }

    //var_dump($_REQUEST);
    if ($allow_order && isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if ($allow_order && isset($_REQUEST['save_order']) && $_REQUEST['save_order']) {
        if (isset($_REQUEST['langitem_order']) && is_array($_REQUEST['langitem_order']) && count($_REQUEST['langitem_order'])>0) {
            $done=language__save_item_order($item,$_REQUEST['langitem_order']);
            message(lang('new_order_saved'));
            redirect('admin/lang_item_main.php?item='.urlencode($item));
        }
    }
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title">';
    echo '<div class="orsee-panel-title-main">'.$header.'</div>';
    echo '<div class="orsee-panel-actions">';
    if (check_allow($allow_cat.'_add')) {
        echo button_link('lang_item_edit.php?item='.urlencode($item).'&addit=true',
            lang('create_new'),'plus-circle');
    }
    echo '</div>';
    echo '</div>';


    // load languages
    $languages=get_languages();

    // $item already sanitized above
    if ($show_part_stats) {
        $num_p=array();
        $query="SELECT ".$item." as type_p,
            count(*) as num_p
            FROM ".table('participants')."
            GROUP BY ".$item;
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $num_p[$line['type_p']]=$line['num_p'];
        }
    }


    $query="SELECT *
            FROM ".table('lang')."
            WHERE content_type='".$item."'
            ".$where."
            ORDER BY ".$order;
    $result=or_query($query);

    $rows=array();
    while ($line=pdo_fetch_assoc($result)) {
        $row=array();
        if ($show_id) {
            $row['id']=$line['content_name'];
        }
        $row['languages']=array();
        foreach ($languages as $language) {
            if (isset($chnl2br) && $chnl2br) {
                $row['languages'][$language]=nl2br(stripslashes($line[$language]));
            } else {
                $row['languages'][$language]=stripslashes($line[$language]);
            }
        }
        if ($show_part_stats) {
            if (isset($num_p[$line['content_name']])) {
                $np=$num_p[$line['content_name']];
            } else {
                $np=0;
            }
            $row['participants']=$np;
        }
        $row['action']=button_link('lang_item_edit.php?item='.$item.'&id='.$line['lang_id'],lang('edit'),'pencil-square-o');

        $row_text='';
        if ($show_id) {
            $row_text.='<div class="orsee-listcell">'.$row['id'].'</div>';
        }
        foreach ($languages as $language) {
            $row_text.='<div class="orsee-listcell">'.$row['languages'][$language].'</div>';
        }
        if ($show_part_stats) {
            $row_text.='<div class="orsee-listcell">'.$row['participants'].'</div>';
        }
        $row_text.='<div class="orsee-listcell">'.$row['action'].'</div>';

        $rowelem=array('content_name'=>$line['content_name'],
                        'text'=>$row_text,
                        'data'=>$row);
        $rows[]=$rowelem;
    }

    $thc=0;
    if ($show_id) {
        $thc++;
    }
    $thc+=count($languages);
    if ($show_part_stats) {
        $thc++;
    }
    $thc++;


    if (count($rows)==0) {
        if ($allow_order) {
            echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
            echo '<div class="orsee-table-row orsee-table-head">';
            if ($show_id) {
                echo '<div class="orsee-table-cell">'.lang('id').'</div>';
            }
            foreach ($languages as $language) {
                echo '<div class="orsee-table-cell">'.$language.'</div>';
            }
            if ($show_part_stats) {
                echo '<div class="orsee-table-cell">'.lang('participants').'</div>';
            }
            echo '<div class="orsee-table-cell">'.lang('action').'</div>';
            echo '</div>';
            echo '<div class="orsee-table-row">';
            echo '<div class="orsee-table-cell" style="grid-column: 1 / -1;">'.lang('no_items_found').'</div>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
            echo '<div class="orsee-table-row orsee-table-head">';
            if ($show_id) {
                echo '<div class="orsee-table-cell">'.lang('id').'</div>';
            }
            foreach ($languages as $language) {
                echo '<div class="orsee-table-cell">'.$language.'</div>';
            }
            if ($show_part_stats) {
                echo '<div class="orsee-table-cell">'.lang('participants').'</div>';
            }
            echo '<div class="orsee-table-cell">'.lang('action').'</div>';
            echo '</div>';
            echo '<div class="orsee-table-row">';
            echo '<div class="orsee-table-cell" style="grid-column: 1 / -1;">'.lang('no_items_found').'</div>';
            echo '</div>';
            echo '</div>';
        }
    } elseif ($allow_order) {
        $listrows=array();
        $i=0;
        foreach ($rows as $k=>$row) {
            $i++;
            $listrows[$row['content_name']]=array(
                    'display_text' => $row['content_name'],
                    'on_list' => true,
                    'allow_remove' => false,
                    'allow_drag' => true,
                    'fixed_position' => $i,
                    'cols'=> $row['text']
                    );
        }
        echo '<form action="" method="POST">';
        echo csrf__field();
        echo formhelpers__orderlist("langitem_list", "langitem_order", $listrows, true, lang('add'), "");
        echo '<div class="orsee-options-actions-center"><input class="button" name="save_order" type="submit" value="'.lang('save_order').'"></div>';
        echo '</form>';
    } else {
        $id_cell_style=$id_nowrap ? ' style="white-space: nowrap; vertical-align: top;"' : ' style="vertical-align: top;"';
        echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
        echo '<div class="orsee-table-row orsee-table-head">';
        if ($show_id) {
            echo '<div class="orsee-table-cell">'.lang('id').'</div>';
        }
        foreach ($languages as $language) {
            echo '<div class="orsee-table-cell">'.$language.'</div>';
        }
        if ($show_part_stats) {
            echo '<div class="orsee-table-cell">'.lang('participants').'</div>';
        }
        echo '<div class="orsee-table-cell">'.lang('action').'</div>';
        echo '</div>';

        $shade=false;
        foreach ($rows as $k=>$row) {
            $row_class='orsee-table-row';
            if ($shade) {
                $row_class.=' is-alt';
                $shade=false;
            } else {
                $shade=true;
            }
            echo '<div class="'.$row_class.'">';
            if ($show_id) {
                echo '<div class="orsee-table-cell" data-label="'.lang('id').'"'.$id_cell_style.'>'.$row['data']['id'].'</div>';
            }
            foreach ($languages as $language) {
                echo '<div class="orsee-table-cell" data-label="'.$language.'">'.$row['data']['languages'][$language].'</div>';
            }
            if ($show_part_stats) {
                echo '<div class="orsee-table-cell" data-label="'.lang('participants').'">'.$row['data']['participants'].'</div>';
            }
            echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">'.$row['data']['action'].'</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';

    echo '</div>';
}
include("footer.php");

?>
