<?php
// part of orsee. see orsee.org
ob_start();
$title="options";
$menu__area="options";
include("header.php");

if ($proceed) {
    $allow=check_allow('pform_saved_queries_view','options_main.php');
}
if ($proceed) {
    $query_types=array('participants_search_active','participants_search_all');
    if (isset($_REQUEST['type']) && $_REQUEST['type'] && in_array($_REQUEST['type'],$query_types)) {
        $type=$_REQUEST['type'];
    } else {
        redirect('admin/options_main.php');
    }
}

if ($proceed) {
    if (isset($_REQUEST['deletesel']) && $_REQUEST['deletesel'] && check_allow('pform_saved_queries_delete')) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['deletesel']) && $_REQUEST['deletesel'] && check_allow('pform_saved_queries_delete')) {
        $dids=array();
        if (isset($_REQUEST['del']) && is_array($_REQUEST['del'])) {
            foreach ($_REQUEST['del'] as $k=>$v) {
                if ($v=='y') {
                    $dids[]=$k;
                }
            }
        }
        if (count($dids)>0) {
            $i=0;
            $parnames=array();
            foreach ($dids as $id) {
                $i++;
                $tparname=':query_id'.$i;
                $parnames[]=$tparname;
                $pars[$tparname]=$id;
            }
            $pars[':query_type']=$type;
            $query="DELETE FROM ".table('queries')."
                    WHERE query_type=:query_type
                    AND query_id IN (".implode(",",$parnames).") ";
            $done=or_query($query,$pars);
            $number=pdo_num_rows($done);
            message($number.' '.lang('xxx_queries_deleted'));
            if ($number>0) {
                log__admin("query_delete","Type: ".$type.", Count: ".$number);
            }
            redirect("admin/options_saved_queries.php?type=".$type);
        } else {
            message(lang('error__query_delete_no_queries_selected'),'warning');
            redirect("admin/options_saved_queries.php?type=".$type);
        }
    }
}

if ($proceed) {
    $pars=array();
    $pars[':query_type']=$type;
    $query="SELECT * FROM ".table('queries')."
        WHERE query_type = :query_type
        ORDER BY query_time DESC";
    $result=or_query($query,$pars);
    $num_rows=pdo_num_rows($result);

    $titles=array('participants_search_active'=>'saved_queries_for_active_participants',
                'participants_search_all'=>'saved_queries_for_all_participants');

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.lang($titles[$type]).'</div><div class="orsee-panel-actions"></div></div>';

    if (check_allow('pform_saved_queries_delete')) {
        echo '<FORM action="'.thisdoc().'" method="POST">
                <INPUT type="hidden" name="type" value="'.$type.'">
                '.csrf__field().'
                ';
        echo '<div class="orsee-options-actions-end">';
        echo button_submit_delete('deletesel',lang('delete_selected'));
        echo '</div>';
    }
    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
    $is_rtl=lang__is_rtl();
    // header
    echo '<div class="orsee-table-row orsee-table-head">';
    if ($is_rtl && check_allow('pform_saved_queries_delete')) {
        echo '<div class="orsee-table-cell">
            '.lang('select_all').'
            '.javascript__selectall_checkbox_script('del').'
        </div>';
    }
    echo '<div class="orsee-table-cell" style="white-space: nowrap;">'.lang('date_and_time').'</div>';
    echo '<div class="orsee-table-cell">'.lang('query').'</div>';
    if (!$is_rtl && check_allow('pform_saved_queries_delete')) {
        echo '<div class="orsee-table-cell">
            '.lang('select_all').'
            '.javascript__selectall_checkbox_script('del').'
        </div>';
    }
    echo '</div>';

    $shade=false;
    $ids=array();
    if ($type=='participants_search_active') {
        $active=true;
    } else {
        $active=false;
    }
    while ($line=pdo_fetch_assoc($result)) {
        $posted_query=json_decode($line['json_query'],true);
        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,$active);

        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
            $shade=false;
        } else {
            $shade=true;
        }
        echo '<div class="'.$row_class.'">';
        if ($is_rtl && check_allow('pform_saved_queries_delete')) {
            echo '<div class="orsee-table-cell" data-label="'.lang('action').'"><INPUT type="checkbox" name="del['.$line['query_id'].']" value="y"></div>';
        }
        echo '<div class="orsee-table-cell" data-label="'.lang('date_and_time').'" style="white-space: nowrap;">'.ortime__format($line['query_time'],'hide_second:false',lang('lang')).'</div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('query').'">'.$pseudo_query_display.'</div>';
        $reference=array();
        if (!$is_rtl && check_allow('pform_saved_queries_delete')) {
            echo '<div class="orsee-table-cell" data-label="'.lang('action').'"><INPUT type="checkbox" name="del['.$line['query_id'].']" value="y"></div>';
        }
        echo '</div>';
    }
    echo '</div>';
    if (check_allow('pform_saved_queries_delete')) {
        echo '</FORM>';
    }
    echo '<div class="orsee-options-actions">'.button_back('options_main.php').'</div>';
    echo '</div>';
}
include("footer.php");

?>
