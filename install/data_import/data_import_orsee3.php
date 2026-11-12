<?php

// THIS FILE IS FOR IMPORTING DATA FROM ORSEE 3.0.0-3.3.0 TO ORSEE >=3.4.0.
// CREATE THE CONFIGURATION CODE IN Options/Prepare data import //
// THEN COPY YOUR IMPORT CONFIGURATION CODE          //


//////////////////////////////////////////////////////////////////
// COPY YOUR IMPORT CONFIGURATION CODE BELOW HERE               //

$old_db_name="orsee_old";
$import_mode="operational"; // operational | full_bootstrap

// mapping from old participant profile form to new form
// empty value for new column name implies no import
// pform_mapping[old column name]=new column name
$pform_mapping=array();

// mappings from old IDs/keys to target IDs/keys
$admin_mapping=array();
$admin_import_new=array();
$subpool_mapping=array();
$participant_status_mapping=array();
$participation_status_mapping=array();
$experiment_type_mapping=array();
$experiment_class_mapping=array();
$laboratory_mapping=array();
$event_category_mapping=array();
$file_upload_category_mapping=array();
$payment_type_mapping=array();
$budget_mapping=array();
$mailbox_mapping=array();

// public_content_mapping[old content_name]=target richtext content_name
$public_content_mapping=array();

// copied objects are created/updated from the old database on every import run
$copy_budgets=array();
$copy_mailboxes=array();

// email archive import is optional because it can be large and sensitive
$import_emails="n";


// COPY YOUR IMPORT CONFIGURATION CODE ABOVE HERE               //
//////////////////////////////////////////////////////////////////

foreach (array(
    'pform_mapping',
    'admin_mapping',
    'admin_import_new',
    'subpool_mapping',
    'participant_status_mapping',
    'participation_status_mapping',
    'experiment_type_mapping',
    'experiment_class_mapping',
    'laboratory_mapping',
    'event_category_mapping',
    'file_upload_category_mapping',
    'payment_type_mapping',
    'budget_mapping',
    'mailbox_mapping',
    'public_content_mapping',
    'copy_budgets',
    'copy_mailboxes'
) as $mapping_var) {
    if (!isset($$mapping_var)) {
        $$mapping_var=array();
    }
}
if (!isset($import_emails)) {
    $import_emails="n";
}

if (PHP_SAPI!=='cli') {
    http_response_code(403);
    exit("This script can only be executed from the command line.\n");
}

$data_import_dir=__DIR__;
chdir($data_import_dir.'/..');

// for debugging purposes
$do_delete=true;
$do_insert=true;

// get tagsets
include("../admin/cronheader.php");

if ($old_db_name===$site__database_database) {
    exit("The source database must be different from the configured target database.\n");
}
$new_db_name=$site__database_database;


// Import helpers for ORSEE db-string lists and same-generation table copies.
function import__map_id($value,$mapping) {
    if ($value==='' || $value===null) {
        return $value;
    }
    return isset($mapping[$value]) ? $mapping[$value] : $value;
}

function import__map_db_string($value,$mapping) {
    $ids=db_string_to_id_array((string)$value);
    $mapped=array();
    foreach ($ids as $id) {
        $mapped[]=import__map_id($id,$mapping);
    }
    return id_array_to_db_string($mapped);
}

function import__clear_table($table,$where='') {
    global $do_delete, $new_db_name;
    if (!$do_delete) {
        return false;
    }
    $query="DELETE FROM ".$new_db_name.".".table($table);
    if ($where) {
        $query.=" ".$where;
    }
    return or_query($query);
}

function import__get_columns($db_name,$table) {
    $query="SHOW COLUMNS FROM ".$db_name.".".table($table);
    $result=or_query($query);
    $columns=array();
    while ($line=pdo_fetch_assoc($result)) {
        $columns[]=$line['Field'];
    }
    return $columns;
}

function import__common_columns($table) {
    global $old_db_name, $new_db_name;
    $old_columns=import__get_columns($old_db_name,$table);
    $new_columns=import__get_columns($new_db_name,$table);
    return array_values(array_intersect($old_columns,$new_columns));
}

