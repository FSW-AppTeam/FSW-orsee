<?php
// part of orsee. see orsee.org


$all_orsee_query_modules=array(
"statusids",
"pformtextfields",
"noshows",
"participations",
"activity",
"updaterequest",
"authmigration",
"subsubjectpool",
"subscriptions",
"interfacelanguage",
"pformselects",
"experimentclasses",
"experimenters",
"experimentsparticipated",
"experimentsassigned",
"randsubset",
"brackets"
);


function query__get_query_form_prototypes($hide_modules=array(),$experiment_id="",$status_query="") {
    global $lang, $settings, $all_orsee_query_modules;
    $formfields=participantform__load();
    if ($settings['subject_authentication']!=='migration') {
        $hide_modules[]='authmigration';
    }

    $orsee_query_modules=$all_orsee_query_modules;
    $prototypes=array();
    foreach ($orsee_query_modules as $module) {
        if (!in_array($module,$hide_modules)) {
            switch ($module) {
                case "brackets":
                    $prototype=array('type'=>'brackets',
                                    'displayname'=>lang('query_brackets'),
                                    'field_name_placeholder'=>'#brackets#'
                                    );
                    $content="";
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;

                case "experimentclasses":
                    $prototype=array('type'=>'experimentclasses_multiselect',
                                    'displayname'=>lang('query_experiment_class'),
                                    'field_name_placeholder'=>'#experiment_class#'
                                    );
                    $content="";
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('participants_participated_expclass').' ';
                    $content.=experiment__experiment_class_select_field('#experiment_class#_ms_classes',array(),true);
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;

                case "experimenters":
                    $prototype=array('type'=>'experimenters_multiselect',
                                    'displayname'=>lang('query_experimenters'),
                                    'field_name_placeholder'=>'#experimenters#'
                                    );
                    $content="";
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('participants_participated_experimenters').' ';
                    $content.=experiment__experimenters_select_field("#experimenters#_ms_experimenters",array(),true,array('tag_bg_color'=>'--color-selector-tag-bg-experimenters'));
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;

                case "experimentsassigned":
                    $prototype=array('type'=>'experimentsassigned_multiselect',
                                    'displayname'=>lang('query_experiments_assigned'),
                                    'field_name_placeholder'=>'#experiments_assigned#'
                                    );
                    $content="";
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('participants_were_assigned_to').' ';
                    $content.=experiment__other_experiments_select_field("#experiments_assigned#_ms_experiments","assigned",$experiment_id,array(),true);
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;

                case "experimentsparticipated":
                    $prototype=array('type'=>'experimentsparticipated_multiselect',
                                    'displayname'=>lang('query_experiments_participated'),
                                    'field_name_placeholder'=>'#experiments_participated#'
                                    );
                    $content="";
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('participants_have_participated_on').' ';
                    $content.=experiment__other_experiments_select_field("#experiments_participated#_ms_experiments","participated",$experiment_id,array(),true);
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;

                case "statusids":
                    $prototype=array('type'=>'statusids_multiselect',
                                    'displayname'=>lang('query_participant_status'),
                                    'field_name_placeholder'=>'#statusids#'
                                    );
                    $content="";
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('participants_of_status').' ';
                    $content.=participant_status__multi_select_field("#statusids#_ms_status",array());
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
                case "subscriptions":
                    $prototype=array('type'=>'subscriptions_multiselect',
                                    'displayname'=>lang('query_subscriptions'),
                                    'field_name_placeholder'=>'#subscriptions#'
                                    );
                    $content="";
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('query_who_have_subscribed_to_experiment_types').' ';
                    $exptypes=load_external_experiment_types();
                    $items=array();
                    foreach ($exptypes as $et_id=>$et_arr) {
                        $items[$et_id]=$et_arr['exptype_name'];
                    }
                    asort($items);
                    $content.=get_tag_picker('#subscriptions#_ms_subscriptions',$items,array());
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
                case "pformtextfields":
                    $prototype=array('type'=>'pformtextfields_freetextsearch',
                                    'displayname'=>lang('query_participant_form_textfields'),
                                    'field_name_placeholder'=>'#participant_form_textfields#'
                                    );
                    $form_query_fields=array();
                    foreach ($formfields as $f) {
                        if (preg_match("/(textline|email|textarea|phone)/i",$f['type']) &&
                            ((!$experiment_id && $f['search_include_in_participant_query']=='y')    ||
                            ($experiment_id &&  $f['search_include_in_experiment_assign_query']=='y'))) {
                            $tfield=array();
                            $tfield['value']=$f['mysql_column_name'];
                            $tfield['name']=participant__field_localized_text($f,'name_text_lang_json','name_lang');
                            $form_query_fields[]=$tfield;
                        }
                    }
                    $int_fields=participant__get_internal_freetext_search_fields();
                    foreach ($int_fields as $ifield) {
                        $form_query_fields[]=$ifield;
                    }
                    $content="";
                    $content.=lang('where');
                    $content.=' <input class="input is-primary orsee-input orsee-input-text orsee-input-compact" type="text" size="20" maxlength="100" name="search_string" value="">';
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT">'.lang('not').'</OPTION>
                        <OPTION value="" SELECTED></OPTION>
                    </SELECT></span> ';
                    $content.=' '.lang('in').' ';
                    $content.='<span class="select is-primary select-compact"><SELECT name="search_field">
                    <OPTION value="all" SELECTED>'.lang('any_field').'</OPTION>';
                    foreach ($form_query_fields as $tf) {
                        $content.='<OPTION value="'.$tf['value'].'">'.$tf['name'].'</OPTION>';
                    }
                    $content.='</SELECT></span>';
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;



                case "pformselects":
                    $pform_selects=array();
                    foreach ($formfields as $f) {
                        if ((!preg_match("/(textline|email|textarea|phone)/i",$f['type'])) &&
                            (((!$experiment_id)    && $f['search_include_in_participant_query']=='y') ||
                              ($experiment_id && $f['search_include_in_experiment_assign_query']=='y')
                            )) {
                            $pform_selects[]=$f['mysql_column_name'];
                        }
                    }

                    // $existing=true;
                    //if ($experiment_id) $show_count=false; else $show_count=true;
                    // needs too much time for queries. So  better:
                    $existing=false;
                    $show_count=false;

                    foreach ($pform_selects as $fieldname) {
                        $f=array();
                        foreach ($formfields as $p) {
                            if ($p['mysql_column_name']==$fieldname) {
                                $f=$p;
                            }
                        }
                        $f=form__replace_funcs_in_field($f);
                        if (isset($f['mysql_column_name'])) {
                            $fieldname_lang=participant__field_localized_text($f,'name_text_lang_json','name_lang');
                            $fname_ph='#pform_select_'.$fieldname.'#';
                            $prototype=array('type'=>'pform_select_'.$fieldname,
                                    'displayname'=>lang('query_participant_form_selectfield').$fieldname_lang,
                                    'field_name_placeholder'=>$fname_ph
                                    );
                            $content="";
                            $content.=lang('where').' '.$fieldname_lang.' ';
                            $date_mode=(isset($f['date_mode']) ? $f['date_mode'] : 'ymd');
                            if (!in_array($date_mode,array('ymd','ym','y'))) {
                                $date_mode='ymd';
                            }
                            if ($f['type']=='select_numbers' || $f['type']=='date') {
                                $content.='<span class="select is-primary select-compact"><select name="sign">
                      <OPTION value="<="><=</OPTION>
                      <OPTION value="=" SELECTED>=</OPTION>
                      <OPTION value=">">></OPTION>
                      </select></span>';
                            } else {
                                $content.='<span class="select is-primary select-compact"><select name="not">
                    <OPTION value="" SELECTED>=</OPTION>
                    <OPTION value="NOT">'.lang('not').' =</OPTION>
                    </select></span> ';
                            }

                            if (preg_match("/(select_lang|radioline_lang|checkboxlist_lang)/",$f['type'])) {
                                $order='alphabetically';
                                if ($f['type']==='select_lang' && isset($f['order_select_lang_values']) && $f['order_select_lang_values']==='fixed_order') {
                                    $order='fixed_order';
                                }
                                if (preg_match("/(radioline_lang|checkboxlist_lang)/",$f['type']) && isset($f['order_radio_lang_values']) && $f['order_radio_lang_values']==='fixed_order') {
                                    $order='fixed_order';
                                }
                                $content.=language__multiselectfield_item($fieldname,$fieldname,$fname_ph.'_ms_'.$fieldname,array(),"",$existing,$status_query,$show_count,true,array('tag_bg_color'=>'--color-selector-tag-bg-profilefields','order'=>$order));
                                $prototype['type']='pform_multiselect_'.$fieldname;
                            } elseif ($f['type']=='boolean') {
                                $tmp_bool=array(
                                    'mysql_column_name'=>'fieldvalue',
                                    'option_values'=>'y,n',
                                    'option_values_lang'=>'y,n',
                                    'include_none_option'=>'n',
                                    'value'=>''
                                );
                                $content.=form__render_select_list($tmp_bool,'fieldvalue',true);
                                $prototype['type']='pform_simpleselect_'.$fieldname;
                            } elseif ($f['type']=='select_numbers') {
                                if ($f['values_reverse']=='y') {
                                    $reverse=true;
                                } else {
                                    $reverse=false;
                                }
                                $content.=participant__select_numbers($fieldname,'fieldvalue','',$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,false,$existing,$status_query,$show_count,true);
                                $prototype['type']='pform_numberselect_'.$fieldname;
                            } elseif ($f['type']=='date') {
                                $content.=formhelpers__pick_date('fieldvalue',0,0,0,true,true,$date_mode);
                                $prototype['type']='pform_dateselect_'.$fieldname;
                            } elseif (preg_match("/(select_list|radioline)/i",$f['type']) && !$existing) {
                                $f['value']='';
                                $content.=form__render_select_list($f,'fieldvalue',true);
                                $prototype['type']='pform_simpleselect_'.$fieldname;
                            } else {
                                $content.=participant__select_existing($fieldname,'fieldvalue','',$status_query,$show_count,true);
                                $prototype['type']='pform_simpleselect_'.$fieldname;
                            }
                            $prototype['content']=$content;
                            $prototypes[]=$prototype;
                        }
                    }
                    break;

                case "noshows":
                    $prototype=array('type'=>'noshows_numbercompare',
                                    'displayname'=>lang('query_noshows'),
                                    'field_name_placeholder'=>'#noshows#'
                                    );
                    $query="SELECT max(number_noshowup) as maxnoshow FROM ".table('participants');
                    if ($status_query) {
                        $query.=" WHERE ".$status_query;
                    }
                    $line=orsee_query($query);
                    $content="";
                    $content.=lang('where_nr_noshowups_is').' ';
                    $content.='<span class="select is-primary select-compact"><select name="sign">
                        <OPTION value="<=" SELECTED><=</OPTION>
                        <OPTION value=">">></OPTION>
                        </select></span> ';
                    $content.='<span class="select is-primary select-compact">'.helpers__select_number("count",'0',0,$line['maxnoshow'],0).'</span>';
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;

                case "participations":
                    $prototype=array('type'=>'participations_numbercompare',
                                    'displayname'=>lang('query_participations'),
                                    'field_name_placeholder'=>'#participations#'
                                    );
                    $query="SELECT max(number_reg) as maxnumreg FROM ".table('participants');
                    if ($status_query) {
                        $query.=" WHERE ".$status_query;
                    }
                    $line=orsee_query($query);
                    $content="";
                    $content.=lang('where_nr_participations_is').' ';
                    $content.='<span class="select is-primary select-compact"><select name="sign">
                        <OPTION value="<=" SELECTED><=</OPTION>
                        <OPTION value=">">></OPTION>
                        </select></span> ';
                    $content.='<span class="select is-primary select-compact">'.helpers__select_number("count",'0',0,$line['maxnumreg'],0).'</span>';
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
                case "updaterequest":
                    $prototype=array('type'=>'updaterequest_simpleselect',
                                    'displayname'=>lang('query_profile_update_request'),
                                    'field_name_placeholder'=>'#updaterequest#'
                                    );
                    $content="";
                    $content.=lang('where_profile_update_request_is').' ';
                    $content.='<span class="select is-primary select-compact"><select name="update_request_status">
                    <OPTION value="y">'.lang('active').'</OPTION>
                    <OPTION value="n">'.lang('inactive').'</OPTION>
                    </select></span> ';
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
                case "interfacelanguage":
                    $prototype=array('type'=>'interfacelanguage_simpleselect',
                                    'displayname'=>lang('query_interface_language'),
                                    'field_name_placeholder'=>'#interfacelanguage#'
                                    );
                    $content="";
                    $content.=lang('where_interface_language_is');
                    $content.=' <span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="" SELECTED></OPTION>
                        <OPTION value="NOT">'.lang('not').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang__select_lang('interface_language',$settings['public_standard_language'],'public','select is-primary',true);
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;

                case "activity":
                    $prototype=array('type'=>'activity_numbercompare',
                                    'displayname'=>lang('query_activity'),
                                    'field_name_placeholder'=>'#activity#'
                                    );
                    $content=lang('where');
                    $content.='<span class="select is-primary select-compact"><SELECT name="activity_type">
                        <OPTION value="last_activity" SELECTED>'.lang('last_activity').'</OPTION>
                        <OPTION value="last_enrolment">'.lang('last_enrolment').'</OPTION>
                        <OPTION value="last_profile_update">'.lang('last_profile_update').'</OPTION>
                        <OPTION value="creation_time">'.lang('creation_time').'</OPTION>';
                    //$content.='    <OPTION value="deletion_time">'.lang('deletion_time').'</OPTION>';
                    $content.='</SELECT></span> ';
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="" SELECTED></OPTION>
                        <OPTION value="NOT">'.lang('not').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('before_date').' ';
                    $content.=formhelpers__pick_date('#activity#_dt_activity',0,0,0,true);
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
                case "randsubset":
                    $prototype=array('type'=>'randsubset_limitnumber',
                                    'displayname'=>lang('query_rand_subset'),
                                    'field_name_placeholder'=>'#rand_subset#'
                                    );
                    $query_limit = (!isset($_REQUEST['query_limit']) ||!$_REQUEST['query_limit']) ? $settings['query_random_subset_default_size'] : $_REQUEST['query_limit'];
                    $content="";
                    $content.=lang('limit_to_randomly_drawn').' ';
                    $content.='<INPUT type="text" data-elem-name="limit" dir="ltr" value="'.$settings['query_random_subset_default_size'].'" size="5" maxlength="10">';
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
                case "subsubjectpool":
                    $prototype=array('type'=>'subsubjectpool_multiselect',
                                    'displayname'=>lang('query_subsubjectpool'),
                                    'field_name_placeholder'=>'#subsubjectpool#',
                                    'defaults'=>array('#subsubjectpool#_not'=>'',
                                                    '#subsubjectpool#_ms_subpool'=>''
                                                    )
                                    );
                    $content="";
                    $content.='<span class="select is-primary select-compact"><SELECT name="not">
                        <OPTION value="NOT" SELECTED>'.lang('without').'</OPTION>
                        <OPTION value="">'.lang('only').'</OPTION>
                    </SELECT></span> ';
                    $content.=lang('who_are_in_subjectpool').' ';
                    $content.=subpools__multi_select_field("#subsubjectpool#_ms_subpool",array());
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
                case "authmigration":
                    $prototype=array('type'=>'authmigration_simpleselect',
                                    'displayname'=>lang('query_auth_migration_status'),
                                    'field_name_placeholder'=>'#authmigration#'
                                    );
                    $content="";
                    $content.=lang('where_auth_migration_status_is').' ';
                    $content.='<span class="select is-primary select-compact"><SELECT name="migration_state">
                        <OPTION value="no_password">'.lang('query_auth_migration_no_password').'</OPTION>
                        <OPTION value="has_password">'.lang('query_auth_migration_has_password').'</OPTION>
                    </SELECT></span> ';
                    $prototype['content']=$content;
                    $prototypes[]=$prototype;
                    break;
            }
        }
    }

    return $prototypes;
}



