<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
if (isset($_REQUEST['item'])) {
    $item=$_REQUEST['item'];
} else {
    $item='';
}
$title="";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['item'])) {
        $item=$_REQUEST['item'];
    } else {
        $item="";
    }
    if (isset($_REQUEST['id'])) {
        $id=$_REQUEST['id'];
    } else {
        $id="";
    }
    if (!$id || !$item) {
        redirect("admin/");
    }
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        } else {
            redirect('admin/lang_item_edit.php?item='.$item.'&id='.$id);
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

    $titem=orsee_db_load_array("lang",$id,"lang_id");

    $done=false;
    $formfields=participantform__load('draft');
    $allow_cat=$item;
    foreach ($formfields as $f) {
        if (preg_match("/(select_lang|radioline_lang)/",$f['type']) && $item==$f['mysql_column_name']) {
            $done=true;
            $header=participant__field_localized_text($f,'name_lang','name_lang');
            if (!$header) {
                $header=$f['mysql_column_name'];
            }
            $headervar=lang('lang');
            $reset_part_field=$f['mysql_column_name'];
            $deletion_message=lang('symbol_deleted');
            $allow_cat='pform_lang_field';
            break;
        }
    }
    $allow=check_allow($allow_cat.'_delete','lang_item_edit.php?id='.$id.'&item='.$item);
}

if ($proceed) {
    switch ($item) {
        case 'experimentclass':
            $header=lang('delete_experiment_class');
            $headervar=lang('lang');
            $reset_part_field="";
            $deletion_message=lang('experiment_class_deleted');
            break;
        case 'public_content':
            $header=lang('delete_public_content');
            $headervar="content_name";
            $reset_part_field="";
            $deletion_message=lang('public_content_deleted');
            break;
        case 'datetime_format':
            $header=lang('delete_datetime_format');
            $headervar="content_name";
            $reset_part_field="";
            $deletion_message=lang('datetime_format_deleted');
            break;
            //      case 'help':
            //          $header=lang('delete_help');
            //          $headervar="content_name";
            //          $reset_part_field="";
            //          $deletion_message=lang('help_deleted');
            //          break;
        case 'mail':
            $header=lang('delete_default_mail');
            $headervar="content_name";
            $reset_part_field="";
            $deletion_message=lang('default_mail_deleted');
            break;
        case 'laboratory':
            $header=lang('delete_laboratory');
            $headervar="content_name";
            $reset_part_field="";
            $deletion_message=lang('laboratory_deleted');
            break;
        case 'payments_type':
            $header=lang('delete_payment_type');
            $headervar=lang('lang');
            $reset_part_field="";
            $deletion_message=lang('payment_type_deleted');
            break;
        case 'file_upload_category':
            $header=lang('delete_upload_category');
            $headervar=lang('lang');
            $reset_part_field="";
            $deletion_message=lang('upload_category_deleted');
            break;
        case 'events_category':
            $header=lang('delete_event_category');
            $headervar=lang('lang');
            $reset_part_field="";
            $deletion_message=lang('event_category_deleted');
            break;
        case 'emails_mailbox':
            $header=lang('delete_email_mailbox');
            $headervar=lang('lang');
            $reset_part_field="";
            $deletion_message=lang('email_mailbox_deleted');
            break;
    }

    echo '<center>';

    if ($reallydelete) {
        $pars=array(':id'=>$id);
        $query="DELETE FROM ".table('lang')."
                WHERE lang_id= :id";
        $result=or_query($query,$pars);

        // there should be a miore sophisticarted way of doing this
        if ($reset_part_field) {
            $pars=array(':content_name'=>$titem['content_name']);
            $query="UPDATE ".table('participants')."
                    SET ".$reset_part_field."='0'
                    WHERE ".$reset_part_field."= :content_name";
            $result=or_query($query,$pars);
        }
        message($deletion_message);
        log__admin($item."_delete","lang_id:".$titem['content_type'].','.$titem['content_name']);
        redirect('admin/lang_item_main.php?item='.$item);
    }
}


if ($proceed) {
    // form
    echo '<div class="orsee-panel orsee-form-shell">
                <div class="orsee-panel-title">'.$header.'</div>
                <div class="orsee-content">
                    <div class="orsee-callout orsee-message-box orsee-callout-warning">'.lang('do_you_really_want_to_delete').'</div>
                    <div class="field">
                        <label class="label">'.lang('id').'</label>
                        <div><span class="orsee-dense-id-tag">'.htmlspecialchars($titem['lang_id']).'</span></div>
                    </div>
                    <div class="field">
                        <label class="label">'.lang('symbol').'</label>
                        <div>'.htmlspecialchars($titem['content_name']).'</div>
                    </div>
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: center;">
                        <div class="orsee-form-row-col">
                            '.button_link(
        'lang_item_delete.php?id='.urlencode($id).'&item='.urlencode($item).'&reallydelete=true&csrf_token='.urlencode(csrf__get_token()),
        lang('yes_delete'),
        'check-square',
        '',
        '',
        'orsee-btn--delete'
    ).'
                        </div>
                        <div class="orsee-form-row-col has-text-right">
                            '.button_link(
        'lang_item_delete.php?id='.urlencode($id).'&item='.urlencode($item).'&betternot=true&csrf_token='.urlencode(csrf__get_token()),
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
