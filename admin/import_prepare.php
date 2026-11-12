<?php
// part of orsee. see orsee.org
ob_start();
$old_versions=array('orsee3'=>'versions from 3.0.0 to 3.3.x','orsee2'=>'versions <3.0');
$title="prepare_data_import";
$menu__area="options";
include("header.php");

if ($proceed) {
    check_allow('import_data','options_main.php');
}
if ($proceed) {
    $continue=true;
    if ((isset($_REQUEST['submit1']) && $_REQUEST['submit1']) || (isset($_REQUEST['submit_mode']) && $_REQUEST['submit_mode']) || (isset($_REQUEST['submit2']) && $_REQUEST['submit2'])) {
        if (!csrf__validate_request_message()) {
            $proceed=false;
            $continue=false;
        }
    }
}

if ($proceed) {
    $continue=true;


    if ($continue) {
        $databases=array();
        $query="SELECT `SCHEMA_NAME`
                FROM `INFORMATION_SCHEMA`.`SCHEMATA`
                WHERE `SCHEMA_NAME` NOT IN ('information_schema','mysql','performance_schema','sys')
                ORDER BY `SCHEMA_NAME`";
        $result=or_query($query);
        while ($line=pdo_fetch_assoc($result)) {
            if ($line['SCHEMA_NAME']!=$site__database_database) {
                $databases[]=$line['SCHEMA_NAME'];
            }
        }

        // first step:
        if (!isset($_REQUEST['old_version']) || !isset($old_versions[$_REQUEST['old_version']])
            || !isset($_REQUEST['old_database']) || !in_array($_REQUEST['old_database'],$databases)) {
            $continue=false;

            echo '<form action="'.thisdoc().'" method="POST">
                    '.csrf__field().'
                    <div class="orsee-panel">
                        <div class="orsee-form-shell">';

            echo '          <div class="field">
                            <label class="label">From which ORSEE version do you want to import data?</label>
                            <div class="control"><span class="select is-primary"><SELECT name="old_version">';
            foreach ($old_versions as $ov=>$text) {
                echo '<OPTION value="'.htmlspecialchars($ov,ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($text,ENT_QUOTES,'UTF-8').'</OPTION>';
            }
            echo '              </SELECT></span></div>
                        </div>';

            echo '          <div class="field">
                            <label class="label">From which database should the data be imported?</label>
                            <div class="control"><span class="select is-primary"><SELECT name="old_database">';
            foreach ($databases as $db) {
                echo '<OPTION value="'.htmlspecialchars($db,ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($db,ENT_QUOTES,'UTF-8').'</OPTION>';
            }
            echo '              </SELECT></span></div>
                        </div>';

            echo '          <div class="field orsee-form-actions has-text-centered">
                                <INPUT type="submit" class="button orsee-btn" name="submit1" value="Next">
                            </div>
                        </div>
                    </div>
                  </form>';
        }
    }

    if ($continue) {
        $old_version=$_REQUEST['old_version'];
        $old_database=$_REQUEST['old_database'];
        $old_version_html=htmlspecialchars($old_version,ENT_QUOTES,'UTF-8');
        $old_database_html=htmlspecialchars($old_database,ENT_QUOTES,'UTF-8');

        // Check whether the selected import path fits the old database structure.
        $pars=array(':old_database'=>$old_database);
        $query="SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA=:old_database
                AND TABLE_NAME='".table('options')."'";
        $result=or_query($query,$pars);
        $old_tables=array();
        while ($line=pdo_fetch_assoc($result)) {
            $old_tables[]=$line['TABLE_NAME'];
        }

        $query="SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=:old_database
                AND TABLE_NAME='".table('participants')."'
                AND COLUMN_NAME IN ('deleted','excluded')";
        $result=or_query($query,$pars);
        $old_participant_columns=array();
        while ($line=pdo_fetch_assoc($result)) {
            $old_participant_columns[]=$line['COLUMN_NAME'];
        }

        $old_database_version='';
        if (in_array(table('options'),$old_tables)) {
            $query="SELECT option_value
                    FROM ".$old_database.".".table('options')."
                    WHERE option_name='database_version'";
            $line=orsee_query($query);
            if (isset($line['option_value'])) {
                $old_database_version=$line['option_value'];
            }
        }
        $detected_old_version='';
        if ($old_database_version) {
            $detected_old_version='orsee3';
        } elseif (in_array('deleted',$old_participant_columns) && in_array('excluded',$old_participant_columns)) {
            $detected_old_version='orsee2';
        }
        if ($detected_old_version && $detected_old_version!=$old_version) {
            if ($detected_old_version==='orsee2') {
                show_message('The selected source database looks like versions <3.0, but you selected versions 3.0.0 to 3.3.x. Please check source database and selected import version.','warning');
            } else {
                show_message('The selected database looks like versions 3.0.0 to 3.3.x, but you selected versions <3.0. Please check source database and selected import version.','warning');
            }
        } elseif (!$detected_old_version) {
            show_message('The selected database does not look like an ORSEE 2 or ORSEE 3 database. Please check the selected database.','warning');
        }

        /////////////////////////
        if ($old_version=="orsee2") {
            if (isset($_REQUEST['submit1'])) {
                echo '<FORM action="'.thisdoc().'" method="POST">'.csrf__field();
                echo '<INPUT type="hidden" name="old_version" value="'.$old_version_html.'">';
                echo '<INPUT type="hidden" name="old_database" value="'.$old_database_html.'">';
                echo '<div class="orsee-panel"><div class="orsee-form-shell">';

                echo '<div class="field">
                            <div class="control">Do you want to import
                            <UL><LI><B>all data</B> (admins, admin types, language items, files, plus all what\'s listed below)</LI>
                                <LI>or only <B>participation data</B> (participants, experiments, sessions, participations, lab bookings, admin log, cron log, participant log)?</LI>
                            </UL>
                            </div>
                        </div>';
                echo '<div class="field has-text-centered"><div class="control"><span class="select is-primary"><SELECT name="import_type">
                            <OPTION value="all">all data</OPTION>
                            <OPTION value="participation">participation data</OPTION>
                            </SELECT></span></div></div>';
                echo '<div class="field"><hr class="orsee-option-divider"></div>';

                $statuses=participant_status__get_statuses();
                echo '<div class="field"><div class="control">There are '.count($statuses).' participant statuses defined in this system.<BR>
                            Please select how previous participant properties should be assigned to these states.
                            </div></div>';
                echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Previous participant status</div><div class="orsee-table-cell">Map to current status</div></div>';
                $prev_statuses=array('n_n'=>array('not deleted, not excluded',1),
                                    'y_n'=>array('deleted, not excluded',2),
                                    'y_y'=>array('deleted and excluded',3));
                foreach ($prev_statuses as $ps=>$arr) {
                    echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Previous participant status">'.$arr[0].'</div>
                            <div class="orsee-table-cell" data-label="Map to current status"><span class="select is-primary"><SELECT name="status_'.$ps.'">';
                    foreach ($statuses as $status) {
                        echo '<OPTION value="'.htmlspecialchars($status['status_id'],ENT_QUOTES,'UTF-8').'"';
                        if ($status['status_id']==$arr[1]) {
                            echo ' SELECTED';
                        }
                        echo '>'.htmlspecialchars($status['name'],ENT_QUOTES,'UTF-8').'</OPTION>';
                    }
                    echo '</SELECT></span></div></div>';
                }
                echo '</div></div>';
                echo '<div class="field"><hr class="orsee-option-divider"></div>';

                $pstatuses=expregister__get_participation_statuses();
                echo '<div class="field"><div class="control">There are '.count($pstatuses).' experiment participation statuses defined in this system.<BR>
                            Please select how previous participation states should be assigned to these states.
                         </div></div>';
                echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Previous participation state</div><div class="orsee-table-cell">Map to current participation status</div></div>';
                $prev_pstatuses=array('n_n'=>array('not shownup, not participated',3),
                                    'y_n'=>array('shownup, not participated',2),
                                    'y_y'=>array('shownup and participated',1));
                foreach ($prev_pstatuses as $ps=>$arr) {
                    echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Previous participation state">'.$arr[0].'</div>
                            <div class="orsee-table-cell" data-label="Map to current participation status"><span class="select is-primary"><SELECT name="pstatus_'.$ps.'">';
                    foreach ($pstatuses as $status) {
                        echo '<OPTION value="'.htmlspecialchars($status['pstatus_id'],ENT_QUOTES,'UTF-8').'"';
                        if ($status['pstatus_id']==$arr[1]) {
                            echo ' SELECTED';
                        }
                        echo '>'.htmlspecialchars($status['internal_name'],ENT_QUOTES,'UTF-8').'</OPTION>';
                    }
                    echo '</SELECT></span></div></div>';
                }
                echo '</div></div>';
                echo '<div class="field"><hr class="orsee-option-divider"></div>';

                $old_subpools=array();
                $query="SELECT subpool_id, subpool_name, subpool_description
                        FROM ".$old_database.".".table('subpools')."
                        ORDER BY subpool_id";
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    $old_subpools[$line['subpool_id']]=$line;
                }
                $new_subpools=subpools__get_subpools();
                reset($new_subpools);
                $default_subpool_id=key($new_subpools);
                echo '<div class="field"><div class="control">Please map old subpools to the subpools configured in the current system.</div></div>';
                echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Old subpool</div><div class="orsee-table-cell">Map to current subpool</div></div>';
                foreach ($old_subpools as $old_subpool) {
                    $selected_subpool_id=(isset($new_subpools[$old_subpool['subpool_id']]) ? $old_subpool['subpool_id'] : $default_subpool_id);
                    echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Old subpool">'.htmlspecialchars($old_subpool['subpool_id'].' - '.$old_subpool['subpool_name'],ENT_QUOTES,'UTF-8').'</div>
                            <div class="orsee-table-cell" data-label="Map to current subpool"><span class="select is-primary"><SELECT name="map_subpool_'.htmlspecialchars($old_subpool['subpool_id'],ENT_QUOTES,'UTF-8').'">';
                    foreach ($new_subpools as $new_subpool) {
                        echo '<OPTION value="'.htmlspecialchars($new_subpool['subpool_id'],ENT_QUOTES,'UTF-8').'"';
                        if ($new_subpool['subpool_id']==$selected_subpool_id) {
                            echo ' SELECTED';
                        }
                        echo '>'.htmlspecialchars($new_subpool['subpool_id'].' - '.$new_subpool['subpool_name'],ENT_QUOTES,'UTF-8').'</OPTION>';
                    }
                    echo '</SELECT></span></div></div>';
                }
                echo '</div></div>';
                echo '<div class="field"><hr class="orsee-option-divider"></div>';

                $old_exptypes=array();
                $query="SELECT exptype_id, exptype_name, exptype_description
                        FROM ".$old_database.".".table('experiment_types')."
                        ORDER BY exptype_id";
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    $old_exptypes[$line['exptype_id']]=$line;
                }
                $new_exptypes=load_external_experiment_types("",false);
                reset($new_exptypes);
                $default_exptype_id=key($new_exptypes);
                echo '<div class="field"><div class="control">Please map old experiment types to the experiment types configured in the current system.</div></div>';
                echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Old experiment type</div><div class="orsee-table-cell">Map to current experiment type</div></div>';
                foreach ($old_exptypes as $old_exptype) {
                    $selected_exptype_id=(isset($new_exptypes[$old_exptype['exptype_id']]) ? $old_exptype['exptype_id'] : $default_exptype_id);
                    echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Old experiment type">'.htmlspecialchars($old_exptype['exptype_id'].' - '.$old_exptype['exptype_name'],ENT_QUOTES,'UTF-8').'</div>
                            <div class="orsee-table-cell" data-label="Map to current experiment type"><span class="select is-primary"><SELECT name="map_exptype_'.htmlspecialchars($old_exptype['exptype_id'],ENT_QUOTES,'UTF-8').'">';
                    foreach ($new_exptypes as $new_exptype) {
                        echo '<OPTION value="'.htmlspecialchars($new_exptype['exptype_id'],ENT_QUOTES,'UTF-8').'"';
                        if ($new_exptype['exptype_id']==$selected_exptype_id) {
                            echo ' SELECTED';
                        }
                        echo '>'.htmlspecialchars($new_exptype['exptype_id'].' - '.$new_exptype['exptype_name'],ENT_QUOTES,'UTF-8').'</OPTION>';
                    }
                    echo '</SELECT></span></div></div>';
                }
                echo '</div></div>';
                echo '<div class="field"><hr class="orsee-option-divider"></div>';

                $old_fields=array();
                $pars=array(':dbname'=>$old_database);
                $query="SELECT `COLUMN_NAME`
                FROM `INFORMATION_SCHEMA`.`COLUMNS`
                WHERE `TABLE_SCHEMA`= :dbname
                AND `TABLE_NAME`='or_participants'";
                $result=or_query($query,$pars);
                while ($line=pdo_fetch_assoc($result)) {
                    $old_fields[]=$line['COLUMN_NAME'];
                }
                $unset_in_old=array('participant_id','participant_id_crypt','creation_time','subpool_id',
                    'number_reg','number_noshowup','language','remarks','rules_signed','deleted','excluded','subscriptions');
                $old_fields=or_array_delete_values($old_fields,$unset_in_old);
                $old_has_email=in_array('email',$old_fields,true);
                if ($old_has_email) {
                    $old_fields=or_array_delete_values($old_fields,array('email'));
                }

                $new_fields=participant__userdefined_columns();
                if (!$old_has_email) {
                    $new_fields['email']=array('Type'=>'-','fieldtype'=>'email');
                }
                $query="SELECT * FROM ".table('profile_fields');
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    if (isset($new_fields[$line['mysql_column_name']])) {
                        $new_fields[$line['mysql_column_name']]['fieldtype']=$line['type'];
                    }
                }

                echo '<div class="field"><div class="control">
                        The following profile fields are defined in the current (new) system.
                        Please select from which column in the old system the data should be imported.
                        </div></div>';
                echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Profile field</div><div class="orsee-table-cell">Column type</div>
                                <div class="orsee-table-cell">Form field type</div><div class="orsee-table-cell">Import from '.$old_database_html.'</div></div>';
                foreach ($new_fields as $field=>$f) {
                    echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Profile field">'.htmlspecialchars($field,ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Column type">'.htmlspecialchars($f['Type'],ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Form field type">';
                    if (isset($f['fieldtype'])) {
                        echo htmlspecialchars($f['fieldtype'],ENT_QUOTES,'UTF-8');
                    } else {
                        echo '???';
                    }
                    echo '</div><div class="orsee-table-cell" data-label="Import from '.$old_database_html.'">';
                    echo '<span class="select is-primary"><SELECT name="map_'.htmlspecialchars($field,ENT_QUOTES,'UTF-8').'">';
                    echo '<OPTION value="">Don\'t import</OPTION>';
                    foreach ($old_fields as $of) {
                        echo '<OPTION value="'.htmlspecialchars($of,ENT_QUOTES,'UTF-8').'"';
                        if ($of==$field) {
                            echo ' SELECTED';
                        }
                        echo '>'.htmlspecialchars($of,ENT_QUOTES,'UTF-8').'</OPTION>';
                    }
                    echo '</SELECT></span>';
                    echo '</div></div>';
                }
                echo '</div></div>';

                echo '<div class="field"><hr class="orsee-option-divider"></div>';

                echo '<div class="field"><div class="control">Do you want to create new (more secure) tokens for participant URLs?
                            <UL>
                            <LI>The disadvantage of this is that the old URL\'s which participants used to access their
                            ORSEE enrolment page won\'t work anymore.<BR>The new URLs (with the newly created tokens)
                            will be included in any email sent out by the system. </LI>
                            <LI>If you plan to migrate to a username/password authentication for participants in the new system,<BR>
                            you could also leave the tokens as they are, because once fully migrated they will be irrelevant for access.</LI>
                            </UL>
                            </div></div>';
                echo '<div class="field has-text-centered"><div class="control"><span class="select is-primary"><SELECT name="replace_tokens">
                            <OPTION value="n">Keep old tokens</OPTION>
                            <OPTION value="y">Replace tokens</OPTION>
                            </SELECT></span></div></div>';

                echo '<div class="field orsee-form-actions has-text-centered">
                        <INPUT type="submit" class="button orsee-btn" name="submit2" value="Next">
                    </div>';
                echo '</div></div>';
                echo '</FORM>';
            }

            if (isset($_REQUEST['submit2'])) {
                $code='';
                $code.='$old_db_name='.var_export($old_database,true).';'."\n";
                $code.=''."\n";
                $code.='// mapping of participant statuses'."\n";
                $code.='// participant_status_mapping[deleted y/n][excluded y/n]=status_id'."\n";
                $code.='$participant_status_mapping=array();'."\n";
                $code.='$participant_status_mapping["n"]["n"]='.var_export($_REQUEST['status_n_n'],true).';'."\n";
                $code.='$participant_status_mapping["n"]["y"]='.var_export($_REQUEST['status_y_y'],true).';'."\n";
                $code.='$participant_status_mapping["y"]["n"]='.var_export($_REQUEST['status_y_n'],true).';'."\n";
                $code.='$participant_status_mapping["y"]["y"]='.var_export($_REQUEST['status_y_y'],true).';'."\n";
                $code.=''."\n";
                $code.='// mapping of participation statuses'."\n";
                $code.='// participation_mapping[shownup y/n][participated y/n]=pstatus_id'."\n";
                $code.='$participation_mapping=array();'."\n";
                $code.='$participation_mapping["n"]["n"]='.var_export($_REQUEST['pstatus_n_n'],true).';'."\n";
                $code.='$participation_mapping["n"]["y"]='.var_export($_REQUEST['pstatus_n_n'],true).';'."\n";
                $code.='$participation_mapping["y"]["n"]='.var_export($_REQUEST['pstatus_y_n'],true).';'."\n";
                $code.='$participation_mapping["y"]["y"]='.var_export($_REQUEST['pstatus_y_y'],true).';'."\n";
                $code.=''."\n";
                $code.='// mapping from old participant profile form to new form'."\n";
                $code.='// empty value for new column name implies no import'."\n";
                $code.='// pform_mapping[old column name]=new column name'."\n";
                $code.='$pform_mapping=array();'."\n";
                $new_fields=participant__userdefined_columns();
                if (isset($_REQUEST['map_email'])) {
                    $new_fields['email']=array();
                }
                foreach ($new_fields as $field=>$f) {
                    if (isset($_REQUEST['map_'.$field]) && $_REQUEST['map_'.$field]) {
                        $code.='$pform_mapping['.var_export($_REQUEST['map_'.$field],true).']='.var_export($field,true).';'."\n";
                    }
                }
                $code.=''."\n";
                $code.='// mappings from old IDs to target IDs'."\n";
                $code.='$subpool_mapping=array();'."\n";
                $query="SELECT subpool_id
                        FROM ".$old_database.".".table('subpools')."
                        ORDER BY subpool_id";
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    $code.='$subpool_mapping['.var_export($line['subpool_id'],true).']='.var_export($_REQUEST['map_subpool_'.$line['subpool_id']],true).';'."\n";
                }
                $code.=''."\n";
                $code.='$experiment_type_mapping=array();'."\n";
                $query="SELECT exptype_id
                        FROM ".$old_database.".".table('experiment_types')."
                        ORDER BY exptype_id";
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    $code.='$experiment_type_mapping['.var_export($line['exptype_id'],true).']='.var_export($_REQUEST['map_exptype_'.$line['exptype_id']],true).';'."\n";
                }
                $code.=''."\n";
                $code.='// other settings'."\n";
                $import_type=(isset($_REQUEST['import_type']) && in_array($_REQUEST['import_type'],array('all','participation'),true)) ? $_REQUEST['import_type'] : 'all';
                $replace_tokens=(isset($_REQUEST['replace_tokens']) && $_REQUEST['replace_tokens']==='y') ? 'y' : 'n';
                $code.='$import_type='.var_export($import_type,true).';'."\n";
                $code.='$replace_tokens='.var_export($replace_tokens,true).';'."\n";
                $code.=''."\n";
                $code.=''."\n";

                echo '<div class="orsee-panel"><div class="orsee-form-shell">';
                echo '<div class="field"><div class="control">Below you find the code to copy over to install/data_import/data_import_orsee2.php.</div></div>';
                echo '<div class="field"><div class="control"><TEXTAREA class="textarea is-primary orsee-textarea" rows=40 cols=80>'.htmlspecialchars($code,ENT_QUOTES,'UTF-8').'</TEXTAREA></div></div>';
                echo '</div></div>';
            }
        }

        if ($old_version=="orsee3") {
            if (isset($_REQUEST['submit1'])) {
                echo '<FORM action="'.thisdoc().'" method="POST">'.csrf__field();
                echo '<INPUT type="hidden" name="old_version" value="'.$old_version_html.'">';
                echo '<INPUT type="hidden" name="old_database" value="'.$old_database_html.'">';
                echo '<div class="orsee-panel"><div class="orsee-form-shell">';

                echo '<div class="field">
                            <div class="control orsee-richtext">
                                ORSEE 3 imports can be prepared in two modes. 
                                
                                <UL>
                                    <LI><B>full bootstrap</B>
                                        <UL>
                                            <LI><B>Use:</B> Setup a fresh ORSEE system with most info from old system, to then finish configuration. This can be the full migration already, or a temporary setup while the old system keeps running, where the full migration is then completed with an operational import (see below).</LI>
                                            <LI><B>Precondition:</B> Fresh ORSEE installation with target languages created, profile/MySQL participant columns, and public/admin menu configured.</LI>
                                            <LI><B>Imports:</B> experiment types, subpools, participant statuses, participation statuses, budgets, payment types, laboratories, event and upload categories, mailboxes, admin types (if not exist yet), admins (if not exist yet), language symbols, datetime formats, FAQs, public content mapped to menu items, participants, experiments, sessions, participations, events, uploads, queries, logs, and optional email archive.</LI>
                                            <LI><B>Does not import:</B> options, profile field definitions, profile form layouts, menu configuration.</LI>
                                        </UL>
                                    </LI>
                                    <LI><B>operational</B>
                                        <UL>
                                            <LI><B>Use:</B> Refresh data from old running ORSEE3 system when switching over (in case you used the old system until everything is configured).</LI> 
                                            <LI><B>Preconditions:</B> New ORSEE is fully configured. This includes profile fields, layouts, menus, public content, languages, subpools, statuses, experiment types, laboratories, payment types, budgets, mailboxes, and all relevant settings.</LI>
                                            <LI><B>Imports/refreshes:</B> operational data, such as participants, experiments, sessions, participations, admins (if not exist yet), participant log, events, uploads, queries, admin log, cron log, experiment-specific mail texts, and optional email archive. Configurations are mapped to existing target data.</LI>
                                            <LI><B>Does not import:</B> options, public content, FAQs, profile field definitions, profile form layouts, menu configuration, language symbols, datetime formats. Does not import but maps experiment types, subpools, participant statuses, participation statuses, payment types, laboratories, event and upload categories. Budgets and mailboxes can be mapped or copied.</LI>
                                        </UL>
                                    </LI>
                                </UL>
                            </div>
                        </div>';
                echo '<div class="field has-text-centered"><label class="label">Import mode</label><div class="control"><span class="select is-primary"><SELECT name="import_mode">
                            <OPTION value="full_bootstrap">Full bootstrap</OPTION>
                            <OPTION value="operational">Operational</OPTION>
                            </SELECT></span></div></div>';

                echo '<div class="field orsee-form-actions has-text-centered">
                        <INPUT type="submit" class="button orsee-btn" name="submit_mode" value="Next">
                    </div>';
                echo '</div></div>';
                echo '</FORM>';
            }

            if (isset($_REQUEST['submit_mode'])) {
                $import_mode=(isset($_REQUEST['import_mode']) && in_array($_REQUEST['import_mode'],array('full_bootstrap','operational'),true)) ? $_REQUEST['import_mode'] : 'operational';

                echo '<FORM action="'.thisdoc().'" method="POST">'.csrf__field();
                echo '<INPUT type="hidden" name="old_version" value="'.$old_version_html.'">';
                echo '<INPUT type="hidden" name="old_database" value="'.$old_database_html.'">';
                echo '<INPUT type="hidden" name="import_mode" value="'.htmlspecialchars($import_mode,ENT_QUOTES,'UTF-8').'">';
                echo '<div class="orsee-panel"><div class="orsee-form-shell">';
                echo '<div class="field"><div class="control">Selected ORSEE 3 import mode: <B>'.htmlspecialchars($import_mode,ENT_QUOTES,'UTF-8').'</B>.</div></div>';

                $lang_fixed_columns=array('lang_id','enabled','order_number','content_type','content_name');
                $old_lang_columns=array();
                $query="SHOW COLUMNS FROM ".$old_database.".".table('lang');
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    if (!in_array($line['Field'],$lang_fixed_columns,true)) {
                        $old_lang_columns[]=$line['Field'];
                    }
                }
                $new_lang_columns=array();
                $query="SHOW COLUMNS FROM ".table('lang');
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    if (!in_array($line['Field'],$lang_fixed_columns,true)) {
                        $new_lang_columns[]=$line['Field'];
                    }
                }
                $old_lang_display_column=in_array(lang('lang'),$old_lang_columns,true) ? lang('lang') : (count($old_lang_columns)>0 ? $old_lang_columns[0] : 'content_name');
                $missing_languages=array_diff($old_lang_columns,$new_lang_columns);
                if (count($missing_languages)>0) {
                    echo '<div class="field"><div class="control"><B>Note:</B> The old database contains language columns not present in the target database: '.htmlspecialchars(implode(', ',$missing_languages),ENT_QUOTES,'UTF-8').'. Values for these languages cannot be imported.</div></div>';
                }

                $old_fields=array();
                $pars=array(':dbname'=>$old_database);
                $query="SELECT `COLUMN_NAME`
                        FROM `INFORMATION_SCHEMA`.`COLUMNS`
                        WHERE `TABLE_SCHEMA`= :dbname
                        AND `TABLE_NAME`='or_participants'";
                $result=or_query($query,$pars);
                while ($line=pdo_fetch_assoc($result)) {
                    $old_fields[]=$line['COLUMN_NAME'];
                }
                $old_fields=or_array_delete_values($old_fields,array_keys(participant__nonuserdefined_columns()));

                $new_fields=participant__userdefined_columns();
                $query="SELECT * FROM ".table('profile_fields');
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    if (isset($new_fields[$line['mysql_column_name']])) {
                        $new_fields[$line['mysql_column_name']]['fieldtype']=$line['type'];
                    }
                }

                echo '<div class="field"><hr class="orsee-option-divider"></div>';
                echo '<div class="field"><div class="control">Please map participant profile fields from the old system to the profile fields configured in the target system.</div></div>';
                echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Target profile field</div><div class="orsee-table-cell">Column type</div><div class="orsee-table-cell">Field type</div><div class="orsee-table-cell">Import from '.$old_database_html.'</div></div>';
                foreach ($new_fields as $field=>$f) {
                    echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Target profile field">'.htmlspecialchars($field,ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Column type">'.htmlspecialchars($f['Type'],ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Field type">';
                    echo isset($f['fieldtype']) ? htmlspecialchars($f['fieldtype'],ENT_QUOTES,'UTF-8') : '???';
                    echo '</div><div class="orsee-table-cell" data-label="Import from '.$old_database_html.'"><span class="select is-primary"><SELECT name="map_pform_'.htmlspecialchars($field,ENT_QUOTES,'UTF-8').'">';
                    echo '<OPTION value="">Don\'t import</OPTION>';
                    foreach ($old_fields as $of) {
                        echo '<OPTION value="'.htmlspecialchars($of,ENT_QUOTES,'UTF-8').'"';
                        if ($of==$field) {
                            echo ' SELECTED';
                        }
                        echo '>'.htmlspecialchars($of,ENT_QUOTES,'UTF-8').'</OPTION>';
                    }
                    echo '</SELECT></span></div></div>';
                }
                echo '</div></div>';

                $old_admins=array();
                $query="SELECT admin_id, adminname, fname, lname FROM ".$old_database.".".table('admin')." ORDER BY admin_id";
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    $old_admins[$line['admin_id']]=$line;
                }
                $new_admins=array();
                $new_adminnames=array();
                $query="SELECT admin_id, adminname, fname, lname FROM ".table('admin')." ORDER BY admin_id";
                $result=or_query($query);
                while ($line=pdo_fetch_assoc($result)) {
                    $new_admins[$line['admin_id']]=$line;
                    $new_adminnames[$line['adminname']]=$line['admin_id'];
                }
                $admin_conflicts=array();
                $next_admin_id=1;
                $all_admin_ids=array_merge(array_keys($new_admins),array_keys($old_admins));
                if (count($all_admin_ids)>0) {
                    $next_admin_id=max($all_admin_ids)+1;
                }
                $reserved_adminnames=$new_adminnames;
                foreach ($old_admins as $old_admin_id=>$old_admin) {
                    $id_conflict=(isset($new_admins[$old_admin_id]) && $new_admins[$old_admin_id]['adminname']!==$old_admin['adminname']);
                    $name_conflict=(isset($new_adminnames[$old_admin['adminname']]) && (string)$new_adminnames[$old_admin['adminname']]!==(string)$old_admin_id);
                    if (!$id_conflict && !$name_conflict) {
                        continue;
                    }
                    $new_adminname=$old_admin['adminname'];
                    if ($name_conflict) {
                        $base=substr($old_admin['adminname'],0,11).'_imported';
                        $new_adminname=substr($base,0,20);
                        $suffix=2;
                        while (isset($reserved_adminnames[$new_adminname])) {
                            $suffix_text='_'.$suffix;
                            $new_adminname=substr($base,0,20-strlen($suffix_text)).$suffix_text;
                            $suffix++;
                        }
                    }
                    $reserved_adminnames[$new_adminname]=$next_admin_id;
                    $admin_conflicts[$old_admin_id]=array(
                        'admin'=>$old_admin,
                        'id_conflict'=>$id_conflict,
                        'name_conflict'=>$name_conflict,
                        'new_admin_id'=>$next_admin_id,
                        'new_adminname'=>$new_adminname
                    );
                    $next_admin_id++;
                }
                echo '<div class="field"><hr class="orsee-option-divider"></div>';
                if (count($admin_conflicts)>0) {
                    echo '<div class="field"><div class="control">Non-conflicting admin accounts will be imported automatically. Please resolve the following admin account conflicts.</div></div>';
                    echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                    echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Old admin</div><div class="orsee-table-cell">Conflict</div><div class="orsee-table-cell">Action</div></div>';
                    foreach ($admin_conflicts as $old_admin_id=>$conflict) {
                        $old_admin_label=$conflict['admin']['admin_id'].' - '.$conflict['admin']['adminname'].' ('.$conflict['admin']['fname'].' '.$conflict['admin']['lname'].')';
                        $old_admin_label_html=htmlspecialchars($old_admin_label,ENT_QUOTES,'UTF-8');
                        $conflict_text=array();
                        if ($conflict['id_conflict']) {
                            $conflict_text[]='admin_id exists';
                        }
                        if ($conflict['name_conflict']) {
                            $conflict_text[]='adminname exists';
                        }
                        echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Old admin">'.$old_admin_label_html.'</div><div class="orsee-table-cell" data-label="Conflict">'.htmlspecialchars(implode(', ',$conflict_text),ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Action"><span class="select is-primary"><SELECT name="admin_action_'.htmlspecialchars($old_admin_id,ENT_QUOTES,'UTF-8').'">';
                        echo '<OPTION value="'.htmlspecialchars('new:'.$conflict['new_admin_id'].':'.$conflict['new_adminname'],ENT_QUOTES,'UTF-8').'"';
                        if (!$conflict['name_conflict']) {
                            echo ' SELECTED';
                        }
                        echo '>Import as new admin account: '.htmlspecialchars($conflict['new_admin_id'].' - '.$conflict['new_adminname'],ENT_QUOTES,'UTF-8').'</OPTION>';
                        foreach ($new_admins as $new_admin_id=>$new_admin) {
                            $new_admin_label=$new_admin['admin_id'].' - '.$new_admin['adminname'].' ('.$new_admin['fname'].' '.$new_admin['lname'].')';
                            echo '<OPTION value="'.htmlspecialchars('map:'.$new_admin_id,ENT_QUOTES,'UTF-8').'"';
                            if ($conflict['name_conflict'] && (string)$new_admin_id===(string)$new_adminnames[$conflict['admin']['adminname']]) {
                                echo ' SELECTED';
                            }
                            echo '>Map to existing admin: '.htmlspecialchars($new_admin_label,ENT_QUOTES,'UTF-8').'</OPTION>';
                        }
                        echo '</SELECT></span></div></div>';
                    }
                    echo '</div></div>';
                } else {
                    echo '<div class="field"><div class="control">No admin account conflicts found. Non-conflicting admin accounts will be imported automatically.</div></div>';
                }

                if ($import_mode==='full_bootstrap') {
                    $old_public_content=array();
                    $query="SELECT content_name
                            FROM ".$old_database.".".table('lang')."
                            WHERE content_type='public_content'
                            ORDER BY content_name";
                    $result=or_query($query);
                    while ($line=pdo_fetch_assoc($result)) {
                        $old_public_content[$line['content_name']]=$line['content_name'];
                    }
                    $target_public_content=array();
                    $target_public_content['error_temporary_disabled']='error_temporary_disabled';
                    foreach (array('public','admin') as $menu_area_name) {
                        $menu_config=html__menu_load_config($menu_area_name);
                        if (!isset($menu_config['items']) || !is_array($menu_config['items'])) {
                            continue;
                        }
                        foreach ($menu_config['items'] as $menu_item) {
                            if (!is_array($menu_item) || !isset($menu_item['richtext']) || $menu_item['richtext']!=='y' || !isset($menu_item['content_name']) || trim((string)$menu_item['content_name'])==='') {
                                continue;
                            }
                            $menu_label=html__menu_text_from_lang_map((isset($menu_item['menu_term_lang']) ? $menu_item['menu_term_lang'] : array()),'');
                            $target_public_content[$menu_item['content_name']]=$menu_item['content_name'].($menu_label ? ' - '.$menu_label : '');
                        }
                    }
                    echo '<div class="field"><hr class="orsee-option-divider"></div>';
                    echo '<div class="field"><div class="control">Please map old public content items to richtext items in the target system.</div></div>';
                    echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                    echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Old public content</div><div class="orsee-table-cell">Target richtext item</div></div>';
                    foreach ($old_public_content as $old_content_name=>$old_content_label) {
                        echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Old public content">'.htmlspecialchars($old_content_label,ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Target richtext item"><span class="select is-primary"><SELECT name="map_public_content_'.htmlspecialchars($old_content_name,ENT_QUOTES,'UTF-8').'">';
                        echo '<OPTION value="">Don\'t import</OPTION>';
                        foreach ($target_public_content as $new_content_name=>$new_content_label) {
                            echo '<OPTION value="'.htmlspecialchars($new_content_name,ENT_QUOTES,'UTF-8').'"';
                            if ($new_content_name==$old_content_name) {
                                echo ' SELECTED';
                            }
                            echo '>'.htmlspecialchars($new_content_label,ENT_QUOTES,'UTF-8').'</OPTION>';
                        }
                        echo '</SELECT></span></div></div>';
                    }
                    echo '</div></div>';
                }

                if ($import_mode==='operational') {
                    $mapping_sections=array();
                    $old_subpools=array();
                    $query="SELECT subpool_id, subpool_name FROM ".$old_database.".".table('subpools')." ORDER BY subpool_id";
                    $result=or_query($query);
                    while ($line=pdo_fetch_assoc($result)) {
                        $old_subpools[$line['subpool_id']]=$line['subpool_id'].' - '.$line['subpool_name'];
                    }
                    $new_subpools=array();
                    foreach (subpools__get_subpools() as $subpool_id=>$subpool) {
                        $new_subpools[$subpool_id]=$subpool_id.' - '.$subpool['subpool_name'];
                    }
                    $mapping_sections[]=array('request'=>'subpool','title'=>'Subpools','old'=>$old_subpools,'new'=>$new_subpools);

                    $old_participant_statuses=array();
                    $query="SELECT ps.status_id, lang.".$old_lang_display_column." AS name
                            FROM ".$old_database.".".table('participant_statuses')." AS ps
                            LEFT JOIN ".$old_database.".".table('lang')." AS lang ON lang.content_type='participant_status_name' AND lang.content_name=ps.status_id
                            ORDER BY ps.status_id";
                    $result=or_query($query);
                    while ($line=pdo_fetch_assoc($result)) {
                        $old_participant_statuses[$line['status_id']]=$line['status_id'].($line['name'] ? ' - '.$line['name'] : '');
                    }
                    $new_participant_statuses=array();
                    foreach (participant_status__get_statuses() as $status_id=>$status) {
                        $new_participant_statuses[$status_id]=$status_id.(isset($status['name']) ? ' - '.$status['name'] : '');
                    }
                    $mapping_sections[]=array('request'=>'participant_status','title'=>'Participant statuses','old'=>$old_participant_statuses,'new'=>$new_participant_statuses);

                    $old_participation_statuses=array();
                    $query="SELECT ps.pstatus_id, lang.".$old_lang_display_column." AS name
                            FROM ".$old_database.".".table('participation_statuses')." AS ps
                            LEFT JOIN ".$old_database.".".table('lang')." AS lang ON lang.content_type='participation_status_internal_name' AND lang.content_name=ps.pstatus_id
                            ORDER BY ps.pstatus_id";
                    $result=or_query($query);
                    while ($line=pdo_fetch_assoc($result)) {
                        $old_participation_statuses[$line['pstatus_id']]=$line['pstatus_id'].($line['name'] ? ' - '.$line['name'] : '');
                    }
                    $new_participation_statuses=array();
                    foreach (expregister__get_participation_statuses() as $pstatus_id=>$pstatus) {
                        $new_participation_statuses[$pstatus_id]=$pstatus_id.(isset($pstatus['internal_name']) ? ' - '.$pstatus['internal_name'] : '');
                    }
                    $mapping_sections[]=array('request'=>'participation_status','title'=>'Participation statuses','old'=>$old_participation_statuses,'new'=>$new_participation_statuses);

                    $old_exptypes=array();
                    $query="SELECT exptype_id, exptype_name FROM ".$old_database.".".table('experiment_types')." ORDER BY exptype_id";
                    $result=or_query($query);
                    while ($line=pdo_fetch_assoc($result)) {
                        $old_exptypes[$line['exptype_id']]=$line['exptype_id'].' - '.$line['exptype_name'];
                    }
                    $new_exptypes=array();
                    foreach (load_external_experiment_types("",false) as $exptype_id=>$exptype) {
                        $new_exptypes[$exptype_id]=$exptype_id.' - '.$exptype['exptype_name'];
                    }
                    $mapping_sections[]=array('request'=>'experiment_type','title'=>'Experiment types','old'=>$old_exptypes,'new'=>$new_exptypes);

                    foreach (array(
                        array('request'=>'experiment_class','title'=>'Experiment classes','content_type'=>'experimentclass'),
                        array('request'=>'laboratory','title'=>'Laboratories','content_type'=>'laboratory'),
                        array('request'=>'event_category','title'=>'Event categories','content_type'=>'events_category'),
                        array('request'=>'file_upload_category','title'=>'File upload categories','content_type'=>'file_upload_category'),
                        array('request'=>'payment_type','title'=>'Payment types','content_type'=>'payments_type')
                    ) as $cat_section) {
                        $old_items=array();
                        $query="SELECT content_name, ".$old_lang_display_column." AS content_value
                                FROM ".$old_database.".".table('lang')."
                                WHERE content_type='".$cat_section['content_type']."'
                                ORDER BY order_number, content_name";
                        $result=or_query($query);
                        while ($line=pdo_fetch_assoc($result)) {
                            $item_text=$line['content_value'];
                            if ($cat_section['request']==='laboratory') {
                                $item_text=trim(strtok((string)$line['content_value'],"\r\n"));
                            }
                            $old_items[$line['content_name']]=($item_text ? $item_text : $line['content_name']);
                        }
                        $new_items=lang__load_lang_cat($cat_section['content_type'],'','fixed_order');
                        foreach ($new_items as $item_id=>$item_text) {
                            if ($cat_section['request']==='laboratory') {
                                $item_text=trim(strtok((string)$item_text,"\r\n"));
                            } else {
                                $item_text=$item_id.' - '.$item_text;
                            }
                            $new_items[$item_id]=$item_text;
                        }
                        $mapping_section=array('request'=>$cat_section['request'],'title'=>$cat_section['title'],'old'=>$old_items,'new'=>$new_items);
                        if ($cat_section['request']==='experiment_class') {
                            $mapping_section['import_missing']=true;
                        }
                        $mapping_sections[]=$mapping_section;
                    }

                    foreach ($mapping_sections as $section) {
                        echo '<div class="field"><hr class="orsee-option-divider"></div>';
                        echo '<div class="field"><div class="control">Please map '.htmlspecialchars($section['title'],ENT_QUOTES,'UTF-8').' from the old system to the target system.</div></div>';
                        echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                        echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Old '.htmlspecialchars($section['title'],ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell">Target '.htmlspecialchars($section['title'],ENT_QUOTES,'UTF-8').'</div></div>';
                        foreach ($section['old'] as $old_id=>$old_label) {
                            echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Old '.htmlspecialchars($section['title'],ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($old_label,ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Target '.htmlspecialchars($section['title'],ENT_QUOTES,'UTF-8').'"><span class="select is-primary"><SELECT name="map_'.htmlspecialchars($section['request'],ENT_QUOTES,'UTF-8').'_'.htmlspecialchars($old_id,ENT_QUOTES,'UTF-8').'">';
                            if (isset($section['import_missing']) && !isset($section['new'][$old_id])) {
                                echo '<OPTION value="" SELECTED>Create new and import: '.htmlspecialchars($old_label,ENT_QUOTES,'UTF-8').'</OPTION>';
                            }
                            foreach ($section['new'] as $new_id=>$new_label) {
                                echo '<OPTION value="'.htmlspecialchars($new_id,ENT_QUOTES,'UTF-8').'"';
                                if ((string)$new_id===(string)$old_id) {
                                    echo ' SELECTED';
                                }
                                echo '>'.htmlspecialchars($new_label,ENT_QUOTES,'UTF-8').'</OPTION>';
                            }
                            echo '</SELECT></span></div></div>';
                        }
                        echo '</div></div>';
                    }

                    $old_budgets=array();
                    $query="SELECT budget_id, budget_name FROM ".$old_database.".".table('budgets')." ORDER BY budget_name";
                    $result=or_query($query);
                    while ($line=pdo_fetch_assoc($result)) {
                        $old_budgets[$line['budget_id']]=$line;
                    }
                    $new_budgets=payments__load_budgets(true);
                    $next_budget_id=1;
                    $all_budget_ids=array_merge(array_keys($old_budgets),array_keys($new_budgets));
                    if (count($all_budget_ids)>0) {
                        $next_budget_id=max($all_budget_ids)+1;
                    }
                    echo '<div class="field"><hr class="orsee-option-divider"></div>';
                    echo '<div class="field"><div class="control">Please map old budgets to target budgets or import them as new budgets. Budgets without target conflict will be imported automatically.</div></div>';
                    echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                    echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Old budget</div><div class="orsee-table-cell">Target action</div></div>';
                    foreach ($old_budgets as $old_budget_id=>$old_budget) {
                        $old_budget_label=$old_budget_id.' - '.$old_budget['budget_name'];
                        echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Old budget">'.htmlspecialchars($old_budget_label,ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Target action"><span class="select is-primary"><SELECT name="budget_action_'.htmlspecialchars($old_budget_id,ENT_QUOTES,'UTF-8').'">';
                        if (!isset($new_budgets[$old_budget_id])) {
                            echo '<OPTION value="" SELECTED>Import automatically: '.htmlspecialchars($old_budget_label,ENT_QUOTES,'UTF-8').'</OPTION>';
                        }
                        foreach ($new_budgets as $new_budget_id=>$new_budget) {
                            echo '<OPTION value="'.htmlspecialchars('map:'.$new_budget_id,ENT_QUOTES,'UTF-8').'"';
                            if ((string)$new_budget_id===(string)$old_budget_id) {
                                echo ' SELECTED';
                            }
                            echo '>Map to '.htmlspecialchars($new_budget_id.' - '.$new_budget['budget_name'],ENT_QUOTES,'UTF-8').'</OPTION>';
                        }
                        echo '<OPTION value="'.htmlspecialchars('new:'.$next_budget_id,ENT_QUOTES,'UTF-8').'"';
                        echo '>Import as new budget: '.htmlspecialchars($next_budget_id.' - '.$old_budget['budget_name'],ENT_QUOTES,'UTF-8').'</OPTION>';
                        echo '</SELECT></span></div></div>';
                        $next_budget_id++;
                    }
                    echo '</div></div>';

                    $old_mailboxes=array();
                    $query="SELECT content_name, ".$old_lang_display_column." AS content_value
                            FROM ".$old_database.".".table('lang')."
                            WHERE content_type='emails_mailbox'
                            ORDER BY order_number, content_name";
                    $result=or_query($query);
                    while ($line=pdo_fetch_assoc($result)) {
                        $old_mailboxes[$line['content_name']]=array(
                            'content_name'=>$line['content_name'],
                            'label'=>trim(strtok((string)$line['content_value'],"\r\n"))
                        );
                    }
                    $new_mailboxes=lang__load_lang_cat('emails_mailbox','','fixed_order');
                    $reserved_mailboxes=$new_mailboxes;
                    echo '<div class="field"><hr class="orsee-option-divider"></div>';
                    echo '<div class="field"><div class="control">Please map old email mailboxes to target mailboxes or import them as new mailboxes. Mailboxes without target conflict will be imported automatically.</div></div>';
                    echo '<div class="field"><div class="orsee-table orsee-table-tablet-2cols orsee-table-mobile">';
                    echo '<div class="orsee-table-row orsee-table-head"><div class="orsee-table-cell">Old mailbox</div><div class="orsee-table-cell">Target action</div></div>';
                    foreach ($old_mailboxes as $old_mailbox_id=>$old_mailbox) {
                        $old_mailbox_label=$old_mailbox_id.($old_mailbox['label'] ? ' - '.$old_mailbox['label'] : '');
                        $new_mailbox_id=$old_mailbox_id;
                        if (isset($new_mailboxes[$old_mailbox_id])) {
                            $base=substr(trim(str_replace(' ','_',$old_mailbox['label'])),0,11).'_imported';
                            $new_mailbox_id=substr($base,0,20);
                            $suffix=2;
                            while (isset($reserved_mailboxes[$new_mailbox_id])) {
                                $suffix_text='_'.$suffix;
                                $new_mailbox_id=substr($base,0,20-strlen($suffix_text)).$suffix_text;
                                $suffix++;
                            }
                        }
                        $reserved_mailboxes[$new_mailbox_id]=$old_mailbox['label'];
                        echo '<div class="orsee-table-row"><div class="orsee-table-cell" data-label="Old mailbox">'.htmlspecialchars($old_mailbox_label,ENT_QUOTES,'UTF-8').'</div><div class="orsee-table-cell" data-label="Target action"><span class="select is-primary"><SELECT name="mailbox_action_'.htmlspecialchars($old_mailbox_id,ENT_QUOTES,'UTF-8').'">';
                        if (!isset($new_mailboxes[$old_mailbox_id])) {
                            echo '<OPTION value="" SELECTED>Import automatically: '.htmlspecialchars($old_mailbox_label,ENT_QUOTES,'UTF-8').'</OPTION>';
                        }
                        foreach ($new_mailboxes as $target_mailbox_id=>$target_mailbox_label) {
                            echo '<OPTION value="'.htmlspecialchars('map:'.$target_mailbox_id,ENT_QUOTES,'UTF-8').'"';
                            if ((string)$target_mailbox_id===(string)$old_mailbox_id) {
                                echo ' SELECTED';
                            }
                            echo '>Map to '.htmlspecialchars($target_mailbox_id.' - '.trim(strtok((string)$target_mailbox_label,"\r\n")),ENT_QUOTES,'UTF-8').'</OPTION>';
                        }
                        echo '<OPTION value="'.htmlspecialchars('new:'.$new_mailbox_id,ENT_QUOTES,'UTF-8').'"';
                        echo '>Import as new mailbox: '.htmlspecialchars($new_mailbox_id,ENT_QUOTES,'UTF-8').'</OPTION>';
                        echo '</SELECT></span></div></div>';
                    }
                    echo '</div></div>';
                }

                echo '<div class="field"><hr class="orsee-option-divider"></div>';
                echo '<div class="field has-text-centered"><label class="label">Import emails for email module?</label><div class="control"><span class="select is-primary"><SELECT name="import_emails">
                            <OPTION value="n">No</OPTION>
                            <OPTION value="y">Yes</OPTION>
                            </SELECT></span></div></div>';
                echo '<div class="field orsee-form-actions has-text-centered">
                        <INPUT type="submit" class="button orsee-btn" name="submit2" value="Next">
                    </div>';
                echo '</div></div>';
                echo '</FORM>';
            }

            if (isset($_REQUEST['submit2'])) {
                $import_mode=(isset($_REQUEST['import_mode']) && in_array($_REQUEST['import_mode'],array('full_bootstrap','operational'),true)) ? $_REQUEST['import_mode'] : 'operational';
                $code='';
                $code.='$old_db_name='.var_export($old_database,true).';'."\n";
                $code.='$import_mode='.var_export($import_mode,true).';'."\n\n";

                $pform_code='';
                $new_fields=participant__userdefined_columns();
                foreach ($new_fields as $field=>$f) {
                    if (isset($_REQUEST['map_pform_'.$field]) && $_REQUEST['map_pform_'.$field]) {
                        $pform_code.='$pform_mapping['.var_export($_REQUEST['map_pform_'.$field],true).']='.var_export($field,true).';'."\n";
                    }
                }
                if ($pform_code) {
                    $code.='// mapping from old participant profile form to target form'."\n";
                    $code.='$pform_mapping=array();'."\n";
                    $code.=$pform_code."\n";
                }

                $admin_mapping_code='';
                $admin_import_new_code='';
                foreach ($_REQUEST as $request_key=>$request_value) {
                    if (substr($request_key,0,13)==='admin_action_' && $request_value!=='') {
                        $old_id=substr($request_key,13);
                        $parts=explode(':',$request_value,3);
                        if (count($parts)>=2 && $parts[0]==='map') {
                            $admin_mapping_code.='$admin_mapping['.var_export($old_id,true).']='.var_export($parts[1],true).';'."\n";
                        } elseif (count($parts)==3 && $parts[0]==='new') {
                            $admin_mapping_code.='$admin_mapping['.var_export($old_id,true).']='.var_export($parts[1],true).';'."\n";
                            $admin_import_new_code.='$admin_import_new['.var_export($old_id,true).']=array("admin_id"=>'.var_export($parts[1],true).',"adminname"=>'.var_export($parts[2],true).');'."\n";
                        }
                    }
                }
                if ($admin_mapping_code || $admin_import_new_code) {
                    $code.='// admin account conflict handling'."\n";
                    if ($admin_mapping_code) {
                        $code.='$admin_mapping=array();'."\n";
                        $code.=$admin_mapping_code;
                    }
                    if ($admin_import_new_code) {
                        $code.='$admin_import_new=array();'."\n";
                        $code.=$admin_import_new_code;
                    }
                    $code.="\n";
                }

                $mapping_vars=array('subpool','participant_status','participation_status','experiment_type','experiment_class','laboratory','event_category','file_upload_category','payment_type','budget','mailbox');
                foreach ($mapping_vars as $mapping_var) {
                    $mapping_code='';
                    $request_prefix='map_'.$mapping_var.'_';
                    foreach ($_REQUEST as $request_key=>$request_value) {
                        if (substr($request_key,0,strlen($request_prefix))===$request_prefix && $request_value!=='') {
                            $old_id=substr($request_key,strlen($request_prefix));
                            $mapping_code.='$'.$mapping_var.'_mapping['.var_export($old_id,true).']='.var_export($request_value,true).';'."\n";
                        }
                    }
                    if ($mapping_code) {
                        $code.='$'.$mapping_var.'_mapping=array();'."\n";
                        $code.=$mapping_code."\n";
                    }
                }

                $public_content_code='';
                if ($import_mode==='full_bootstrap') {
                    foreach ($_REQUEST as $request_key=>$request_value) {
                        if (substr($request_key,0,19)==='map_public_content_' && $request_value!=='') {
                            $old_id=substr($request_key,19);
                            $public_content_code.='$public_content_mapping['.var_export($old_id,true).']='.var_export($request_value,true).';'."\n";
                        }
                    }
                }
                if ($public_content_code) {
                    $code.='// public_content_mapping[old content_name]=target richtext content_name'."\n";
                    $code.='$public_content_mapping=array();'."\n";
                    $code.=$public_content_code."\n";
                }

                $budget_mapping_code='';
                $copy_budgets_code='';
                $mailbox_mapping_code='';
                $copy_mailboxes_code='';
                if ($import_mode==='operational') {
                    foreach ($_REQUEST as $request_key=>$request_value) {
                        if (substr($request_key,0,14)==='budget_action_') {
                            $old_id=substr($request_key,14);
                            $parts=explode(':',$request_value,2);
                            if (count($parts)==2) {
                                $budget_mapping_code.='$budget_mapping['.var_export($old_id,true).']='.var_export($parts[1],true).';'."\n";
                                if ($parts[0]==='new') {
                                    $copy_budgets_code.='$copy_budgets['.var_export($old_id,true).']='.var_export($parts[1],true).';'."\n";
                                }
                            }
                        } elseif (substr($request_key,0,15)==='mailbox_action_') {
                            $old_id=substr($request_key,15);
                            $parts=explode(':',$request_value,2);
                            if (count($parts)==2) {
                                $mailbox_mapping_code.='$mailbox_mapping['.var_export($old_id,true).']='.var_export($parts[1],true).';'."\n";
                                if ($parts[0]==='new') {
                                    $copy_mailboxes_code.='$copy_mailboxes['.var_export($old_id,true).']='.var_export($parts[1],true).';'."\n";
                                }
                            }
                        }
                    }
                }
                if ($budget_mapping_code || $copy_budgets_code || $mailbox_mapping_code || $copy_mailboxes_code) {
                    $code.='// copied objects are created/updated from the old database on every import run'."\n";
                    if ($budget_mapping_code) {
                        $code.='$budget_mapping=array();'."\n";
                        $code.=$budget_mapping_code;
                    }
                    if ($copy_budgets_code) {
                        $code.='$copy_budgets=array();'."\n";
                        $code.=$copy_budgets_code;
                    }
                    if ($mailbox_mapping_code) {
                        $code.='$mailbox_mapping=array();'."\n";
                        $code.=$mailbox_mapping_code;
                    }
                    if ($copy_mailboxes_code) {
                        $code.='$copy_mailboxes=array();'."\n";
                        $code.=$copy_mailboxes_code;
                    }
                    $code.="\n";
                }

                $import_emails=(isset($_REQUEST['import_emails']) && $_REQUEST['import_emails']==='y') ? 'y' : 'n';
                if ($import_emails==='y') {
                    $code.='// email archive import is optional because it can be large and sensitive'."\n";
                    $code.='$import_emails='.var_export($import_emails,true).';'."\n";
                }

                echo '<div class="orsee-panel"><div class="orsee-form-shell">';
                echo '<div class="field"><div class="control">Below you find the code to copy over to install/data_import/data_import_orsee3.php.</div></div>';
                echo '<div class="field"><div class="control"><TEXTAREA class="textarea is-primary orsee-textarea" rows=40 cols=80>'.htmlspecialchars($code,ENT_QUOTES,'UTF-8').'</TEXTAREA></div></div>';
                echo '</div></div>';
            }
        }
        /////////////////////////
    }
}
include("footer.php");

?>
