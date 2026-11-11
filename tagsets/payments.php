<?php
// part of orsee. see orsee.org

function payments__paytype_selectfield($formfieldvarname,$selected,$exclude=array(),$only=array(),$select_wrapper_class='select is-primary') {
    $paytypes=payments__load_paytypes();
    $first=true;
    if (count($only)>0) {
        $restrict=true;
    } else {
        $restrict=false;
    }
    $out='';
    $out.='<span class="'.$select_wrapper_class.'"><SELECT name="'.$formfieldvarname.'">';
    foreach ($paytypes as $k=>$v) {
        if (($restrict && in_array($k,$only)) || ((!$restrict) && (!in_array($k,$exclude)))) {
            $out.='<OPTION value="'.$k.'"';
            if (!$selected && $first) {
                $out.=' SELECTED';
                $first=false;
            } elseif ($selected==$k) {
                $out.=' SELECTED';
            }
            $out.='>'.$v.'</OPTION>';
        }
    }
    $out.='</SELECT></span>';
    return $out;
}

function payments__budget_selectfield($formfieldvarname,$selected,$exclude=array(),$only=array(),$select_wrapper_class='select is-primary') {
    $budgets=payments__load_budgets();
    $first=true;
    if (count($only)>0) {
        $restrict=true;
    } else {
        $restrict=false;
    }
    $out='';
    $out.='<span class="'.$select_wrapper_class.'"><SELECT name="'.$formfieldvarname.'">';
    foreach ($budgets as $k=>$v) {
        if (($restrict && in_array($k,$only)) || ((!$restrict) && (!in_array($k,$exclude)))) {
            $out.='<OPTION value="'.$k.'"';
            if (!$selected && $first) {
                $out.=' SELECTED';
                $first=false;
            } elseif ($selected==$k) {
                $out.=' SELECTED';
            }
            $out.='>'.$v['budget_name'].'</OPTION>';
        }
    }
    $out.='</SELECT></span>';
    return $out;
}

function payments__load_paytypes() {
    global $preloaded_payment_types;
    if (isset($preloaded_payment_types) && is_array($preloaded_payment_types)) {
        return $preloaded_payment_types;
    } else {
        $paytypes=array();
        $query="SELECT * FROM ".table('lang')."
                WHERE content_type='payments_type'
                ORDER BY order_number";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $paytypes[$line['content_name']]=$line[lang('lang')];
        }
        $preloaded_payment_types=$paytypes;
        return $paytypes;
    }
}

function payments__load_budgets($include_notenabled=false) {
    global $preloaded_payment_budgets;
    if (isset($preloaded_payment_budgets) && is_array($preloaded_payment_budgets) && !$include_notenabled) {
        return $preloaded_payment_budgets;
    } else {
        $budgets=array();
        $query="SELECT * FROM ".table('budgets');
        if (!$include_notenabled) {
            $query.=" WHERE enabled = 1 ";
        }
        $query.=" ORDER BY budget_name";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $budgets[$line['budget_id']]=$line;
        }
        $preloaded_payment_budgets=$budgets;
        return $budgets;
    }
}

function payments__get_default_paytype($experiment=array(),$session=array()) {
    $continue=true;
    if ($continue) {
        if (is_array($session) && isset($session['payment_types'])) {
            $paytypes=db_string_to_id_array($session['payment_types']);
            if (count($paytypes)>0) {
                $continue=false;
                return $paytypes[0];
            }
        }
    }
    if ($continue) {
        if (is_array($experiment) && isset($experiment['payment_types'])) {
            $paytypes=db_string_to_id_array($experiment['payment_types']);
            if (count($paytypes)>0) {
                $continue=false;
                return $paytypes[0];
            }
        }
    }
    if ($continue) {
        $paytypes=payments__load_paytypes();
        ksort($paytypes);
        $first=true;
        foreach ($paytypes as $k=>$paytype) {
            if ($first) {
                return $k;
                $first=false;
            }
        }
    }
}

function payments__get_default_budget($experiment=array(),$session=array()) {
    $continue=true;
    if ($continue) {
        if (is_array($session) && isset($session['payment_budgets'])) {
            $budgets=db_string_to_id_array($session['payment_budgets']);
            if (count($budgets)>0) {
                $continue=false;
                return $budgets[0];
            }
        }
    }
    if ($continue) {
        if (is_array($experiment) && isset($experiment['payment_budgets'])) {
            $budgets=db_string_to_id_array($experiment['payment_budgets']);
            if (count($budgets)>0) {
                $continue=false;
                return $budgets[0];
            }
        }
    }
    if ($continue) {
        $budgets=payments__load_budgets(true);
        ksort($budgets);
        $first=true;
        foreach ($budgets as $k=>$budget) {
            if ($first) {
                return $k;
                $first=false;
            }
        }
    }
    if ($continue) {
        $query="SELECT * FROM ".table('budgets')."
                ORDER BY budget_id
                LIMIT 1";
        $result=or_query($query);
        $line = pdo_fetch_assoc($result);
        return $line['budget_id'];
    }
}

?>
