<?php
// part of orsee. see orsee.org


// select field for numbers from begin to end by steps
function helpers__select_numbers($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$none=false) {
    $i=$begin;
    echo '<span class="select is-primary"><select name="'.$name.'" id="'.$name.'">';
    if ($none) {
        echo '<option value="">-</option>';
    }
    while ($i<=$end) {
        echo '<option value="'.$i.'"';
        if ($i == (int) $prevalue) {
            echo ' SELECTED';
        }
        echo '>';
        echo helpers__pad_number($i,$fillzeros);
        echo '</option>
            ';
        $i=$i+$steps;
    }
    echo '</select></span>';
}

function helpers__select_number($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$none=false) {
    $out='';
    $i=$begin;
    $out.='<select name="'.$name.'" id="'.$name.'">';
    if ($none) {
        $out.='<option value="">-</option>';
    }
    while ($i<=$end) {
        $out.='<option value="'.$i.'"';
        if ($i == (int) $prevalue) {
            $out.=' SELECTED';
        }
        $out.='>';
        $out.=helpers__pad_number($i,$fillzeros);
        $out.='</option>
            ';
        $i=$i+$steps;
    }
    $out.='</select>';
    return $out;
}


// select field for text array
function helpers__select_text($tarray,$name,$prevalue,$none=false) {
    global $lang;
    $out='<select name="'.$name.'">';
    if ($none) {
        $out.='<option value=""></option>';
    }
    foreach ($tarray as $k=>$text) {
        $out.='<option value="'.$k.'"';
        if ($k == $prevalue) {
            $out.=' SELECTED';
        }
        $out.='>';
        $out.=lang($text);
        $out.='</option>
                        ';
    }
    $out.='</select>';
    return $out;
}


// select field for values for reminder time and registration end
function helpers__select_numbers_relative($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$current_time=0) {
    global $authdata;
    $i=$begin;
    echo '<span class="select is-primary"><select name="'.$name.'">';
    while ($i <= $end) {
        echo '<option value="'.$i.'"';
        if ($i== (int) $prevalue) {
            echo ' SELECTED';
        }
        echo '>';
        echo helpers__pad_number($i,$fillzeros);
        if ($current_time > 0) {
            $utime=$current_time - ($i * 60 * 60);
            echo ' ('.ortime__format($utime,'',$authdata['language']).')';
        }
        echo '</option>
            ';
        $i=$i+$steps;
    }
    echo '</select></span>';
}



function experiment_ext_types__checkboxes($postvarname,$showvar,$checked=array()) {
    $exptypes=load_external_experiment_types();
    foreach ($exptypes as $exptype_id=>$exptype) {
        echo '<INPUT type="checkbox" name="'.$postvarname.'['.$exptype_id.']"
                 value="'.$exptype_id.'"';
        if (isset($checked[$exptype_id]) && $checked[$exptype_id]) {
            echo " CHECKED";
        }
        echo '>'.$exptype[lang('lang')];
        echo '<BR>';
    }
}

// select field for sessions
function select__sessions($preval,$varname,$sessions,$hide_nosession=false,$with_exp=false,$select_wrapper_class='select is-primary') {
    global $lang, $expadmindata;
    if (!$preval) {
        $preval=0;
    }
    if (!$varname) {
        $varname="session";
    }

    $out='';
    $out.='<span class="'.$select_wrapper_class.'"><SELECT name="'.$varname.'">';
    if (!$hide_nosession) {
        $out.='<OPTION value="0"';
        if ($preval==0) {
            $out.=" SELECTED";
        }
        $out.='>'.lang('no_session').'</OPTION>';
    }

    foreach ($sessions as $line) {
        $out.='<OPTION value="'.$line['session_id'].'"';
        if ($preval==$line['session_id']) {
            $out.=" SELECTED";
        }
        $out.='>';
        $session_label=ortime__format(ortime__sesstime_to_unixtime($line['session_start']),'hide_second:true',lang('lang'));
        if ($with_exp) {
            if (lang__is_rtl()) {
                $out.=$session_label.' - '.$line['experiment_name'];
            } else {
                $out.=$line['experiment_name'].' - '.$session_label;
            }
        } else {
            $out.=$session_label;
        }
        if (isset($line['p_is_assigned'])) {
            if ($line['p_is_assigned']) {
                $out.=' - '.lang('is_assigned_to_experiment_short');
            } else {
                $out.=' - '.lang('is_not_yet_assigned_to_experiment_short');
            }
        }
        $out.='</OPTION>';
    }
    $out.='</SELECT></span>';
    return $out;
}