function query__get_query_array($posted_array,$experiment_id="") {
    global $lang;

    $formfields=participantform__load();
    $participated_clause=expregister__get_pstatus_query_snippet("participated");
    $allowed_signs=array('<=','=','>');

    $query_array=array();
    $query_array['clauses']=array();

    foreach ($posted_array as $num=>$entry) {
        $temp_keys=array_keys($entry);
        $module_string=$temp_keys[0];
        $module_string_array=explode("_",$module_string);
        $module=$module_string_array[0];
        $type=$module_string_array[1];
        if ($module=='pform') {
            unset($module_string_array[0]);
            unset($module_string_array[1]);
            if ($module_string_array[2]=='ms') {
                unset($module_string_array[2]);
            }
            $pform_formfield=implode("_",$module_string_array);
        } else {
            $pform_formfield="";
        }
        $params=$entry[$module_string];

        $op='';
        $ctype='';
        $pars=array();
        $clause='';
        $subqueries=array();
        $add=true;

        if (isset($params['logical_op']) && $params['logical_op']) {
            $op=strtoupper($params['logical_op']);
        }


        switch ($module) {
            case "bracket":
                if ($type=='open') {
                    $ctype='bracket_open';
                    $clause='(';
                } else {
                    $ctype='bracket_close';
                    $clause=')';
                }
                break;
            case "experimentclasses":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) {
                    $clause.='NOT ';
                }
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (#subquery0#)
                            AND ".$participated_clause;
                $subqueries[0]['clause']['pars']=array();
                $likelist=query__make_like_list($params['ms_classes'],'experiment_class');
                $subqueries[0]['subqueries'][0]['clause']['query']="
                        SELECT experiment_id as id
                        FROM ".table('experiments')."
                        WHERE (".$likelist['par_names'].") ";
                $subqueries[0]['subqueries'][0]['clause']['pars']=$likelist['pars'];
                break;

            case "experimenters":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) {
                    $clause.='NOT ';
                }
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (#subquery0#)
                            AND ".$participated_clause;
                $subqueries[0]['clause']['pars']=array();
                $likelist=query__make_like_list($params['ms_experimenters'],'experimenter');
                $subqueries[0]['subqueries'][0]['clause']['query']="
                        SELECT experiment_id as id
                        FROM ".table('experiments')."
                        WHERE (".$likelist['par_names'].") ";
                $subqueries[0]['subqueries'][0]['clause']['pars']=$likelist['pars'];
                break;

            case "experimentsassigned":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) {
                    $clause.='NOT ';
                }
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $list=query__make_enquoted_list($params['ms_experiments'],'experiment_id');
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (".$list['par_names'].")";
                $subqueries[0]['clause']['pars']=$list['pars'];
                break;
            case "experimentsparticipated":
                $ctype='subquery';
                // clause
                $clause='participant_id ';
                if ($params['not']) {
                    $clause.='NOT ';
                }
                $clause.='IN (#subquery0#) ';
                $pars=array();
                // subquery
                $list=query__make_enquoted_list($params['ms_experiments'],'experiment_id');
                $subqueries[0]['clause']['query']="SELECT participant_id as id
                            FROM ".table('participate_at')."
                            WHERE experiment_id IN (".$list['par_names'].")
                            AND ".$participated_clause;
                $subqueries[0]['clause']['pars']=$list['pars'];
                break;
            case "statusids":
                $ctype='part';
                $list=query__make_enquoted_list($params['ms_status'],'status_id');
                $clause='status_id ';
                if ($params['not']) {
                    $clause.='NOT ';
                }
                $clause.="IN (".$list['par_names'].")";
                $pars=$list['pars'];
                break;
            case "subscriptions":
                $ctype='part';
                if (!isset($params['ms_subscriptions']) || trim((string)$params['ms_subscriptions'])==='') {
                    $add=false;
                    break;
                }
                $likelist=query__make_like_list($params['ms_subscriptions'],'subscriptions');
                if ($params['not']) {
                    $clause='NOT ('.$likelist['par_names'].')';
                } else {
                    $clause='('.$likelist['par_names'].')';
                }
                $pars=$likelist['pars'];
                break;
            case "pformtextfields":
                $ctype='part';
                $clause="";
                if ($params['not']) {
                    $clause.='NOT ';
                }
                $form_query_fields=array();
                foreach ($formfields as $f) { // whitelist by loop
                    if (preg_match("/(textline|email|textarea|phone)/i",$f['type']) &&
                        ((!$experiment_id && $f['search_include_in_participant_query']=='y')    ||
                        ($experiment_id &&  $f['search_include_in_experiment_assign_query']=='y'))) {
                        if ($params['search_field']=='all') {
                            $form_query_fields[]=$f['mysql_column_name'];
                        } elseif ($params['search_field']==$f['mysql_column_name']) {
                            $form_query_fields[]=$f['mysql_column_name'];
                        }
                    }
                }
                $int_fields=participant__get_internal_freetext_search_fields();
                foreach ($int_fields as $ifield) {
                    if ($params['search_field']=='all') {
                        $form_query_fields[]=$ifield['value'];
                    } elseif ($params['search_field']==$ifield['value']) {
                        $form_query_fields[]=$ifield['value'];
                    }
                }
                $like_array=array();
                $pars=array();
                $i=0;
                foreach ($form_query_fields as $field) {
                    $like_array[]=$field." LIKE :search_string".$i." ";
                    $pars[':search_string'.$i]='%'.$params['search_string'].'%';
                    $i++;
                }
                $clause.=' ('.implode(" OR ",$like_array).') ';
                break;
            case "pform":
                $ctype='part';
                $clause="";
                $f=array();
                foreach ($formfields as $p) {
                    if ($p['mysql_column_name']==$pform_formfield) {
                        $f=$p;
                    }
                }
                if (isset($f['mysql_column_name'])) {
                    $fieldname=$f['mysql_column_name'];
                    $clause.=$fieldname.' ';
                    if ($type=='numberselect') {
                        if (in_array($params['sign'],$allowed_signs)) {
                            $clause.=$params['sign'];
                        } else {
                            $clause.=$allowed_signs[0];
                        }
                        $clause.=' :number';
                        $pars=array(':number'=>$params['fieldvalue']);
                    } elseif ($type=='dateselect') {
                        if (in_array($params['sign'],$allowed_signs)) {
                            $sign=$params['sign'];
                        } else {
                            $sign=$allowed_signs[0];
                        }
                        $date_mode=(isset($f['date_mode']) ? $f['date_mode'] : 'ymd');
                        if (!in_array($date_mode,array('ymd','ym','y'))) {
                            $date_mode='ymd';
                        }
                        $date_ymd=ortime__date_parts_to_ymd(
                            (isset($params['fieldvalue_y']) ? $params['fieldvalue_y'] : ''),
                            (isset($params['fieldvalue_m']) ? $params['fieldvalue_m'] : ''),
                            (isset($params['fieldvalue_d']) ? $params['fieldvalue_d'] : ''),
                            $date_mode
                        );
                        if ($date_ymd) {
                            $clause=$fieldname.' '.$sign.' :datevalue';
                            $pars=array(':datevalue'=>$date_ymd);
                        } else {
                            if ($sign=='>') {
                                $clause='('.$fieldname." IS NOT NULL AND TRIM(".$fieldname.")!='')";
                            } else {
                                $clause='('.$fieldname." IS NULL OR TRIM(".$fieldname.")='')";
                            }
                        }
                    } elseif ($type=='simpleselect') {
                        if ($params['not']) {
                            $clause.="!= ";
                        } else {
                            $clause.="= ";
                        }
                        $clause.=" :fieldvalue";
                        $pars=array(':fieldvalue'=>trim($params['fieldvalue']));
                    } else {
                        if (isset($f['type']) && $f['type']==='checkboxlist_lang') {
                            $likelist=query__make_like_list($params['ms_'.$pform_formfield],$fieldname);
                            if ($params['not']) {
                                $clause='NOT ('.$likelist['par_names'].')';
                            } else {
                                $clause='('.$likelist['par_names'].')';
                            }
                            $pars=$likelist['pars'];
                        } else {
                            if ($params['not']) {
                                $clause.="NOT ";
                            }
                            $list=query__make_enquoted_list($params['ms_'.$pform_formfield],'fieldvalue');
                            $clause.="IN (".$list['par_names'].")";
                            $pars=$list['pars'];
                        }
                    }
                } else {
                    $add=false;
                }
                break;
            case "noshows":
                $ctype='part';
                if ($params['count']==0) {
                    $params['count']=0;
                }
                $clause='number_noshowup ';
                if (in_array($params['sign'],$allowed_signs)) {
                    $clause.=$params['sign'];
                } else {
                    $clause.=$allowed_signs[0];
                }
                $clause.=' :noshowcount';
                $pars=array(':noshowcount'=>$params['count']);
                break;
            case "participations":
                $ctype='part';
                if ($params['count']==0) {
                    $params['count']=0;
                }
                $clause='number_reg ';
                if (in_array($params['sign'],$allowed_signs)) {
                    $clause.=$params['sign'];
                } else {
                    $clause.=$allowed_signs[0];
                }
                $clause.=' :partcount';
                $pars=array(':partcount'=>$params['count']);
                break;
            case "updaterequest":
                $ctype='part';
                if ($params['update_request_status']=='y') {
                    $params['update_request_status']='y';
                } else {
                    $params['update_request_status']='n';
                }
                $clause='pending_profile_update_request = :pending_profile_update_request';
                $pars=array(':pending_profile_update_request'=>$params['update_request_status']);
                break;
            case "interfacelanguage":
                $ctype='part';
                $clause='language ';
                if ($params['not']) {
                    $clause.="!= ";
                } else {
                    $clause.="= ";
                }
                $clause.=' :interface_language';
                $pars=array(':interface_language'=>$params['interface_language']);
                break;
            case "randsubset":
                $add=false;
                if ($params['limit']==0) {
                    $params['limit']=0;
                }
                $query_array['limit']=$params['limit'];
                break;
            case "activity":
                $activities=array('last_activity','last_enrolment','last_profile_update',
                                'creation_time','deletion_time');
                if (in_array($params['activity_type'],$activities)) {
                    $ctype='part';
                    $clause="";
                    if ($params['not']) {
                        $clause.='NOT ';
                    }
                    $sesstime_act=ortime__array_to_sesstime($params,'dt_activity_');
                    $pars=array(':activity_time'=>ortime__sesstime_to_unixtime($sesstime_act));
                    $clause.=' ('.$params['activity_type'].' < :activity_time) ';
                } else {
                    $add=false;
                }
                break;
            case "subsubjectpool":
                $ctype='part';
                $list=query__make_enquoted_list($params['ms_subpool'],'subpool_id');
                $clause='subpool_id ';
                if ($params['not']) {
                    $clause.='NOT ';
                }
                $clause.="IN (".$list['par_names'].")";
                $pars=$list['pars'];
                break;
            case "authmigration":
                $ctype='part';
                if (!isset($params['migration_state'])) {
                    $add=false;
                } elseif ($params['migration_state']==='has_password') {
                    $clause="(password_crypted IS NOT NULL AND TRIM(password_crypted)!='')";
                } elseif ($params['migration_state']==='no_password') {
                    $clause="(password_crypted IS NULL OR TRIM(password_crypted)='')";
                } else {
                    $add=false;
                }
                break;
            default:
                $add=false;
                break;
        }
        if ($add) {
            $query_array['clauses'][]=array('op'=>$op, 'ctype'=>$ctype,'clause'=>array('query'=>$clause,'pars'=>$pars), 'subqueries'=>$subqueries);
        }
    }

    // remove unnecessary whitespace from any queries
    foreach ($query_array['clauses'] as $k=>$q) {
        $query_array['clauses'][$k]['clause']['query'] = trim(preg_replace('/\s+/', ' ', $query_array['clauses'][$k]['clause']['query']));
        if (isset($query_array['clauses'][$k]['subqueries'])) {
            $query_array['clauses'][$k]['subqueries']=query__strip_ws_subqueries_recursively($query_array['clauses'][$k]['subqueries']);
        }
    }
    // unset empty brackets, recursively if needed
    $ok=false;
    while (!$ok) {
        $ok=true;
        foreach ($query_array['clauses'] as $k=>$q) {
            if ($ok && $q['ctype']=='bracket_close' && $query_array['clauses'][$k-1]['ctype']=='bracket_open') {
                unset($query_array['clauses'][$k]);
                unset($query_array['clauses'][$k-1]);
                $ok=false;
                if (isset($query_array['clauses'][$k-2]) &&
                    $query_array['clauses'][$k-2]['ctype']=='bracket_open' &&
                    isset($query_array['clauses'][$k+1]['op'])) {
                    $query_array['clauses'][$k+1]['op']='';
                }
            }
        }
        $new_clauses=array();
        foreach ($query_array['clauses'] as $k=>$q) {
            $new_clauses[]=$q;
        }
        $query_array['clauses']=$new_clauses;
    }

    return $query_array;
}



