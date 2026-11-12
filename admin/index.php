<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="admin_mainpage";
$title="welcome";
include("header.php");

if ($proceed) {
    $content_name=(isset($GLOBALS['admin__menu_page_item']['content_name']) ? (string)$GLOBALS['admin__menu_page_item']['content_name'] : 'admin_mainpage');
    show_message();
    $content_html=content__get_content($content_name);
    echo '<div class="orsee-panel">';
    echo '  <div class="orsee-richtext">'.$content_html.'</div>';
    echo '</div>';
}
include("footer.php");

?>
