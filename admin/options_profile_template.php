<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="participant_profile_form_template_legacy";
$js_modules=array('switchy','intltelinput');
include("header.php");

if ($proceed) {
    $allow=check_allow('pform_templates_edit','options_main.php');
}

if ($proceed) {
    if (!isset($_REQUEST['subpool_id'])) {
        $subpool_id=1;
    } else {
        $subpool_id=$_REQUEST['subpool_id'];
    }
    $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
    if (!$subpool['subpool_id']) {
        $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    }
}

if ($proceed) {
    echo '<div class="orsee-panel">
            <div class="orsee-panel-title">
                <div>'.lang('participant_profile_form_template_legacy').'</div>
            </div>
            <div class="orsee-form-shell">
                <form action="options_profile_template.php" method="GET">
                    <div class="field orsee-form-row-grid orsee-form-row-grid--2" style="align-items: end;">
                        <div class="orsee-form-row-col">
                            <label class="label">'.lang('display_preview_for_subjectpool').'</label>
                            <div class="control">
                                '.subpools__select_field('subpool_id',$subpool_id).'
                            </div>
                        </div>
                        <div class="orsee-form-row-col has-text-right">
                            <input class="button orsee-btn" id="change_subpool" name="change_subpool" type="submit" value="'.lang('apply').'">
                        </div>
                    </div>
                </form>
            </div>';

    echo '<div class="orsee-table orsee-table-mobile" style="width: 100%; max-width: 100%;">
            <div class="orsee-table-row orsee-table-head">
                <div class="orsee-table-cell">'.lang('name').'</div>
                <div class="orsee-table-cell">'.lang('currently_active_form_template').'</div>
                <div class="orsee-table-cell">'.lang('current_template_draft').'</div>
                <div class="orsee-table-cell">'.lang('action').'</div>
            </div>';

    $edit=array();
    if (isset($subpool_id)) {
        $edit['subpool_id']=$subpool_id;
    }

    $query="SELECT *
            FROM ".table('objects')."
            WHERE item_type='profile_form_template'
            ORDER BY item_id ";
    $result=or_query($query);
    while ($t=pdo_fetch_assoc($result)) {
        $details=db_string_to_property_array($t['item_details']);
        if ($t['item_name']=='profile_form_public') {
            ob_start();
            participant__show_inner_form($edit,array(),'profile_form_public_admin_edit','current_template');
            $preview_current=ob_get_clean();

            $preview_draft='';
            if ($details['current_template']!=$details['current_draft']) {
                ob_start();
                participant__show_inner_form($edit,array(),'profile_form_public_admin_edit','current_draft');
                $preview_draft=ob_get_clean();
            }

            echo '<div class="orsee-table-row">
                    <div class="orsee-table-cell" data-label="'.lang('name').'">'.lang('profile_form_public').'</div>
                    <div class="orsee-table-cell" data-label="'.lang('currently_active_form_template').'">'.$preview_current.'</div>
                    <div class="orsee-table-cell" data-label="'.lang('current_template_draft').'">'.$preview_draft.'</div>
                    <div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">'.
                        button_link('options_profile_template_edit.php?item_name='.$t['item_name'],lang('edit'),'pencil-square-o')
                    .'</div>
                </div>';
        } elseif ($t['item_name']=='profile_form_admin_part') {
            $preview_current=participant__get_inner_admin_form($edit,array(),'current_template');
            $preview_draft='';
            if ($details['current_template']!=$details['current_draft']) {
                $preview_draft=participant__get_inner_admin_form($edit,array(),'current_draft');
            }

            echo '<div class="orsee-table-row is-alt">
                    <div class="orsee-table-cell" data-label="'.lang('name').'">'.lang('profile_form_admin_part').'</div>
                    <div class="orsee-table-cell" data-label="'.lang('currently_active_form_template').'">'.$preview_current.'</div>
                    <div class="orsee-table-cell" data-label="'.lang('current_template_draft').'">'.$preview_draft.'</div>
                    <div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">'.
                        button_link('options_profile_template_edit.php?item_name='.$t['item_name'],lang('edit'),'pencil-square-o')
                    .'</div>
                </div>';
        }
    }

    echo '</div>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
