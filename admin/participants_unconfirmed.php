<?php
// part of orsee. see orsee.org
ob_start();
$title="registered_but_not_confirmed_xxx";
$menu__area="participants";
include("header.php");

if ($proceed) {
    $allow=check_allow('participants_unconfirmed_edit','participants_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['deleteall']) && $_REQUEST['deleteall']) {
        $dall=true;
    } else {
        $dall=false;
    }
    if (isset($_REQUEST['deletesel']) && $_REQUEST['deletesel']) {
        $dsel=true;
    } else {
        $dsel=false;
    }

    if ($dall || $dsel) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
        }
    }
}

if ($proceed) {
    if (isset($_REQUEST['deleteall']) && $_REQUEST['deleteall']) {
        $dall=true;
    } else {
        $dall=false;
    }
    if (isset($_REQUEST['deletesel']) && $_REQUEST['deletesel']) {
        $dsel=true;
    } else {
        $dsel=false;
    }

    if ($dall || $dsel) {
        $ok=false;
        if ($dsel) {
            $dids=array();
            if (isset($_REQUEST['sel']) && is_array($_REQUEST['sel'])) {
                foreach ($_REQUEST['sel'] as $k=>$v) {
                    if ($v=='y') {
                        $dids[]=$k;
                    }
                }
            }
            if (count($dids)>0) {
                $ok=true;
                $i=0;
                $pars=array();
                $parnames=array();
                foreach ($dids as $id) {
                    $i++;
                    $pars[':participant_id'.$i]=$id;
                    $parnames[]=':participant_id'.$i;
                }
                $in_clause=" AND participant_id IN (".implode(",",$parnames).")";
            }
        } elseif ($dall) {
            $ok=true;
            $pars=array();
            $in_clause="";
        }

        if ($ok) {
            $query="SELECT participant_id, email
                    FROM ".table('participants')."
                    WHERE status_id='0' ".$in_clause;
            $result=or_query($query,$pars);
            $del_emails=array();
            while ($line=pdo_fetch_assoc($result)) {
                $del_emails[$line['participant_id']]=$line['email'];
            }

            $query="DELETE FROM ".table('participants')."
                    WHERE status_id='0' ".$in_clause;
            $done=or_query($query,$pars);
            $number=pdo_num_rows($done);

            message($number.' '.lang('xxx_temp_participants_deleted'));
            foreach ($del_emails as $participant_id=>$email) {
                log__admin("participant_unconfirmed_delete","participant_id: ".$participant_id.', email: '.$email);
            }
            redirect("admin/participants_unconfirmed.php");
        }
    }
}

if ($proceed) {
    echo '<div class="orsee-panel">';

    echo '<FORM action="participants_unconfirmed.php" method="POST">';
    echo csrf__field();

    $posted_query=array('query'=> array(0=> array("statusids_multiselect"=>array("not"=>"", "ms_status"=>"0"))));
    $query_array=query__get_query_array($posted_query['query']);
    $query=query__get_query($query_array,0,array(),'creation_time DESC',false);

    echo '<div class="orsee-options-actions" style="justify-content: flex-end; gap: 0.6rem; margin-bottom: 0.6rem;">';
    echo button_submit_delete('deleteall',lang('delete_all'));
    echo button_submit_delete('deletesel',lang('delete_selected'));
    echo '</div>';

    $emails=query_show_query_result($query,"participants_unconfirmed",false);

    echo '</FORM>';

    $emailstring=implode(",",$emails);
    echo '<div class="orsee-options-actions-center" style="margin-top: 0.84rem;">';
    echo button_link('mailto:'.$settings['support_mail'].'?bcc='.$emailstring,lang('write_message_to_all_listed'),'envelope');
    echo '</div>';
    echo '<div class="orsee-options-actions">';
    echo button_back('participants_main.php');
    echo '</div>';

    echo '</div>';
}
include("footer.php");

?>
