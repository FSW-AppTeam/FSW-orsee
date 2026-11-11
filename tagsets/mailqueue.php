<?php
// part of orsee. see orsee.org

function mailqueue__show_mailqueue($experiment_id="",$limit=-1) {
    global $lang, $options,$proceed;
    $is_rtl=lang__is_rtl();

    if ($proceed) {
        $pars=array();

        if ($limit==-1 && $experiment_id && isset($options['mailqueue_experiment_number_of_entries_per_page']) && $options['mailqueue_experiment_number_of_entries_per_page']) {
            $limit=$options['mailqueue_experiment_number_of_entries_per_page'];
        } elseif ($limit==-1 && isset($options['mailqueue_number_of_entries_per_page']) && $options['mailqueue_number_of_entries_per_page']) {
            $limit=$options['mailqueue_number_of_entries_per_page'];
        } else {
            $limit=100;
        }

        if (isset($_REQUEST['os']) && $_REQUEST['os']>0) {
            $offset=$_REQUEST['os'];
        } else {
            $offset=0;
        }

        if ($experiment_id) {
            $equery=" AND experiment_id=:experiment_id ";
            $pars[':experiment_id']=$experiment_id;
        } else {
            $equery="";
        }

        if (isset($_REQUEST['deleteall']) && $_REQUEST['deleteall']) {
            $dall=true;
        } else {
            $dall=false;
        }
        if (isset($_REQUEST['deleteallonpage']) && $_REQUEST['deleteallonpage']) {
            $dallpage=true;
        } else {
            $dallpage=false;
        }
        if (isset($_REQUEST['deletesel']) && $_REQUEST['deletesel']) {
            $dsel=true;
        } else {
            $dsel=false;
        }
    }

    if ($proceed) {
        if ($dall || $dallpage || $dsel) {
            if (!csrf__validate_request_message()) {
                $proceed=false;
            }
        }
    }

    if ($proceed) {
        if ($dall || $dallpage || $dsel) {
            if ($experiment_id) {
                $allow=check_allow('mailqueue_edit_experiment','experiment_mailqueue_show?experiment_id='.$experiment_id);
            } else {
                $allow=check_allow('mailqueue_edit_all','mailqueue_show.php');
            }

            $where_clause=" WHERE mail_id IS NOT NULL ".$equery;

            $ok=false;
            if ($dall) {
                $ok=true;
            }


            if ($dallpage) {
                $tallids=array();
                if (isset($_REQUEST['allids']) && trim($_REQUEST['allids'])) {
                    $tallids=explode(",",trim($_REQUEST['allids']));
                }
                if (count($tallids)>0) {
                    $i=0;
                    $parnames=array();
                    foreach ($tallids as $id) {
                        $i++;
                        $tparname=':mailid'.$i;
                        $parnames[]=$tparname;
                        $pars[$tparname]=$id;
                    }
                    $where_clause.=" AND mail_id IN (".implode(",",$parnames).") ";
                    $ok=true;
                } else {
                    message(lang('error__mailqueue_delete_no_emails_selected'),'warning');
                    $ok=false;
                }
            }

            if ($dsel) {
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
                        $tparname=':mailid'.$i;
                        $parnames[]=$tparname;
                        $pars[$tparname]=$id;
                    }
                    $where_clause.=" AND mail_id IN (".implode(",",$parnames).") ";
                    $ok=true;
                } else {
                    message(lang('error__mailqueue_delete_no_emails_selected'),'warning');
                    $ok=false;
                }
            }

            if ($ok) {
                $query="DELETE FROM ".table('mail_queue').$where_clause;
                //echo $query;

                $done=or_query($query,$pars);
                $number=pdo_num_rows($done);
                message($number.' '.lang('xxx_emails_deleted_from_queue'));

                if ($experiment_id) {
                    if ($number>0) {
                        log__admin("mailqueue_delete_entries","Experiment: ".$experiment_id.", Count: ".$number);
                    }
                } else {
                    if ($number>0) {
                        log__admin("mailqueue_delete_entries","Count: ".$number);
                    }
                }
            }
            if ($experiment_id) {
                redirect("admin/experiment_mailqueue_show.php?experiment_id=".$experiment_id);
            } else {
                redirect("admin/mailqueue_show.php");
            }
        }
    }

    if ($proceed) {
        $pars=array();
        if ($experiment_id) {
            $equery=" AND experiment_id=:experiment_id ";
            $pars[':experiment_id']=$experiment_id;
        } else {
            $equery="";
        }
        $pars[':offset']=$offset;
        $pars[':limit']=$limit;
        $query="SELECT * FROM ".table('mail_queue')."
        WHERE mail_id IS NOT NULL ".
            $equery.
            " ORDER BY timestamp, mail_id
        LIMIT :offset , :limit";
        $result=or_query($query,$pars);
        $num_rows=pdo_num_rows($result);

        $shade=false;
        $ids=array();
        $experiment_ids=array();
        $entries=array();
        while ($line=pdo_fetch_assoc($result)) {
            $ids[]=$line['mail_id'];
            if ($line['experiment_id']) {
                $experiment_ids[]=$line['experiment_id'];
            }
            $entries[]=$line;
        }
        $experiments=experiment__load_experiments_for_ids($experiment_ids);

        $can_select=check_allow('mailqueue_edit_all');

        if (check_allow('participants_edit')) {
            echo javascript__edit_popup();
        }

        echo '<div class="orsee-log-topbar">';
        echo '<div></div>';
        echo '<div>';
        if ($can_select) {
            echo '<form class="orsee-log-delete-form" method="POST" action="'.($experiment_id ? 'experiment_mailqueue_show.php' : 'mailqueue_show.php').'">';
            echo csrf__field();
            if ($experiment_id) {
                echo '<input type="hidden" name="experiment_id" value="'.$experiment_id.'">';
            }
            echo '<input type="hidden" name="allids" value="'.implode(",",$ids).'">';
            echo button_submit_delete('deleteall',lang('delete_all'));
            echo button_submit_delete('deleteallonpage',lang('delete_all_on_page'));
            echo button_submit_delete('deletesel',lang('delete_selected'));
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

        if ($can_select) {
            echo '<form method="POST" action="'.($experiment_id ? 'experiment_mailqueue_show.php' : 'mailqueue_show.php').'">';
            if ($experiment_id) {
                echo '<input type="hidden" name="experiment_id" value="'.$experiment_id.'">';
            }
            echo '<input type="hidden" name="allids" value="'.implode(",",$ids).'">';
        }
        echo '<div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile orsee-table-cells-compact">';
        $head_class='orsee-table-row orsee-table-head';
        echo '<div class="'.$head_class.'">';
        if ($is_rtl && $can_select) {
            echo '<div class="orsee-table-cell orsee-table-action" data-label="'.htmlspecialchars(lang('select_all')).'">';
            echo lang('select_all').' '.javascript__selectall_checkbox_script('del');
            echo '</div>';
        }
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('id')).'">'.lang('id').'</div>';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('date_and_time')).'">'.lang('date_and_time').'</div>';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('email_type')).'">'.lang('email_type').'</div>';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('email_recipient')).'">'.lang('email_recipient').'</div>';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('reference')).'">'.lang('reference').'</div>';
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('error')).'">'.lang('error').'</div>';
        if (!$is_rtl && $can_select) {
            echo '<div class="orsee-table-cell orsee-table-action" data-label="'.htmlspecialchars(lang('select_all')).'">';
            echo lang('select_all').' '.javascript__selectall_checkbox_script('del');
            echo '</div>';
        }
        echo '</div>';

        foreach ($entries as $line) {
            $row_class='orsee-table-row';
            if ($shade) {
                $row_class.=' is-alt';
                $shade=false;
            } else {
                $shade=true;
            }
            echo '<div class="'.$row_class.'">';
            if ($is_rtl && $can_select) {
                echo '<div class="orsee-table-cell orsee-table-action" data-label="'.htmlspecialchars(lang('select')).'"><INPUT type="checkbox" name="del['.$line['mail_id'].']" value="y"></div>';
            }
            echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('id')).'">'.$line['mail_id'].'</div>';
            echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('date_and_time')).'">'.ortime__format($line['timestamp'],'hide_second:false',lang('lang')).'</div>';
            echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('email_type')).'">'.$line['mail_type'].'</div>';
            echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('email_recipient')).'">';
            if (check_allow('participants_edit') && preg_match('/^\d+$/',(string)$line['mail_recipient'])) {
                echo '<A class="orsee-link-hover-underline" href="#" onclick="javascript:editPopup('.(int)$line['mail_recipient'].'); return false;">'.$line['mail_recipient'].'</A>';
            } else {
                echo $line['mail_recipient'];
            }
            echo '</div>';
            echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('reference')).'">';
            $reference=array();
            if ($line['experiment_id']) {
                $reference[]='Experiment: <A class="orsee-link-hover-underline" HREF="experiment_show.php?experiment_id='.$line['experiment_id'].'">'.$experiments[$line['experiment_id']]['experiment_name'].'</A>';
            }
            if ($line['session_id']) {
                $reference[]='Session: <A class="orsee-link-hover-underline" HREF="session_edit.php?session_id='.$line['session_id'].'">'.$line['session_id'].'</A>';
            }
            if ($line['bulk_id']) {
                $reference[]='Bulk email: '.$line['bulk_id'];
            }
            echo implode('<BR>',$reference);
            echo '</div>';
            echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('error')).'">'.$line['error'].'</div>';
            if (!$is_rtl && $can_select) {
                echo '<div class="orsee-table-cell orsee-table-action" data-label="'.htmlspecialchars(lang('select')).'"><INPUT type="checkbox" name="del['.$line['mail_id'].']" value="y"></div>';
            }
            echo '</div>';
        }
        echo '</div>';
        if ($can_select) {
            echo '</form>';
        }
        return $num_rows;
    }
}


?>