function import__copy_common_columns($table,$idvar,$where='',$clear=false) {
    global $do_insert, $old_db_name;
    if ($clear) {
        import__clear_table($table);
    }
    $columns=import__common_columns($table);
    $column_sql=implode(',',array_map(function ($column) {
        return '`'.$column.'`';
    },$columns));
    $query="SELECT ".$column_sql." FROM ".$old_db_name.".".table($table);
    if ($where) {
        $query.=" ".$where;
    }
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        if ($do_insert) {
            $done=orsee_db_save_array($line,$table,$line[$idvar],$idvar);
        }
    }
}

function import__append_log_once($table,$line) {
    global $do_insert;
    $pars=array(
        ':id'=>$line['id'],
        ':timestamp'=>$line['timestamp'],
        ':action'=>$line['action'],
        ':target'=>$line['target']
    );
    $query="SELECT log_id FROM ".table($table)."
            WHERE id=:id
            AND timestamp=:timestamp
            AND action=:action
            AND target=:target";
    $existing=orsee_query($query,$pars);
    if (isset($existing['log_id'])) {
        return false;
    }
    unset($line['log_id']);
    if ($do_insert) {
        $set_parts=array();
        $insert_pars=array();
        foreach ($line as $field=>$value) {
            $set_parts[]=$field.'=:'.$field;
            $insert_pars[':'.$field]=$value;
        }
        $query="INSERT INTO ".table($table)." SET ".implode(', ',$set_parts);
        return or_query($query,$insert_pars);
    }
    return false;
}

// IMPORT STARTS HERE
if (!in_array($import_mode,array('operational','full_bootstrap'),true)) {
    echo "Unknown import mode. Use 'operational' or 'full_bootstrap'.\n\n";
    $allset=false;
} elseif ($import_mode==='full_bootstrap') {
    $allset=true;
} elseif (isset($pform_mapping) && is_array($pform_mapping) && count($pform_mapping)>0) {
    $allset=true;
} else {
    $allset=false;
}