function query__get_pseudo_query_array($posted_array) {
    global $lang;

    $formfields=participantform__load();

    $pseudo_query_array=array();

    $clevel=1;
    foreach ($posted_array as $num=>$entry) {
        $temp_keys=array_keys($entry);
        $module_string=$temp_keys[0];
        $module_string_array=explode("_",$module_string);
        $module=$module_string_array[0];
        $type=$module_string_array[1];
        if ($module=='pform') {
            unset($module_string_array[0]);
            unset($module_string_array[1]);
            $pform_formfield=implode("_",$module_string_array);
        } else {
            $pform_formfield="";
        }
        $params=$entry[$module_string];

        $level=$clevel;
        $op_text="";
        $text='';
        $parts=array();
        $add=true;

        if (isset($params['logical_op']) && $params['logical_op']) {
            $op_text=lang($params['logical_op']);
        }

        switch ($module) {
            case "bracket":
                if ($type=='open') {
                    $level=$clevel;
                    $clevel++;
                    $parts[]=array('text'=>'(','dir'=>'');
                } else {
                    $clevel--;
                    $level=$clevel;
                    $parts[]=array('text'=>')','dir'=>'');
                }
                break;
            case "experimentclasses":
                $parts[]=array('text'=>query__pseudo_query_not_without($params),'dir'=>'');
                $parts[]=array('text'=>lang('participants_participated_expclass').':','dir'=>'');
                $parts[]=array('text'=>experiment__experiment_class_field_to_list($params['ms_classes']),'dir'=>'');
                break;
            case "experimenters":
                $parts[]=array('text'=>query__pseudo_query_not_without($params),'dir'=>'');
                $parts[]=array('text'=>lang('participants_participated_experimenters').':','dir'=>'');
                $parts[]=array('text'=>experiment__list_experimenters($params['ms_experimenters'],false,true),'dir'=>'');
                break;
            case "experimentsassigned":
                $parts[]=array('text'=>query__pseudo_query_not_without($params),'dir'=>'');
                $parts[]=array('text'=>lang('participants_were_assigned_to').':','dir'=>'');
                $parts[]=array('text'=>experiment__exp_id_list_to_exp_names($params['ms_experiments']),'dir'=>'');
                break;
            case "experimentsparticipated":
                $parts[]=array('text'=>query__pseudo_query_not_without($params),'dir'=>'');
                $parts[]=array('text'=>lang('participants_have_participated_on').':','dir'=>'');
                $parts[]=array('text'=>experiment__exp_id_list_to_exp_names($params['ms_experiments']),'dir'=>'');
                break;
            case "statusids":
                $parts[]=array('text'=>query__pseudo_query_not_without($params),'dir'=>'');
                $parts[]=array('text'=>lang('participants_of_status').':','dir'=>'');
                $parts[]=array('text'=>participant__status_id_list_to_status_names($params['ms_status']),'dir'=>'');
                break;
            case "subscriptions":
                if (!isset($params['ms_subscriptions']) || trim((string)$params['ms_subscriptions'])==='') {
                    $add=false;
                    break;
                }
                $parts[]=array('text'=>query__pseudo_query_not_without($params),'dir'=>'');
                $parts[]=array('text'=>lang('query_who_have_subscribed_to_experiment_types').':','dir'=>'');
                $exptypes=load_external_experiment_types();
                $selected_ids=explode(',',trim((string)$params['ms_subscriptions']));
                $selected_names=array();
                foreach ($selected_ids as $selected_id) {
                    $selected_id=trim($selected_id);
                    if ($selected_id==='') {
                        continue;
                    }
                    if (isset($exptypes[$selected_id]['exptype_name'])) {
                        $selected_names[]=$exptypes[$selected_id]['exptype_name'];
                    } else {
                        $selected_names[]=$selected_id;
                    }
                }
                $parts[]=array('text'=>implode(', ',$selected_names),'dir'=>'');
                break;
            case "pformtextfields":
                $parts[]=array('text'=>lang('where'),'dir'=>'');
                $parts[]=array('text'=>'"'.$params['search_string'].'"','dir'=>'');
                $parts[]=array('text'=>trim(query__pseudo_query_not_not($params)),'dir'=>'');
                $parts[]=array('text'=>lang('in'),'dir'=>'');
                if ($params['search_field']=='all') {
                    $parts[]=array('text'=>lang('any_field'),'dir'=>'');
                } else {
                    $parts[]=array('text'=>$params['search_field'],'dir'=>'');
                }
                break;
            case "pform":
                $f=array();
                foreach ($formfields as $p) {
                    if ($p['mysql_column_name']==$pform_formfield) {
                        $f=$p;
                    }
                }
                if (isset($f['mysql_column_name'])) {
                    $parts[]=array('text'=>lang('where'),'dir'=>'');
                    $parts[]=array('text'=>participant__field_localized_text($f,'name_text_lang_json','name_lang'),'dir'=>'');
                    if ($type=='numberselect') {
                        $parts[]=array('text'=>$params['sign'],'dir'=>'ltr');
                        $parts[]=array('text'=>$params['fieldvalue'],'dir'=>'ltr');
                    } elseif ($type=='dateselect') {
                        $parts[]=array('text'=>$params['sign'],'dir'=>'ltr');
                        $date_mode=(isset($f['date_mode']) ? $f['date_mode'] : 'ymd');
                        if (!in_array($date_mode,array('ymd','ym','y'))) {
                            $date_mode='ymd';
                        }
                        $date_ymd=ortime__date_parts_to_ymd(
                            (isset($params['fieldvalue_y']) ? $params['fieldvalue_y'] : ''),
                            (isset($params['fieldvalue_m']) ? $params['fieldvalue_m'] : ''),
                            (isset($params['fieldvalue_d']) ? $params['fieldvalue_d'] : ''),
                            $date_mode
                        );
                        if ($date_ymd) {
                            $parts[]=array('text'=>ortime__format_ymd_localized($date_ymd,'',$date_mode),'dir'=>'ltr');
                        } else {
                            $parts[]=array('text'=>"''",'dir'=>'ltr');
                        }
                    } elseif ($type=='simpleselect') {
                        $display_value=$params['fieldvalue'];
                        if (isset($f['type']) && $f['type']=='boolean') {
                            if ($display_value==='y') {
                                $display_value=lang('y');
                            } elseif ($display_value==='n') {
                                $display_value=lang('n');
                            }
                        }
                        $parts[]=array('text'=>trim(query__pseudo_query_not_not($params)),'dir'=>'');
                        $parts[]=array('text'=>'=','dir'=>'');
                        $parts[]=array('text'=>'"'.$display_value.'"','dir'=>'');
                    } else {
                        $parts[]=array('text'=>trim(query__pseudo_query_not_not($params)),'dir'=>'');
                        $parts[]=array('text'=>lang('in').':','dir'=>'');
                        $parts[]=array('text'=>participant__select_lang_idlist_to_names($f['mysql_column_name'],$params['ms_'.$pform_formfield]),'dir'=>'');
                    }
                } else {
                    $add=false;
                }
                break;
            case "noshows":
                $parts[]=array('text'=>lang('where_nr_noshowups_is'),'dir'=>'');
                $parts[]=array('text'=>$params['sign'],'dir'=>'ltr');
                $parts[]=array('text'=>$params['count'],'dir'=>'ltr');
                break;
            case "participations":
                $parts[]=array('text'=>lang('where_nr_participations_is'),'dir'=>'');
                $parts[]=array('text'=>$params['sign'],'dir'=>'ltr');
                $parts[]=array('text'=>$params['count'],'dir'=>'ltr');
                break;
            case "updaterequest":
                $parts[]=array('text'=>lang('where_profile_update_request_is'),'dir'=>'');
                if ($params['update_request_status']=='y') {
                    $parts[]=array('text'=>lang('active'),'dir'=>'');
                } else {
                    $parts[]=array('text'=>lang('inactive'),'dir'=>'');
                }
                break;
            case "interfacelanguage":
                $lnames=lang__get_language_names();
                $parts[]=array('text'=>lang('where_interface_language_is'),'dir'=>'');
                $parts[]=array('text'=>trim(query__pseudo_query_not_not($params)),'dir'=>'');
                $parts[]=array('text'=>'=','dir'=>'');
                $parts[]=array('text'=>'"'.$lnames[$params['interface_language']].'"','dir'=>'');
                break;
            case "activity":
                $sesstime_act=ortime__array_to_sesstime($params,'dt_activity_');
                $date_text=ortime__format(ortime__sesstime_to_unixtime($sesstime_act),'hide_time:true');
                $parts=array(
                    array('text'=>lang('where'),'dir'=>''),
                    array('text'=>lang($params['activity_type']),'dir'=>''),
                    array('text'=>trim(query__pseudo_query_not_not($params)),'dir'=>''),
                    array('text'=>lang('before_date'),'dir'=>''),
                    array('text'=>$date_text,'dir'=>'ltr')
                );
                break;
            case "randsubset":
                $parts[]=array('text'=>lang('limit_to_randomly_drawn'),'dir'=>'');
                $parts[]=array('text'=>$params['limit'],'dir'=>'ltr');
                break;
            case "subsubjectpool":
                $parts[]=array('text'=>query__pseudo_query_not_without($params),'dir'=>'');
                $parts[]=array('text'=>lang('who_are_in_subjectpool').':','dir'=>'');
                $parts[]=array('text'=>subpools__idlist_to_namelist($params['ms_subpool']),'dir'=>'');
                break;
            case "authmigration":
                if (!isset($params['migration_state'])) {
                    $add=false;
                } elseif ($params['migration_state']==='has_password') {
                    $parts[]=array('text'=>lang('where_auth_migration_status_is').':','dir'=>'');
                    $parts[]=array('text'=>lang('query_auth_migration_has_password'),'dir'=>'');
                } elseif ($params['migration_state']==='no_password') {
                    $parts[]=array('text'=>lang('where_auth_migration_status_is').':','dir'=>'');
                    $parts[]=array('text'=>lang('query_auth_migration_no_password'),'dir'=>'');
                } else {
                    $add=false;
                }
                break;
        }
        if ($add && count($parts)>0) {
            $render_parts=array();
            foreach ($parts as $part) {
                if ($part['text']==='') {
                    continue;
                }
                $dir_attr=($part['dir']!=='' ? ' dir="'.$part['dir'].'"' : '');
                $render_parts[]='<span style="unicode-bidi:isolate;"'.$dir_attr.'>'.$part['text'].'</span>';
            }
            $text=implode(' ',$render_parts);
        }
        if ($add) {
            $pseudo_query_array[]=array('level'=>$level, 'op_text'=>$op_text, 'text'=>$text);
        }
    }

    return $pseudo_query_array;
}


