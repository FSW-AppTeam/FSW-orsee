<?php
// part of orsee. see orsee.org

echo '<BR><BR><BR>';
if ($settings['support_mail']) {
    echo '<div class="orsee-public-supportline has-text-centered">';
    echo '<p>';
    echo lang('for_questions_contact_xxx');
    echo ' ';
    helpers__scramblemail($settings['support_mail']);
    echo $settings['support_mail'];
    echo '</A>';
    echo '.</p><br><br><br></div>';
}

debug_output();

html__show_style_footer('public');
html__footer();
?>
