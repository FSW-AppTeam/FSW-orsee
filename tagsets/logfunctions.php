<?php
// part of orsee. see orsee.org


function log__participant($action,$participant_id,$target="") {
    $darr=getdate();
    $pars=array(':participant_id'=>$participant_id,
                ':year'=>$darr['year'],
                ':month'=>$darr['mon'],
                ':day'=>$darr['mday'],
                ':action'=>$action,
                ':target'=>$target,
                ':timestamp'=>$darr[0]
                );
    $query="INSERT INTO ".table('participants_log')."
            SET id= :participant_id,
            year= :year,
            month= :month,
            day= :day,
            action= :action,
            target= :target,
            timestamp= :timestamp";
    $done=or_query($query,$pars);
}

function log__admin($action="unknown",$target="") {
    global $expadmindata;
    if (isset($expadmindata['admin_id'])) {
        $admin_id= $expadmindata['admin_id'];
    } else {
        $admin_id='system';
    }
    $darr=getdate();
    $pars=array(':admin_id'=>$admin_id,
                ':year'=>$darr['year'],
                ':month'=>$darr['mon'],
                ':day'=>$darr['mday'],
                ':action'=>$action,
                ':target'=>$target,
                ':timestamp'=>$darr[0]
                );
    $query="INSERT INTO ".table('admin_log')."
            SET id= :admin_id,
            year= :year,
            month= :month,
            day= :day,
            action= :action,
            target= :target,
            timestamp= :timestamp";
    $done=or_query($query,$pars);
}

function log__cron_job($action="unknown",$target="",$now="",$id="") {
    if ($now=="") {
        $now=time();
    }
    $darr=getdate($now);

    $pars=array(':id'=>$id,
                ':year'=>$darr['year'],
                ':month'=>$darr['mon'],
                ':day'=>$darr['mday'],
                ':action'=>$action,
                ':target'=>$target,
                ':timestamp'=>$darr[0]
                );
    $query="INSERT INTO ".table('cron_log')."
            SET id= :id,
            year= :year,
            month= :month,
            day= :day,
            action= :action,
            target= :target,
            timestamp= :timestamp";
    $done=or_query($query,$pars);
    return $done;
}


function log__link() {
    $post=$_REQUEST;
    unset($post['SID']);
    unset($post['PHPSESSID']);
    $arg_list=func_get_args();
    foreach ($arg_list as $arg) {
        $var=explode("=",$arg);
        $post[$var[0]]=$var[1];
    }
    $link='<A HREF="'.thisdoc().'?';
    foreach ($post as $key=>$value) {
        // $key can contain malicious info
        $correctedKey = str_replace(array('&','<','>','"',"'",'/'),
            array('&amp;','&lt;','&gt;','&quot;','&#x27;','&#x2F;'),
            $key);
        $link.=  $correctedKey.'='.urlencode( $value ).'&';
    }
    $link.='">';
    return $link;
}

function log__restrict_link($varname,$varvalue) {
    global $lang;
    $link=log__link($varname.'='.$varvalue,'os=0');
    $link.='['.lang('restrict').']</A>';
    return $link;
}

