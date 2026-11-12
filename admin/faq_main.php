<?php
// part of orsee. see orsee.org
ob_start();
$title="faq_long";
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('faq_edit','options_main.php');
}

if ($proceed) {
    echo '<div class="orsee-panel">';
    echo '<div class="orsee-options-actions-end">';
    if (check_allow('faq_add')) {
        echo button_link('faq_edit.php?addit=true',lang('create_new'),'plus-circle');
    }
    echo '</div>';

    // load languages
    $languages=get_languages();
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    foreach ($languages as $language) {
        echo '<div class="orsee-table-cell">'.$language.'</div>';
    }
    echo '<div class="orsee-table-cell">'.lang('this_faq_answered_questions_of_xxx').'</div>';
    echo '<div class="orsee-table-cell">'.lang('action').'</div>';
    echo '</div>';

    $query="SELECT *
            FROM ".table('faqs').", ".table('lang')."
            WHERE content_type='faq_question'
            AND ".table('faqs').".faq_id=".table('lang').".content_name
            ORDER BY ".lang('lang');
    $result=or_query($query);

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        echo '<div class="'.$row_class.'">';
        foreach ($languages as $language) {
            echo '<div class="orsee-table-cell" data-label="'.$language.'">'.stripslashes($line[$language]).'</div>';
        }
        echo '<div class="orsee-table-cell" data-label="'.lang('this_faq_answered_questions_of_xxx').'">'.$line['evaluation'].' '.lang('persons').'</div>';
        echo '<div class="orsee-table-cell orsee-table-action" data-label="'.lang('action').'">'.button_link('faq_edit.php?faq_id='.$line['faq_id'],lang('edit'),'pencil-square-o').'</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