function formhelpers__legacy_percent_to_php_format($format) {
    return strtr($format, array(
        '%d' => 'd',
        '%m' => 'm',
        '%Y' => 'Y',
        '%y' => 'y',
        '%H' => 'H',
        '%h' => 'h',
        '%i' => 'i',
        '%a' => 'a'
    ));
}

function formhelpers__format_to_flatpickr($format) {
    if (strpos($format,'%')!==false) {
        $format=formhelpers__legacy_percent_to_php_format($format);
    }
    $map=array(
        'd'=>'d',
        'j'=>'j',
        'm'=>'m',
        'n'=>'n',
        'Y'=>'Y',
        'y'=>'y',
        'M'=>'M',
        'F'=>'F',
        'H'=>'H',
        'G'=>'H',
        'h'=>'h',
        'g'=>'G',
        'i'=>'i',
        's'=>'S',
        'a'=>'K',
        'A'=>'K'
    );
    return strtr($format,$map);
}

function formhelpers__pick_date_flatpickr($field, $selected_date=0, $years_backward=0, $years_forward=0, $compact=false, $voluntary=false, $date_mode='ymd', $dom_suffix='') {
    global $lang;
    if ($date_mode!=='ymd' && $date_mode!=='ym' && $date_mode!=='y') {
        $date_mode='ymd';
    }

    if ($selected_date) {
        $sda=ortime__sesstime_to_array($selected_date);
    } else {
        $sda=array('y'=>0,'m'=>0,'d'=>0);
    }

    $has_min_date=($years_backward>0);
    $has_max_date=($years_forward>0);
    if ($has_min_date) {
        $year_start=(int)date("Y")-$years_backward;
    }
    if ($has_max_date) {
        $year_stop=(int)date("Y")+$years_forward;
        if (date("Y")>=$year_stop) {
            $year_stop=date("Y")+1;
        }
    }

    if ($date_mode==='ym') {
        $display_format=lang('format_datetime_date_no_day');
    } elseif ($date_mode==='y') {
        $display_format='Y';
    } else {
        $display_format=lang('format_datetime_date');
    }
    $flatpickr_format=formhelpers__format_to_flatpickr($display_format);
    $first_day_of_week=(int)lang('format_datetime_firstdayofweek_0:Su_1:Mo');

    $daysMin=explode(",",lang('format_datetime_weekday_abbr'));
    foreach ($daysMin as $k=>$v) {
        $daysMin[$k]=trim($v);
    }
    $daysMin=array_slice($daysMin,0,7);
    $months=explode(",",lang('format_datetime_month_names'));
    foreach ($months as $k=>$v) {
        $months[$k]=trim($v);
    }
    $months=array_slice($months,0,12);
    $monthsShort=explode(",",lang('format_datetime_month_abbr'));
    foreach ($monthsShort as $k=>$v) {
        $monthsShort[$k]=trim($v);
    }
    $monthsShort=array_slice($monthsShort,0,12);

    $locale_json=json_encode(array(
        'firstDay'=>$first_day_of_week,
        'weekdays'=>array('shorthand'=>$daysMin,'longhand'=>$daysMin),
        'months'=>array('shorthand'=>$monthsShort,'longhand'=>$months)
    ));

    $default_day=($selected_date) ? (int)$sda['d'] : '';
    $default_month=($selected_date) ? (int)$sda['m'] : '';
    $default_year=($selected_date) ? (int)$sda['y'] : '';
    if ($date_mode==='y') {
        if ($default_year) {
            $default_month=1;
            $default_day=1;
        } else {
            $default_month='';
            $default_day='';
        }
    } elseif ($date_mode==='ym') {
        if ($default_year && $default_month) {
            $default_day=1;
        } else {
            $default_day='';
        }
    }
    $dom_field=$field;
    if ($dom_suffix!=='') {
        $dom_field=$field.'_'.$dom_suffix;
    }
    $out='<input type="hidden" id="'.$dom_field.'_d" name="'.$field.'_d" value="'.$default_day.'">
            <input type="hidden" id="'.$dom_field.'_m" name="'.$field.'_m" value="'.$default_month.'">
            <input type="hidden" id="'.$dom_field.'_y" name="'.$field.'_y" value="'.$default_year.'">';
    $input_class='input is-primary orsee-input datepick_input orsee-flatpickr-input';
    if ($compact) {
        $input_class.=' orsee-input-compact';
    }
    $out.='
            <span class="orsee-inline-controls">
                <span class="control has-icons-right">
                    <input type="text" id="'.$dom_field.'_flatpickr_date" class="'.$input_class.'" value="" autocomplete="off" readonly>';
    if ($voluntary) {
        $out.='
                    <span class="icon is-right orsee-text-input-inline-button '.$dom_field.'_dateclear" title="'.lang('empty').'" aria-label="'.lang('empty').'" role="button" tabindex="0"><i class="fa fa-times"></i></span>';
    }
    $out.='
                </span>
                <i class="fa fa-calendar fa-lg orsee-flatpickr-trigger '.$dom_field.'_datepicker"></i>
            </span>
            <script type="text/javascript">
            (function() {
                var dateInput=document.getElementById("'.$dom_field.'_flatpickr_date");
                var dayField=document.getElementById("'.$dom_field.'_d");
                var monthField=document.getElementById("'.$dom_field.'_m");
                var yearField=document.getElementById("'.$dom_field.'_y");
                var iconTrigger=document.querySelector(".'.$dom_field.'_datepicker");
                var clearTrigger='.($voluntary ? 'document.querySelector(".'.$dom_field.'_dateclear")' : 'null').';
                if (!dateInput || !dayField || !monthField || !yearField || typeof flatpickr === "undefined") return;

                var mode='.json_encode($date_mode).';
                var dateFormat='.json_encode($flatpickr_format).';
                var modeTouched=false;
                var wasCleared=false;
                var picker=flatpickr(dateInput, {
                    dateFormat: dateFormat,
                    defaultDate: '.($selected_date ? 'new Date('.(int)$sda['y'].', '.((int)$sda['m']-1).', '.(int)$sda['d'].')' : 'null').',
                    minDate: '.($has_min_date ? 'new Date('.(int)$year_start.', 0, 1)' : 'null').',
                    maxDate: '.($has_max_date ? 'new Date('.(int)$year_stop.', 11, 31)' : 'null').',
                    allowInput: false,
                    clickOpens: false,
                    position: '.(lang__is_rtl() ? '"auto right"' : '"auto left"').',
                    locale: '.$locale_json.',
                    onChange: function(selectedDates) {
                        if (!selectedDates || !selectedDates.length) return;
                        var selectedDate=selectedDates[0];
                        if (mode==="y") {
                            dayField.value=1;
                            monthField.value=1;
                        } else if (mode==="ym") {
                            dayField.value=1;
                            monthField.value=selectedDate.getMonth() + 1;
                        } else {
                            dayField.value=selectedDate.getDate();
                            monthField.value=selectedDate.getMonth() + 1;
                        }
                        yearField.value=selectedDate.getFullYear();
                    },
                    onValueUpdate: function(selectedDates) {
                        if (!selectedDates || !selectedDates.length) return;
                        var selectedDate=selectedDates[0];
                        if (mode==="y") {
                            dayField.value=1;
                            monthField.value=1;
                        } else if (mode==="ym") {
                            dayField.value=1;
                            monthField.value=selectedDate.getMonth() + 1;
                        } else {
                            dayField.value=selectedDate.getDate();
                            monthField.value=selectedDate.getMonth() + 1;
                        }
                        yearField.value=selectedDate.getFullYear();
                    }
                });

                if (iconTrigger) {
                    iconTrigger.addEventListener("click", function(event) {
                        event.preventDefault();
                        picker.open();
                    });
                }
                dateInput.addEventListener("click", function() {
                    picker.open();
                });
                dateInput.addEventListener("focus", function() {
                    picker.open();
                });
                if (clearTrigger) {
                    clearTrigger.addEventListener("click", function(event) {
                        event.preventDefault();
                        wasCleared=true;
                        modeTouched=false;
                        picker.clear();
                        dayField.value="";
                        monthField.value="";
                        yearField.value="";
                        dateInput.value="";
                    });
                    clearTrigger.addEventListener("keydown", function(event) {
                        if (event.key!=="Enter" && event.key!==" ") return;
                        event.preventDefault();
                        wasCleared=true;
                        modeTouched=false;
                        picker.clear();
                        dayField.value="";
                        monthField.value="";
                        yearField.value="";
                        dateInput.value="";
                    });
                }

                var applyModeUi=function() {
                    if (mode==="ym" || mode==="y") {
                        dateInput.value="";
                        dateInput.setAttribute("placeholder", "");
                        if (picker.daysContainer) picker.daysContainer.style.display="none";
                        if (picker.weekdayContainer) picker.weekdayContainer.style.display="none";
                        if (picker.calendarContainer) {
                            var daysBlock=picker.calendarContainer.querySelector(".flatpickr-days");
                            if (daysBlock) daysBlock.style.display="none";
                            var weeksBlock=picker.calendarContainer.querySelector(".flatpickr-weekdays");
                            if (weeksBlock) weeksBlock.style.display="none";
                        }
                    }
                    if (mode==="y") {
                        if (picker.monthNav) {
                            var monthDropdown=picker.monthNav.querySelector(".flatpickr-monthDropdown-months");
                            if (monthDropdown) monthDropdown.style.display="none";
                            var currentMonthText=picker.monthNav.querySelector(".cur-month");
                            if (currentMonthText) currentMonthText.style.display="none";
                        }
                        if (picker.prevMonthNav) picker.prevMonthNav.style.visibility="hidden";
                        if (picker.nextMonthNav) picker.nextMonthNav.style.visibility="hidden";
                    }
                };

                var syncByModeFromPicker=function() {
                    if (!picker) return;
                    if (wasCleared) {
                        dateInput.value="";
                        return;
                    }
                    if (mode==="y") {
                        var y=picker.currentYear;
                        if (!y || isNaN(y)) return;
                        yearField.value=y;
                        monthField.value=1;
                        dayField.value=1;
                        dateInput.value=String(y);
                        return;
                    }
                    if (mode==="ym") {
                        var y2=picker.currentYear;
                        var m2=picker.currentMonth+1;
                        if (!y2 || isNaN(y2) || isNaN(m2)) return;
                        yearField.value=y2;
                        monthField.value=m2;
                        dayField.value=1;
                        dateInput.value=String(y2).padStart(4,"0")+"-"+String(m2).padStart(2,"0");
                    }
                };

                applyModeUi();
                if (mode==="ym" || mode==="y") {
                    picker.config.onMonthChange.push(function() {
                        if (!modeTouched) return;
                        wasCleared=false;
                        syncByModeFromPicker();
                    });
                    picker.config.onYearChange.push(function() {
                        if (!modeTouched) return;
                        wasCleared=false;
                        syncByModeFromPicker();
                    });
                    picker.config.onOpen.push(function() { applyModeUi(); });
                    if (picker.monthNav) {
                        picker.monthNav.addEventListener("click", function() { modeTouched=true; });
                        picker.monthNav.addEventListener("change", function() { modeTouched=true; });
                        picker.monthNav.addEventListener("input", function() { modeTouched=true; });
                    }
                    if (yearField.value!=="" || monthField.value!=="" || dayField.value!=="") {
                        wasCleared=false;
                        syncByModeFromPicker();
                    } else {
                        dateInput.value="";
                    }
                }

                var syncPickerFromFields=function() {
                    var day=parseInt(dayField.value,10);
                    var month=parseInt(monthField.value,10);
                    var year=parseInt(yearField.value,10);
                    if (mode==="y") {
                        if (isNaN(year)) return;
                        month=1;
                        day=1;
                    } else if (mode==="ym") {
                        if (isNaN(year) || isNaN(month)) return;
                        day=1;
                    } else {
                        if (isNaN(day) || isNaN(month) || isNaN(year)) return;
                    }
                    var selectedDate=new Date(year, month - 1, day);
                    if (isNaN(selectedDate.getTime())) return;
                    picker.setDate(selectedDate, false, "'.$flatpickr_format.'");
                };
                dayField.addEventListener("change", syncPickerFromFields);
                monthField.addEventListener("change", syncPickerFromFields);
                yearField.addEventListener("change", syncPickerFromFields);
            })();
            </script>';
    return $out;
}

