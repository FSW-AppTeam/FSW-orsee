<?php
// part of orsee. see orsee.org
ob_start();
if (isset($_REQUEST['export']) && $_REQUEST['export']) {
    include("nonoutputheader.php");
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
        $allow=check_allow('lang_lang_export','lang_lang_edit.php?elang='.$lang_id);
    }
    if ($proceed) {
        $query="SELECT * FROM ".table('lang')."
                WHERE content_type IN ('lang','mail','datetime_format')
                AND TRIM(content_type)<>''
                AND TRIM(content_name)<>''
                ORDER by lang_id";
        $result=or_query($query);
        $items=array();
        while ($line=pdo_fetch_assoc($result)) {
            $items[]=array(
                'content_type'=>(string)$line['content_type'],
                'content_name'=>(string)$line['content_name'],
                'content_value'=>(string)$line[$lang_id]
            );
        }
        $payload=array(
            'format'=>'orsee_language_export_json',
            'version'=>1,
            'language'=>$lang_id,
            'content_types'=>array('lang','mail','datetime_format'),
            'items'=>$items
        );
        $file=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        $mime_type="application/json; charset=UTF-8";
        $filename='orsee_'.$lang_id.'.json';
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: ".$mime_type);
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Description: File Transfer");
        echo $file;
    }
} else {
    $menu__area="options";
    $title="export_language";
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
        $allow=check_allow('lang_lang_export','lang_lang_edit.php?elang='.$lang_id);
    }
    if ($proceed) {
        $tlang_name=load_language_symbol('lang_name',$lang_id);

        echo '<div class="orsee-panel">
                <div class="orsee-panel-title"><div>'.lang('export_language').' '.$tlang_name.' ('.$lang_id.')</div></div>
                <div class="orsee-form-shell">
                    <div class="field">
                        <div class="control has-text-centered">'.lang('language_export_explanation').'</div>
                    </div>
                    <div class="orsee-options-actions-center">
                        '.button_link('lang_lang_export.php?lang_id='.$lang_id.'&export=true','orsee_'.$lang_id.'.json','download').'
                    </div>
                    <div class="orsee-options-actions">'.button_back('lang_lang_edit.php?elang='.$lang_id).'</div>
                </div>
            </div>';
    }
    include("footer.php");
}
?>