function log__render_target_links($target_value,$can_edit_participants=false) {
    $entries=preg_split('/\r\n|\r|\n|,\s*(?=[a-z_][a-z0-9_]*\s*[:=])/i',$target_value);
    $out_entries=array();

    $participant_id='';
    $experiment_id='';
    $session_id='';

    foreach ($entries as $entry) {
        $entry_trim=trim($entry);
        if ($entry_trim==='') {
            continue;
        }
        if (preg_match('/^participant_id\s*[:=]\s*(\d+)$/i',$entry_trim,$m)) {
            $participant_id=$m[1];
            continue;
        }
        if (preg_match('/^experiment_id\s*[:=]\s*(\d+)$/i',$entry_trim,$m)) {
            $experiment_id=$m[1];
            continue;
        }
        if (preg_match('/^session_id\s*[:=]\s*(\d+)$/i',$entry_trim,$m)) {
            $session_id=$m[1];
            continue;
        }
    }

    foreach ($entries as $entry) {
        $entry_trim=trim($entry);
        if ($entry_trim==='') {
            continue;
        }

        if (preg_match('/^participant_id\s*[:=]\s*(\d+)$/i',$entry_trim)) {
            if ($participant_id!=='') {
                $has_participant_name=false;
                foreach ($entries as $check_entry) {
                    if (preg_match('/^participant\s*:/i',trim($check_entry))) {
                        $has_participant_name=true;
                        break;
                    }
                }
                if (!$has_participant_name && $can_edit_participants) {
                    $out_entries[]='<A href="#" onclick="javascript:editPopup('.(int)$participant_id.'); return false;">participant_id:'.$participant_id.'</A>';
                } elseif (!$has_participant_name) {
                    $out_entries[]='participant_id:'.$participant_id;
                }
            }
            continue;
        }

        if (preg_match('/^experiment_id\s*[:=]\s*(\d+)$/i',$entry_trim)) {
            if ($experiment_id!=='') {
                $has_experiment_name=false;
                foreach ($entries as $check_entry) {
                    if (preg_match('/^experiment\s*:/i',trim($check_entry))) {
                        $has_experiment_name=true;
                        break;
                    }
                }
                if (!$has_experiment_name) {
                    $out_entries[]='<A href="experiment_show.php?experiment_id='.(int)$experiment_id.'">experiment_id:'.$experiment_id.'</A>';
                }
            }
            continue;
        }

        if (preg_match('/^session_id\s*[:=]\s*(\d+)$/i',$entry_trim)) {
            if ($session_id!=='') {
                $has_session_name=false;
                foreach ($entries as $check_entry) {
                    if (preg_match('/^session\s*:/i',trim($check_entry))) {
                        $has_session_name=true;
                        break;
                    }
                }
                if (!$has_session_name) {
                    if ($experiment_id!=='') {
                        $out_entries[]='<A href="experiment_participants_show.php?experiment_id='.(int)$experiment_id.'&session_id='.(int)$session_id.'">session_id:'.$session_id.'</A>';
                    } else {
                        $out_entries[]='<A href="session_edit.php?session_id='.(int)$session_id.'">session_id:'.$session_id.'</A>';
                    }
                }
            }
            continue;
        }

        if (preg_match('/^participant\s*:/i',$entry_trim) && $participant_id!=='' && $can_edit_participants) {
            $out_entries[]='<A href="#" onclick="javascript:editPopup('.(int)$participant_id.'); return false;">'.$entry_trim.'</A>';
            continue;
        }

        if (preg_match('/^experiment\s*:/i',$entry_trim) && $experiment_id!=='') {
            $out_entries[]='<A href="experiment_show.php?experiment_id='.(int)$experiment_id.'">'.$entry_trim.'</A>';
            continue;
        }

        if (preg_match('/^session\s*:/i',$entry_trim) && $session_id!=='') {
            if ($experiment_id!=='') {
                $out_entries[]='<A href="experiment_participants_show.php?experiment_id='.(int)$experiment_id.'&session_id='.(int)$session_id.'">'.$entry_trim.'</A>';
            } else {
                $out_entries[]='<A href="session_edit.php?session_id='.(int)$session_id.'">'.$entry_trim.'</A>';
            }
            continue;
        }

        $out_entries[]=$entry_trim;
    }

    return implode('<br>',$out_entries);
}

