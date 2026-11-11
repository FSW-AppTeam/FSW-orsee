<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="disabled";
$navigation_disabled=true;
include("header.php");

if ($proceed) {
    echo '<div id="orsee-public-mobile-screen">';
    echo '  <div class="orsee-public-faq-panel">';
    echo '      <div class="orsee-panel">';
    echo '          <div class="orsee-richtext">'.content__get_content("error_temporary_disabled").'</div>';
    echo '      </div>';
    echo '  </div>';
    echo '</div>';
}
include("footer.php");

?>
