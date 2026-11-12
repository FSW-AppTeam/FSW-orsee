<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="options";
$title="edit_language";
include("header.php");

if ($proceed) {
    $allow=check_allow('lang_symbol_edit','lang_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel">';

    // load languages
    $languages=get_languages();

    if (isset($_REQUEST['el']) && $_REQUEST['el'] && in_array($_REQUEST['el'],$languages)) {
        $el=$_REQUEST['el'];
    } else {
        $el=$settings['admin_standard_language'];
    }

    if (isset($_REQUEST['search']) && $_REQUEST['search']) {
        $search=$_REQUEST['search'];
    } else {
        $search='';
    }

    if (isset($_REQUEST['letter']) && $_REQUEST['letter']) {
        $letter=$_REQUEST['letter'];
    } else {
        $letter='a';
    }

    if (isset($_REQUEST['alter_lang']) && $_REQUEST['alter_lang'] && isset($_REQUEST['symbols']) && is_array($_REQUEST['symbols'])) {
        if (!csrf__validate_request_message()) {
            redirect('admin/lang_edit.php?el='.$el.'&letter='.$letter.'&search='.$search);
        }
        $pars=array();
        foreach ($_REQUEST['symbols'] as $symbol => $content) {
            $pars[]=array(':content'=>trim($content),':symbol'=>$symbol);
        }
        $query="UPDATE ".table('lang')."
                SET ".$el."= :content
                WHERE content_name= :symbol
                AND content_type='lang'";
        $done=or_query($query,$pars);
        message(lang('changes_saved'));
        log__admin("language_edit_symbols","language:".$edlang);
        redirect('admin/lang_edit.php?el='.$el.'&letter='.$letter.'&search='.$search);
    }
}

if ($proceed) {
    if ($search) {
        $letter="";
        $lpars=array(':search1'=>'%'.$search.'%',
                    ':search2'=>'%'.$search.'%',
                    ':search3'=>'%'.$search.'%');
        $lquery="select * from ".table('lang')."
                where content_type='lang'
                and (content_name LIKE :search1
                or ".lang('lang')." LIKE :search2
                or ".$el." LIKE :search3)
                AND content_name NOT IN ('lang','lang_name','lang_icon_base64','lang_flag_iso2','lang_is_rtl')
                order by content_name";
    } else {
        $search="";
        $lpars=array(':letter'=>$letter);
        $lquery="select * from ".table('lang')."
                where content_type='lang'
                and left(content_name,1)= :letter
                AND content_name NOT IN ('lang','lang_name','lang_icon_base64','lang_flag_iso2','lang_is_rtl')
                order by content_name";
    }

    echo '<div style="text-align: center; margin-bottom: 0.8rem;">';
    echo '<FORM action="lang_edit.php" style="display: inline-flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; justify-content: center;">';
    echo csrf__field();
    echo '<INPUT type="hidden" name="el" value="'.$el.'">';
    echo '<INPUT type="hidden" name="letter" value="'.$letter.'">';
    echo '<input class="input is-primary orsee-input" type="text" name="search" maxlength="200" value="'.$search.'">';
    echo '<INPUT class="button orsee-btn" type="submit" name="dosearch" value="'.lang('search').'">';
    echo '</FORM>';
    echo '</div>';


    $query="select lower(left(content_name,1)) as letter,
            count(lang_id) as number
            from ".table('lang')."
            where content_type='lang' GROUP BY letter ORDER BY letter";
    $result=or_query($query);
    echo '<div style="text-align: center; margin-bottom: 0.6rem;">';
    while ($line=pdo_fetch_assoc($result)) {
        if ($line['letter']!=$letter) {
            echo '<A HREF="lang_edit.php?el='.$el.'&letter='.$line['letter'].'">'.$line['letter'].'</A>&nbsp; ';
        } else {
            echo $letter.'&nbsp; ';
        }
    }
    echo '</div>';

    $result=or_query($lquery,$lpars);
    $number=pdo_num_rows($result);
    $field_dir=(lang__is_rtl($el) ? 'rtl' : 'ltr');

    echo '<div style="text-align: center; margin-bottom: 0.8rem;">'.lang('symbols').': '.$number.'</div>

        <FORM action="lang_edit.php" method=post>
        <INPUT type=hidden name="el" value="'.$el.'">
        <INPUT type=hidden name="letter" value="'.$letter.'">
        <INPUT type=hidden name="search" value="'.$search.'">
        '.csrf__field().'
        <div class="orsee-options-actions-center" style="margin-bottom: 0.42rem;">
            <INPUT class="button orsee-btn" type=submit name="alter_lang" value="'.lang('change').'">
        </div>
        <div class="orsee-table orsee-table-cells-compact orsee-table-tablet-2cols orsee-table-mobile">
            <div class="orsee-table-row orsee-table-head">
                <div class="orsee-table-cell"><B>'.lang('symbol').'</B></div>
                <div class="orsee-table-cell"><B>'.lang('lang').'</B></div>
                <div class="orsee-table-cell"><B>'.$el.'</B></div>
                <div class="orsee-table-cell">'.lang('action').'</div>
            </div>';

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        echo '<div class="'.$row_class.'">
                <div class="orsee-table-cell" data-label="'.lang('symbol').'" style="white-space: nowrap; vertical-align: top;">'.$line['content_name'].'</div>
                <div class="orsee-table-cell" data-label="'.lang('lang').'" style="vertical-align: top;">'.$lang[$line['content_name']].'</div>
                <div class="orsee-table-cell" data-label="'.$el.'" style="vertical-align: top;">
                    <textarea class="textarea is-primary orsee-textarea orsee-textarea-compact" dir="'.$field_dir.'" style="min-width: 26rem;" rows="3" name="symbols['.$line['content_name'].']">'.
                        trim(stripslashes($line[$el])).'</textarea>
                </div>
                <div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'" style="vertical-align: top;">'.
                    button_link('lang_symbol_edit.php?lang_id='.$line['lang_id'],lang('edit'),'pencil-square-o','','','orsee-btn-compact')
                .'</div>
            </div>
            ';
    }

    echo '      </div>
        <div class="orsee-options-actions-center" style="margin-top: 0.42rem;">
            <INPUT class="button orsee-btn" type=submit name=alter_lang value="'.lang('change').'">
        </div>
        </FORM>';

    echo '<div class="orsee-options-actions-center" style="margin-top: 0.84rem;">'.button_link('lang_symbol_edit.php?go=true',
        lang('add_symbol'),'plus-circle').'</div>';

    echo '<div class="orsee-options-actions">'.button_back('lang_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