function formhelpers__pick_time_flatpickr($field, $selected_time=0,$minute_steps=0) {
    global $settings, $lang;

    if (!$selected_time) {
        $selected_time=ortime__unixtime_to_sesstime();
    }
    if (!$minute_steps) {
        $minute_steps=$settings['session_duration_minute_steps'];
    }

    $tformat=lang('format_datetime_time_no_sec');
    $is_mil_time=is_mil_time($tformat);
    $is_mil_time_str=($is_mil_time) ? 'true' : 'false';
    $flatpickr_format=formhelpers__format_to_flatpickr($tformat);
    $sda=ortime__sesstime_to_array($selected_time);
    $default_hour=(int)$sda['h'];
    $default_minute=(int)$sda['i'];

    $out='<input type="hidden" id="'.$field.'_h" name="'.$field.'_h" value="'.$default_hour.'">
            <input type="hidden" id="'.$field.'_i" name="'.$field.'_i" value="'.$default_minute.'">
            <input type="text" id="'.$field.'_flatpickr_time" class="input is-primary orsee-input clockpick_input orsee-flatpickr-input" value="" autocomplete="off">
            <i id="'.$field.'_clockpicker" class="fa fa-clock-o fa-lg orsee-flatpickr-trigger"></i>
            <script type="text/javascript">
            (function() {
                var timeInput=document.getElementById("'.$field.'_flatpickr_time");
                var hourField=document.getElementById("'.$field.'_h");
                var minuteField=document.getElementById("'.$field.'_i");
                var iconTrigger=document.getElementById("'.$field.'_clockpicker");
                if (!timeInput || !hourField || !minuteField || typeof flatpickr === "undefined") return;

                var defaultDate=new Date();
                defaultDate.setHours('.$default_hour.', '.$default_minute.', 0, 0);
                var picker=flatpickr(timeInput, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "'.$flatpickr_format.'",
                    time_24hr: '.$is_mil_time_str.',
                    minuteIncrement: '.(int)$minute_steps.',
                    allowInput: true,
                    clickOpens: true,
                    defaultDate: defaultDate,
                    onChange: function(selectedDates) {
                        if (!selectedDates || !selectedDates.length) return;
                        var selectedDate=selectedDates[0];
                        hourField.value=selectedDate.getHours();
                        minuteField.value=selectedDate.getMinutes();
                    },
                    onValueUpdate: function(selectedDates) {
                        if (!selectedDates || !selectedDates.length) return;
                        var selectedDate=selectedDates[0];
                        hourField.value=selectedDate.getHours();
                        minuteField.value=selectedDate.getMinutes();
                    }
                });

                if (iconTrigger) {
                    iconTrigger.addEventListener("click", function(event) {
                        event.preventDefault();
                        picker.open();
                    });
                }

                var syncPickerFromFields=function() {
                    var hour=parseInt(hourField.value,10);
                    var minute=parseInt(minuteField.value,10);
                    if (isNaN(hour) || isNaN(minute)) return;
                    var selectedDate=new Date();
                    selectedDate.setHours(hour, minute, 0, 0);
                    picker.setDate(selectedDate, false, "'.$flatpickr_format.'");
                };
                hourField.addEventListener("change", syncPickerFromFields);
                minuteField.addEventListener("change", syncPickerFromFields);
            })();
            </script>';
    return $out;
}