// some query helpers
function query__get_experimenter_or_clause($experimenter_array,$tablename='experiments',$columnname='experimenter') {
    if (is_array($experimenter_array) && count($experimenter_array)>0) {
        $clause_array=array();
        $pars=array();
        $i=0;
        foreach ($experimenter_array as $e) {
            $i++;
            $parname=':'.$columnname.$i;
            $pars[$parname]='%|'.$e.'|%';
            $clause_array[]="(".table($tablename).".".$columnname." LIKE ".$parname.")";
        }
        $exp_clause="( ".implode(" OR ",$clause_array)." )";
    } else {
        $exp_clause="";
        $pars=array();
    }
    return array('clause'=>$exp_clause,'pars'=>$pars);
}

function query__get_class_or_clause($class_array) {
    if (is_array($class_array) && count($class_array)>0) {
        $clause_array=array();
        $pars=array();
        $i=0;
        foreach ($class_array as $c) {
            $i++;
            $parname=':experiment_class'.$i;
            $pars[$parname]='%|'.$c.'|%';
            $clause_array[]="(".table('experiments').".experiment_class LIKE ".$parname.")";
        }
        $class_clause="( ".implode(" OR ",$clause_array)." )";
    } else {
        $class_clause="";
        $pars=array();
    }
    return array('clause'=>$class_clause,'pars'=>$pars);
}





?>