if (!$allset) {
    echo "You first need to configure the data import (in ORSEE, as 'installer', go to Options->Prepare data import) and paste the resulting code into the top of this file.\n\n";
} else {
    echo "ORSEE 3 data import configuration loaded.\n";
    echo "Import mode: ".$import_mode."\n";

    // FULL BOOTSTRAP ONLY
    if ($import_mode==='full_bootstrap') {
        echo "Importing experiment types from ".table('experiment_types')."\n";
        import__copy_common_columns('experiment_types','exptype_id','',true);

        echo "Importing budgets from ".table('budgets')."\n";
        import__copy_common_columns('budgets','budget_id','',true);

        echo "Importing subpools from ".table('subpools')."\n";
        import__copy_common_columns('subpools','subpool_id','',true);

        echo "Importing participant statuses from ".table('participant_statuses')."\n";
        import__copy_common_columns('participant_statuses','status_id','',true);

        echo "Importing participation statuses from ".table('participation_statuses')."\n";
        import__copy_common_columns('participation_statuses','pstatus_id','',true);

        echo "Importing missing admin types from ".table('admin_types')."\n";
        $query="SELECT * FROM ".$old_db_name.".".table('admin_types');
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $query="SELECT type_id FROM ".table('admin_types')."
                    WHERE type_name=:type_name";
            $existing=orsee_query($query,array(':type_name'=>$line['type_name']));
            if (!isset($existing['type_id']) && $do_insert) {
                $done=orsee_db_save_array($line,"admin_types",$line['type_id'],"type_id");
            }
        }

        echo "Importing language symbols and date/time formats from ".table('lang')."\n";
        $lang_columns=import__common_columns('lang');
        $lang_column_sql=implode(',',array_map(function ($column) {
            return '`'.$column.'`';
        },$lang_columns));
        $query="SELECT ".$lang_column_sql." FROM ".$old_db_name.".".table('lang')."
                WHERE content_type IN ('lang','datetime_format')
                AND content_name IS NOT NULL
                AND content_name<>''";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $query="SELECT lang_id FROM ".table('lang')."
                    WHERE content_type=:content_type
                    AND content_name=:content_name";
            $existing=orsee_query($query,array(':content_type'=>$line['content_type'],':content_name'=>$line['content_name']));
            if ($do_insert) {
                if (isset($existing['lang_id'])) {
                    $line['lang_id']=$existing['lang_id'];
                    $done=orsee_db_save_array($line,"lang",$existing['lang_id'],"lang_id");
                } else {
                    unset($line['lang_id']);
                    $done=lang__insert_to_lang($line);
                }
            }
        }

        echo "Importing taxonomy language rows from ".table('lang')."\n";
        $taxonomy_lang_content_types=array('experiment_type','subjectpool','participant_status_name',
            'participant_status_error','participation_status_internal_name','participation_status_display_name',
            'payments_type','laboratory','experimentclass','events_category','file_upload_category','emails_mailbox');
        import__clear_table('lang',"WHERE content_type IN ('".implode("','",$taxonomy_lang_content_types)."')");
        $query="SELECT ".$lang_column_sql." FROM ".$old_db_name.".".table('lang')."
                WHERE content_type IN ('".implode("','",$taxonomy_lang_content_types)."')";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            if ($do_insert) {
                unset($line['lang_id']);
                $done=lang__insert_to_lang($line);
            }
        }

        echo "Importing FAQs from ".table('faqs')."\n";
        import__copy_common_columns('faqs','faq_id','',true);
        import__clear_table('lang',"WHERE content_type IN ('faq_question','faq_answer')");
        $query="SELECT ".$lang_column_sql." FROM ".$old_db_name.".".table('lang')."
                WHERE content_type IN ('faq_question','faq_answer')";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            if ($do_insert) {
                unset($line['lang_id']);
                $done=lang__insert_to_lang($line);
            }
        }

        echo "Importing mapped public content from ".table('lang')."\n";
        foreach ($public_content_mapping as $old_content_name=>$new_content_name) {
            if (!$new_content_name) {
                continue;
            }
            $query="SELECT ".$lang_column_sql." FROM ".$old_db_name.".".table('lang')."
                    WHERE content_type='public_content'
                    AND content_name=:content_name";
            $line=orsee_query($query,array(':content_name'=>$old_content_name));
            if (!isset($line['content_name'])) {
                continue;
            }
            $line['content_name']=$new_content_name;
            $query="SELECT lang_id FROM ".table('lang')."
                    WHERE content_type='public_content'
                    AND content_name=:content_name";
            $existing=orsee_query($query,array(':content_name'=>$new_content_name));
            if ($do_insert) {
                if (isset($existing['lang_id'])) {
                    $line['lang_id']=$existing['lang_id'];
                    $done=orsee_db_save_array($line,"lang",$existing['lang_id'],"lang_id");
                } else {
                    unset($line['lang_id']);
                    $done=lang__insert_to_lang($line);
                }
            }
        }
    }

    // FULL BOOTSTRAP AND OPERATIONAL
    // Admin accounts are merged into the configured target system.
    echo "Importing missing admin profiles from ".table('admin')."\n";
    $existing_admin_ids=array();
    $existing_adminnames=array();
    $query="SELECT admin_id, adminname FROM ".$new_db_name.".".table('admin');
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        $existing_admin_ids[]=$line['admin_id'];
        $existing_adminnames[]=$line['adminname'];
    }
    $query="SELECT * FROM ".$old_db_name.".".table('admin');
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) {
        $old_admin_id=$line['admin_id'];
        $new_admin_id=import__map_id($old_admin_id,$admin_mapping);
        if (isset($admin_import_new[$old_admin_id])) {
            $new_admin_id=$admin_import_new[$old_admin_id]['admin_id'];
            $line['adminname']=$admin_import_new[$old_admin_id]['adminname'];
        }
        if (!in_array($new_admin_id,$existing_admin_ids)) {
            $line['admin_id']=$new_admin_id;
            if (in_array($line['adminname'],$existing_adminnames)) {
                echo "Skipping admin ".$old_admin_id.": target adminname ".$line['adminname']." already exists.\n";
            } elseif ($do_insert) {
                $done=orsee_db_save_array($line,"admin",$new_admin_id,"admin_id");
                $existing_admin_ids[]=$new_admin_id;
                $existing_adminnames[]=$line['adminname'];
            }
        }
    }

    // Core operational tables are replaced on every import run.
    import__clear_table('participants_log');
    import__clear_table('participate_at');
    import__clear_table('sessions');
    import__clear_table('experiments');
    import__clear_table('participants');

    echo "Importing participants from ".table('participants')."\n";
    $participant_base_fields=array('participant_id','participant_id_crypt','password_crypted','confirmation_token',
        'pwreset_token','pwreset_request_time','last_login_attempt','failed_login_attempts','locked','creation_time',
        'deletion_time','subpool_id','subscriptions','rules_signed','status_id','number_reg','number_noshowup',
        'last_enrolment','last_profile_update','last_activity','pending_profile_update_request',
        'profile_update_request_new_pool','apply_permanent_queries','remarks','language');
    $query="SELECT * FROM ".$old_db_name.".".table('participants');
    $result=or_query($query);
    while ($old_participant=pdo_fetch_assoc($result)) {
        $participant=array();
        foreach ($participant_base_fields as $field) {
            if (isset($old_participant[$field])) {
                $participant[$field]=$old_participant[$field];
            }
        }
        if ($import_mode==='operational' && isset($participant['subpool_id'])) {
            $participant['subpool_id']=import__map_id($participant['subpool_id'],$subpool_mapping);
        }
        if ($import_mode==='operational' && isset($participant['profile_update_request_new_pool'])) {
            $participant['profile_update_request_new_pool']=import__map_id($participant['profile_update_request_new_pool'],$subpool_mapping);
        }
        if ($import_mode==='operational' && isset($participant['status_id'])) {
            $participant['status_id']=import__map_id($participant['status_id'],$participant_status_mapping);
        }
        if ($import_mode==='operational' && isset($participant['subscriptions'])) {
            $participant['subscriptions']=import__map_db_string($participant['subscriptions'],$experiment_type_mapping);
        }
        foreach ($pform_mapping as $old_field=>$new_field) {
            if ($new_field && isset($old_participant[$old_field])) {
                $participant[$new_field]=$old_participant[$old_field];
            }
        }
        if ($do_insert) {
            $done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");
        }
    }

    echo "Importing participant log from ".table('participants_log')."\n";
    import__copy_common_columns('participants_log','log_id');

    echo "Importing experiments from ".table('experiments')."\n";
    $query="SELECT * FROM ".$old_db_name.".".table('experiments');
    $result=or_query($query);
    while ($experiment=pdo_fetch_assoc($result)) {
        if ($import_mode==='operational' && isset($experiment['experiment_ext_type'])) {
            $experiment['experiment_ext_type']=import__map_id($experiment['experiment_ext_type'],$experiment_type_mapping);
        }
        if ($import_mode==='operational' && isset($experiment['experiment_class'])) {
            $experiment['experiment_class']=import__map_db_string($experiment['experiment_class'],$experiment_class_mapping);
        }
        if (isset($experiment['experimenter'])) {
            $experiment['experimenter']=import__map_db_string($experiment['experimenter'],$admin_mapping);
        }
        if (isset($experiment['experimenter_mail'])) {
            $experiment['experimenter_mail']=import__map_db_string($experiment['experimenter_mail'],$admin_mapping);
        }
        if ($import_mode==='operational' && isset($experiment['payment_types'])) {
            $experiment['payment_types']=import__map_db_string($experiment['payment_types'],$payment_type_mapping);
        }
        if ($import_mode==='operational' && isset($experiment['payment_budgets'])) {
            $experiment['payment_budgets']=import__map_db_string($experiment['payment_budgets'],$budget_mapping);
        }
        if ($do_insert) {
            $done=orsee_db_save_array($experiment,"experiments",$experiment['experiment_id'],"experiment_id");
        }
    }

    echo "Importing sessions from ".table('sessions')."\n";
    $query="SELECT * FROM ".$old_db_name.".".table('sessions')." WHERE session_id>0";
    $result=or_query($query);
    while ($session=pdo_fetch_assoc($result)) {
        if ($import_mode==='operational' && isset($session['laboratory_id'])) {
            $session['laboratory_id']=import__map_id($session['laboratory_id'],$laboratory_mapping);
        }
        if ($import_mode==='operational' && isset($session['payment_types'])) {
            $session['payment_types']=import__map_db_string($session['payment_types'],$payment_type_mapping);
        }
        if ($import_mode==='operational' && isset($session['payment_budgets'])) {
            $session['payment_budgets']=import__map_db_string($session['payment_budgets'],$budget_mapping);
        }
        if ($do_insert) {
            $done=orsee_db_save_array($session,"sessions",$session['session_id'],"session_id");
        }
    }

    echo "Importing participation history from ".table('participate_at')."\n";
    $query="SELECT * FROM ".$old_db_name.".".table('participate_at');
    $result=or_query($query);
    while ($participation=pdo_fetch_assoc($result)) {
        if ($import_mode==='operational' && isset($participation['pstatus_id'])) {
            $participation['pstatus_id']=import__map_id($participation['pstatus_id'],$participation_status_mapping);
        }
        if ($import_mode==='operational' && isset($participation['payment_type'])) {
            $participation['payment_type']=import__map_id($participation['payment_type'],$payment_type_mapping);
        }
        if ($import_mode==='operational' && isset($participation['payment_budget'])) {
            $participation['payment_budget']=import__map_id($participation['payment_budget'],$budget_mapping);
        }
        if ($do_insert) {
            $done=orsee_db_save_array($participation,"participate_at",$participation['participate_id'],"participate_id");
        }
    }

    // Secondary operational tables are always imported and kept rerunnable.
    echo "Importing events from ".table('events')."\n";
    import__clear_table('events');
    $query="SELECT * FROM ".$old_db_name.".".table('events');
    $result=or_query($query);
    while ($event=pdo_fetch_assoc($result)) {
        if ($import_mode==='operational' && isset($event['laboratory_id'])) {
            $event['laboratory_id']=import__map_id($event['laboratory_id'],$laboratory_mapping);
        }
        if ($import_mode==='operational' && isset($event['event_category'])) {
            $event['event_category']=import__map_id($event['event_category'],$event_category_mapping);
        }
        if (isset($event['experimenter'])) {
            $event['experimenter']=import__map_db_string($event['experimenter'],$admin_mapping);
        }
        if ($do_insert) {
            $done=orsee_db_save_array($event,"events",$event['event_id'],"event_id");
        }
    }

    echo "Importing uploaded files from ".table('uploads')." and ".table('uploads_data')."\n";
    import__clear_table('uploads','WHERE upload_id>1');
    import__clear_table('uploads_data','WHERE upload_id>1');
    $query="SELECT * FROM ".$old_db_name.".".table('uploads')." WHERE upload_id>1";
    $result=or_query($query);
    while ($upload=pdo_fetch_assoc($result)) {
        if ($import_mode==='operational' && isset($upload['upload_type'])) {
            $upload['upload_type']=import__map_id($upload['upload_type'],$file_upload_category_mapping);
        }
        if ($do_insert) {
            $done=orsee_db_save_array($upload,"uploads",$upload['upload_id'],"upload_id");
        }
    }
    $query="SELECT * FROM ".$old_db_name.".".table('uploads_data')." WHERE upload_id>1";
    $result=or_query($query);
    while ($upload_data=pdo_fetch_assoc($result)) {
        if ($do_insert) {
            $done=orsee_db_save_array($upload_data,"uploads_data",$upload_data['upload_id'],"upload_id");
        }
    }

    echo "Importing saved and permanent queries from ".table('queries')."\n";
    import__clear_table('queries');
    $query="SELECT * FROM ".$old_db_name.".".table('queries');
    $result=or_query($query);
    while ($saved_query=pdo_fetch_assoc($result)) {
        if (isset($saved_query['admin_id'])) {
            $saved_query['admin_id']=import__map_id($saved_query['admin_id'],$admin_mapping);
        }
        if ($do_insert) {
            $done=orsee_db_save_array($saved_query,"queries",$saved_query['query_id'],"query_id");
        }
    }

    echo "Importing admin log from ".table('admin_log')."\n";
    $query="SELECT * FROM ".$old_db_name.".".table('admin_log');
    $result=or_query($query);
    while ($log_entry=pdo_fetch_assoc($result)) {
        if (isset($log_entry['id'])) {
            $log_entry['id']=import__map_id($log_entry['id'],$admin_mapping);
        }
        $done=import__append_log_once('admin_log',$log_entry);
    }

    echo "Importing cron log from ".table('cron_log')."\n";
    $query="SELECT * FROM ".$old_db_name.".".table('cron_log');
    $result=or_query($query);
    while ($log_entry=pdo_fetch_assoc($result)) {
        if (isset($log_entry['id'])) {
            $log_entry['id']=import__map_id($log_entry['id'],$admin_mapping);
        }
        $done=import__append_log_once('cron_log',$log_entry);
    }

    // Experiment-specific mail texts follow the imported experiment IDs.
    $experiment_mail_content_types=array('experiment_invitation_mail','experiment_enrolment_conf_mail','experiment_session_reminder_mail');
    $lang_columns=import__common_columns('lang');
    $lang_column_sql=implode(',',array_map(function ($column) {
        return '`'.$column.'`';
    },$lang_columns));
    foreach ($experiment_mail_content_types as $content_type) {
        echo "Importing language items for ".$content_type." from ".table('lang')."\n";
        import__clear_table('lang',"WHERE content_type='".$content_type."'");
        $query="SELECT ".$lang_column_sql." FROM ".$old_db_name.".".table('lang')."
                WHERE content_type='".$content_type."'";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            if ($do_insert) {
                $done=lang__insert_to_lang($line);
            }
        }
    }

    if ($import_emails=='y') {
        echo "Importing email archive from ".table('emails')."\n";
        import__clear_table('emails');
        $email_columns=import__common_columns('emails');
        $email_column_sql=implode(',',array_map(function ($column) {
            return '`'.$column.'`';
        },$email_columns));
        $query="SELECT ".$email_column_sql." FROM ".$old_db_name.".".table('emails');
        $result=or_query($query);
        while ($email=pdo_fetch_assoc($result)) {
            if ($import_mode==='operational' && isset($email['mailbox'])) {
                $email['mailbox']=import__map_id($email['mailbox'],$mailbox_mapping);
            }
            if (isset($email['admin_id'])) {
                $email['admin_id']=import__map_id($email['admin_id'],$admin_mapping);
            }
            if (isset($email['assigned_to'])) {
                $email['assigned_to']=import__map_db_string($email['assigned_to'],$admin_mapping);
            }
            if ($do_insert) {
                $done=orsee_db_save_array($email,"emails",$email['message_id'],"message_id");
            }
        }
    }

    // OPERATIONAL ONLY
    if ($import_mode==='operational') {
        // Copied support objects are updated on rerun; mapped existing objects are left untouched.
        $existing_experiment_classes=array();
        $query="SELECT content_name FROM ".$new_db_name.".".table('lang')."
                WHERE content_type='experimentclass'";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $existing_experiment_classes[]=$line['content_name'];
        }
        $old_experiment_classes=array();
        $query="SELECT content_name FROM ".$old_db_name.".".table('lang')."
                WHERE content_type='experimentclass'";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $old_experiment_classes[]=$line['content_name'];
        }
        echo "Copying missing experiment classes from ".table('lang')."\n";
        foreach ($old_experiment_classes as $old_experiment_class) {
            if (!isset($experiment_class_mapping[$old_experiment_class]) && !in_array($old_experiment_class,$existing_experiment_classes)) {
                $query="SELECT ".$lang_column_sql." FROM ".$old_db_name.".".table('lang')."
                        WHERE content_type='experimentclass'
                        AND content_name=:content_name";
                $experiment_class=orsee_query($query,array(':content_name'=>$old_experiment_class));
                if (isset($experiment_class['content_name']) && $do_insert) {
                    unset($experiment_class['lang_id']);
                    $done=lang__insert_to_lang($experiment_class);
                }
            }
        }

        $existing_budget_ids=array();
        $query="SELECT budget_id FROM ".$new_db_name.".".table('budgets');
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $existing_budget_ids[]=$line['budget_id'];
        }
        $old_budget_ids=array();
        $query="SELECT budget_id FROM ".$old_db_name.".".table('budgets');
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $old_budget_ids[]=$line['budget_id'];
        }
        foreach ($old_budget_ids as $old_budget_id) {
            if (!isset($budget_mapping[$old_budget_id]) && !in_array($old_budget_id,$existing_budget_ids)) {
                $budget_mapping[$old_budget_id]=$old_budget_id;
                $copy_budgets[$old_budget_id]=$old_budget_id;
            }
        }
        echo "Copying imported budgets from ".table('budgets')."\n";
        foreach ($copy_budgets as $old_budget_id=>$new_budget_id) {
            $query="SELECT * FROM ".$old_db_name.".".table('budgets')."
                    WHERE budget_id=:budget_id";
            $budget=orsee_query($query,array(':budget_id'=>$old_budget_id));
            if (isset($budget['budget_id'])) {
                $budget['budget_id']=$new_budget_id;
                if (isset($budget['experimenter'])) {
                    $budget['experimenter']=import__map_db_string($budget['experimenter'],$admin_mapping);
                }
                if ($do_insert) {
                    $done=orsee_db_save_array($budget,"budgets",$new_budget_id,"budget_id");
                }
            }
        }

        $existing_mailboxes=array();
        $query="SELECT content_name FROM ".$new_db_name.".".table('lang')."
                WHERE content_type='emails_mailbox'";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $existing_mailboxes[]=$line['content_name'];
        }
        $old_mailboxes=array();
        $query="SELECT content_name FROM ".$old_db_name.".".table('lang')."
                WHERE content_type='emails_mailbox'";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            $old_mailboxes[]=$line['content_name'];
        }
        foreach ($old_mailboxes as $old_mailbox) {
            if (!isset($mailbox_mapping[$old_mailbox]) && !in_array($old_mailbox,$existing_mailboxes)) {
                $mailbox_mapping[$old_mailbox]=$old_mailbox;
                $copy_mailboxes[$old_mailbox]=$old_mailbox;
            }
        }
        echo "Copying imported email mailboxes from ".table('lang')."\n";
        foreach ($copy_mailboxes as $old_mailbox=>$new_mailbox) {
            $query="SELECT * FROM ".$old_db_name.".".table('lang')."
                    WHERE content_type='emails_mailbox'
                    AND content_name=:content_name";
            $mailbox=orsee_query($query,array(':content_name'=>$old_mailbox));
            if (isset($mailbox['content_name'])) {
                unset($mailbox['lang_id']);
                $mailbox['content_name']=$new_mailbox;
                $query="SELECT lang_id FROM ".table('lang')."
                        WHERE content_type='emails_mailbox'
                        AND content_name=:content_name";
                $existing=orsee_query($query,array(':content_name'=>$new_mailbox));
                if ($do_insert) {
                    if (isset($existing['lang_id'])) {
                        $done=orsee_db_save_array($mailbox,"lang",$existing['lang_id'],"lang_id");
                    } else {
                        $done=lang__insert_to_lang($mailbox);
                    }
                }
            }
        }
    }

    echo "Import completed.\n";
}

?>
