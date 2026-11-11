<?php
// part of orsee. see orsee.org
ob_start();
include("nonoutputheader.php");

if (isset($_REQUEST['displayfrom']) && $_REQUEST['displayfrom']) {
    $displayfrom= (int) $_REQUEST['displayfrom'];
} else {
    $displayfrom=time();
}
if (isset($_REQUEST['wholeyear']) && $_REQUEST['wholeyear']) {
    $wholeyear=true;
} else {
    $wholeyear=false;
}
$laboratory_id=false;
$labs=laboratories__get_laboratories();
if (isset($_REQUEST['laboratory_id']) && $_REQUEST['laboratory_id'] && isset($labs[$_REQUEST['laboratory_id']])) {
    $laboratory_id=$_REQUEST['laboratory_id'];
}
$experimenter_id=false;
$experimenters=experiment__load_experimenters();
if (isset($_REQUEST['experimenter_id']) && $_REQUEST['experimenter_id'] && isset($experimenters[$_REQUEST['experimenter_id']])) {
    $experimenter_id=$_REQUEST['experimenter_id'];
}

pdfoutput__make_pdf_calendar($displayfrom,$wholeyear,true,1,false,$laboratory_id,$experimenter_id);


?>