function formhelpers__pick_date($field, $selected_date=0, $years_backward=0, $years_forward=0, $compact=false, $voluntary=false, $date_mode='ymd', $dom_suffix='') {
    return formhelpers__pick_date_flatpickr($field,$selected_date,$years_backward,$years_forward,$compact,$voluntary,$date_mode,$dom_suffix);
}

function formhelpers__pick_time($field, $selected_time=0,$minute_steps=0) {
    return formhelpers__pick_time_flatpickr($field,$selected_time,$minute_steps);
}

function formhelpers__orderlist($listID, $formName, $rows, $no_add_button=false, $add_button_title="", $tableHeads = "") {
    $dropdownSelector = ($no_add_button ? 'null' : "document.getElementById('".$listID."Dropdown')");
    $out='';
    $out.=" <script>
            var ".$listID."_rows = ";
    $out.=json_encode($rows);
    $out.=";
            document.addEventListener('DOMContentLoaded', function() {
                window.list_".$listID." = new ListTool(".$listID."_rows, document.getElementById('list_".$listID."'), " . $dropdownSelector . ", '".$formName."');
            });
            </script>";

    $out.='<div class="orsee-listtool">';
    if (!$no_add_button) {
        if (!$add_button_title) {
            $add_button_title=lang('add');
        }
        $out.='<div class="orsee-listtool-add">
            <ul id="'.$listID.'Dropdown" class="query_add">
                <li>
                    <a href="#" class="button orsee-btn query_add_btn"><i class="fa fa-plus-circle fa-lg" style="padding: 0 0.3em 0 0"></i>'.$add_button_title.'</a>
                    <ul class="dropdownItems"></ul>
                </li>
            </ul>
        </div>';
    }

    $out.='<div id="list_'.$listID.'" class="orsee-listtable listtable">';
    if ($tableHeads) {
        $out.='<div class="orsee-listhead listhead"><div class="orsee-listcell orsee-listcell-drag"></div>';
        $out.=$tableHeads;
        $out.='<div class="orsee-listcell orsee-listcell-action"></div></div>';
    }
    $out.='<div class="orsee-listbody listbody"></div></div>';
    $out.='</div>';

    return $out;
}




?>