function log__show_log($log) {
    global $limit;

    if (!$limit) {
        $limit=50;
    }
    if (isset($_REQUEST['os']) && $_REQUEST['os']>0) {
        $offset=$_REQUEST['os'];
    } else {
        $offset=0;
    }

    global $lang;

    $pars=array();

    if (isset($_REQUEST['action']) && $_REQUEST['action']) {
        $aquery=" AND action=:action ";
        $pars[':action']=$_REQUEST['action'];
    } else {
        $aquery="";
    }

    if (isset($_REQUEST['id']) && $_REQUEST['id']) {
        $idquery=" AND id=:id ";
        $pars[':id']=$_REQUEST['id'];
    } else {
        $idquery="";
    }

    if (isset($_REQUEST['target']) && $_REQUEST['target']) {
        $tquery=" AND target LIKE :target ";
        $pars[':target']='%'.$_REQUEST['target'].'%';
    } else {
        $tquery="";
    }

    $logtable=table('participants_log');
    switch ($log) {
        case "participant_actions":
            $logtable=table('participants_log');
            $secondtable=" LEFT JOIN ".table('participants')." ON id=participant_id ";
            break;
        case "experimenter_actions":
            $logtable=table('admin_log');
            $secondtable=" LEFT JOIN ".table('admin')." ON id=admin_id ";
            break;
        case "regular_tasks":
            $logtable=table('cron_log');
            $secondtable=" LEFT JOIN ".table('admin')." ON id=admin_id ";
            break;
    }

    if (isset($_REQUEST['delete']) && $_REQUEST['delete'] && isset($_REQUEST['days']) && $_REQUEST['days']) {
        if (!csrf__validate_request_message()) {
            redirect("admin/statistics_show_log.php?log=".$log);
        }

        $allow=check_allow('log_file_'.$log.'_delete','statistics_show_log.php?log='.$log);
        if (isset($_REQUEST['days']) && $_REQUEST['days']=="all") {
            $where_clause="";
        } else {
            $now=time();
            $dsec= (int) $_REQUEST['days']*24*60*60;
            $dtime=$now-$dsec;
            $where_clause=" WHERE timestamp < ".$dtime;
        }
        $query="DELETE FROM ".$logtable.$where_clause;
        $done=or_query($query);
        $number=pdo_num_rows($done);
        message($number.' '.lang('xxx_log_entries_deleted'));
        if ($number>0) {
            log__admin("log_delete_entries","log:".$log."\ndays:".$_REQUEST['days']);
        }
        redirect("admin/statistics_show_log.php?log=".$log);
    }


    $pars[':offset']=$offset;
    $pars[':limit']=$limit;
    $query="SELECT * FROM ".$logtable.$secondtable."
        WHERE id IS NOT NULL ".
        $aquery.$idquery.$tquery.
        " ORDER BY timestamp DESC, log_id DESC 
        LIMIT :offset , :limit ";
    $result=or_query($query,$pars);
    $num_rows=pdo_num_rows($result);
    $can_edit_participants=check_allow('participants_edit');
    if ($can_edit_participants) {
        echo javascript__edit_popup();
    }

    echo '<div class="orsee-log-topbar">';
    echo '<div></div>';
    echo '<div>';
    if (check_allow('log_file_'.$log.'_delete')) {
        echo '<form action="statistics_show_log.php" method="POST" class="orsee-log-delete-form">';
        echo csrf__field();
        echo '<input type="hidden" name="log" value="'.$log.'">';
        echo '<span>'.lang('delete_log_entries_older_than').'</span>';
        echo '<span class="select is-primary select-compact"><select name="days">';
        echo '<option value="all">'.lang('all_entries').'</option>';
        $ddays=array(1,7,30,90,180,360);
        if (isset($_REQUEST['days']) && $_REQUEST['days']) {
            $selected=$_REQUEST['days'];
        } else {
            $selected=90;
        }
        foreach ($ddays as $day) {
            echo '<option value="'.$day.'"';
            if ($day==$selected) {
                echo ' SELECTED';
            }
            echo '>'.$day.' ';
            if ($day==1) {
                echo lang('day');
            } else {
                echo lang('days');
            }
            echo '</option>';
        }
        echo '</select></span>';
        echo button_submit_delete('delete',lang('delete'));
        echo '</form>';
    }
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-log-pagination">';
    if ($offset > 0) {
        $prev_link = str_replace('<A HREF="', '<A class="button orsee-btn" HREF="', log__link('os='.($offset-$limit)));
        echo $prev_link.lang('previous').'</A>';
    } else {
        echo '<span class="button orsee-btn disabled" aria-disabled="true">'.lang('previous').'</span>';
    }
    if ($num_rows >= $limit) {
        $next_link = str_replace('<A HREF="', '<A class="button orsee-btn" HREF="', log__link('os='.($offset+$limit)));
        echo $next_link.lang('next').'</A>';
    } else {
        echo '<span class="button orsee-btn disabled" aria-disabled="true">'.lang('next').'</span>';
    }
    echo '</div>';

    $id_header='';
    if ($log=='participant_actions') {
        $id_header=lang('lastname').', '.lang('firstname');
    } elseif ($log=='experimenter_actions' || $log=='regular_tasks') {
        $id_header=lang('experimenter');
    }
    $action_header=lang('action');
    $target_header=lang('target');
    if (isset($_REQUEST['id']) && $_REQUEST['id']) {
        $id_header .= ' '.log__link('id=','os=0').'['.lang('unrestrict').']</A>';
    }
    if (isset($_REQUEST['action']) && $_REQUEST['action']) {
        $action_header .= ' '.log__link('action=','os=0').'['.lang('unrestrict').']</A>';
    }
    if (isset($_REQUEST['target']) && $_REQUEST['target']) {
        $target_header .= ' '.log__link('target=','os=0').'['.lang('unrestrict').']</A>';
    }

    echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile orsee-table-cells-compact orsee-table-log">';

    // header
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('date_and_time')).'">'.lang('date_and_time').'</div>';
    echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(strip_tags($id_header)).'">'.$id_header.'</div>';
    echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(strip_tags($action_header)).'">'.$action_header.'</div>';
    echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(strip_tags($target_header)).'">'.$target_header.'</div>';
    echo '</div>';

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
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('date_and_time')).'">'.ortime__format($line['timestamp'],'hide_seconds:false',lang('lang')).'</div>';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(strip_tags($id_header)).'">';
        if ($log=='participant_actions') {
            if ($line['participant_id']) {
                $participant_label=$line['lname'].', '.$line['fname'];
                if ($can_edit_participants) {
                    echo '<A href="#" onclick="javascript:editPopup('.(int)$line['participant_id'].'); return false;">'.$participant_label.'</A>';
                } else {
                    echo $participant_label;
                }
            } else {
                echo $line['id'];
            }
        } elseif ($log=='experimenter_actions' || $log=='regular_tasks') {
            echo $line['adminname'];
        }
        if (!isset($_REQUEST['id']) || $_REQUEST['id']!=$line['id']) {
            echo ' '.log__restrict_link('id',$line['id']);
        }
        echo '</div>';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(strip_tags($action_header)).'">'.$line['action'];
        if (!isset($_REQUEST['action']) || $_REQUEST['action']!=$line['action']) {
            echo ' '.log__restrict_link('action',$line['action']);
        }
        echo '</div>';
        $target_value=log__render_target_links(stripslashes($line['target']),$can_edit_participants);
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(strip_tags($target_header)).'">'.nl2br($target_value);
        if (!isset($_REQUEST['target']) || $_REQUEST['target']!=$line['target'] && $log!='regular_tasks') {
            echo ' '.log__restrict_link('target',$line['target']);
        }
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    return $num_rows;
}

?>
