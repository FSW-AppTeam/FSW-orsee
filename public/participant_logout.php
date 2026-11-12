<?php
// part of orsee. see orsee.org
ob_start();
$suppress_html_header=true;
include("header.php");

if ($proceed) {
    log__participant("logout",$participant['participant_id']);
    participant__logout();

    redirect("public/participant_login.php?logout=true");
}
include("footer.php");

?>
