// queryform javascript functions
// part of orsee. see orsee.org.

var Ptypes = {};
var queryCount = 0;
//if the limit prototype has been added to the footer yet
var limitUsed = false;
//counts the number of opened brackets
var multiDefaults = [];
var openCount = 0;
var dragEnabled = false;
var dragStartY = -1;
var dragStartRow = 0;
var dragSensitivity = 15;
var logicalOpPrototype =
    '<div class="orsee-listcell">&nbsp;<span class="select is-primary select-compact"><select>' +
    '<option value="and" selected>AND</option>' +
    '<option value="or">OR</option>' +
    "</select></span></div>";
var logicalOpEmptyCell = '<div class="orsee-listcell">&nbsp;</div>';

var evaldCode = {};

function _qfFirst(selector, root) {
    return (root || document).querySelector(selector);
}

function _qfAll(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
}

function _qfIsUndef(v) {
    return typeof v === "undefined";
}

function _qfSetCursor(value) {
    document.body.style.cursor = value;
    _qfAll("button").forEach(function (b) {
        b.style.cursor = value;
    });
}

function _qfRowFromHtml(html) {
    var tmp = document.createElement("div");
    tmp.innerHTML = html;
    return tmp.firstElementChild;
}

function _qfCellFromHtml(html) {
    var row = document.createElement("div");
    row.innerHTML = html;
    return row.firstElementChild;
}

function _qfBody() {
    return _qfFirst("#queryTable .querybody");
}

function _qfFoot() {
    return _qfFirst("#queryTable .queryfoot");
}

function _qfRowPos(row) {
    var pos = 0;
    var cur = row;
    while (cur && cur.previousElementSibling) {
        pos++;
        cur = cur.previousElementSibling;
    }
    return pos;
}

function _qfApplyCompactFieldStyles(row) {
    var fieldCell = row && row.children ? row.children[field_index] : null;
    if (!fieldCell) return;
    _qfAll("input, select, textarea", fieldCell).forEach(function (el) {
        var tag = el.tagName.toLowerCase();
        if (tag === "input") {
            var type = (el.getAttribute("type") || "text").toLowerCase();
            if (type === "hidden" || type === "checkbox" || type === "radio") return;
            el.classList.add("input", "is-primary", "orsee-input", "orsee-input-text", "orsee-input-compact");
            return;
        }
        if (tag === "textarea") {
            el.classList.add("textarea", "is-primary", "orsee-input", "orsee-textarea", "orsee-input-compact");
            return;
        }
        if (tag === "select") {
            if (!el.parentElement || !el.parentElement.classList.contains("select")) {
                var wrap = document.createElement("span");
                wrap.className = "select is-primary select-compact";
                el.parentNode.insertBefore(wrap, el);
                wrap.appendChild(el);
            }
        }
    });
}

function _qfRemoveAll(selector, root) {
    _qfAll(selector, root).forEach(function (el) {
        el.remove();
    });
}

function _qfVisible(el) {
    if (!el) return false;
    var cs = window.getComputedStyle(el);
    return cs.display !== "none" && cs.visibility !== "hidden";
}

function _qfPrev(el) {
    return el ? el.previousElementSibling : null;
}

function _qfNext(el) {
    return el ? el.nextElementSibling : null;
}

function _qfInsertBefore(el, target) {
    if (!el || !target || !target.parentNode) return;
    target.parentNode.insertBefore(el, target);
}

function _qfInsertAfter(el, target) {
    if (!el || !target || !target.parentNode) return;
    target.parentNode.insertBefore(el, target.nextElementSibling);
}

function _qfInitDropit(ulElement, options) {
    if (!ulElement) return;
    var li = ulElement.firstElementChild || ulElement.querySelector("li");
    if (!li) return;
    var submenu = li.querySelector("ul");
    if (!submenu) return;

    options = options || {};

    ulElement.classList.add("dropit");
    li.classList.add("dropit-trigger");
    submenu.classList.add("dropit-submenu");

    function closeMenu() {
        li.classList.remove("dropit-open");
        submenu.style.display = "none";
    }

    function openMenu() {
        if (typeof options.beforeShow === "function") {
            options.beforeShow();
        }
        li.classList.add("dropit-open");
        submenu.style.display = "block";
    }

    if (ulElement.dataset.qfDropitBound === "1") {
        closeMenu();
        return;
    }

    if (options.action === "mouseenter") {
        li.addEventListener("mouseenter", openMenu);
        li.addEventListener("mouseleave", closeMenu);
    }

    var trigger = li.querySelector("a");
    if (trigger) {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            if (li.classList.contains("dropit-open")) closeMenu();
            else openMenu();
        });
    }

    document.addEventListener("click", function (e) {
        if (!ulElement.contains(e.target)) closeMenu();
    });

    ulElement.dataset.qfDropitBound = "1";
    closeMenu();
}

function Ptype(content, jsEval, type, displayName) {
    this.content = content;
    this.jsEval = jsEval;
    this.type = type;
    this.displayName = displayName;
}

function clearQuery() {
    openCount = 0;
    var tbody = _qfBody();
    if (tbody) {
        while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
    }
    var tfoot = _qfFoot();
    if (tfoot) while (tfoot.firstChild) tfoot.removeChild(tfoot.firstChild);
    limitUsed = false;
    _qfRemoveAll("#protoDropdown li");
    buildDropdown();
}

function loadFromObj(fullObj) {
    var obj = fullObj["query"];
    clearQuery();
    var unclosedBrackets = [];
    for (var i = 0; i < obj.length; i++) {
        for (var key in obj[i]) {
            if (typeof Ptypes[key] !== "undefined") {
                ////load default values for multiselect
                var dataType = key.split("_");
                dataType = dataType[1];
                if (typeof dataType !== "undefined") {
                    if (dataType == "multiselect") {
                        //each field
                        for (var k in obj[i][key]) {
                            var v = obj[i][key][k];
                            //check prefix for ms_
                            var fieldDataType = k.split("_");
                            fieldDataType = fieldDataType[0];
                            if (typeof fieldDataType !== "undefined") {
                                if (fieldDataType == "ms") {
                                    multiDefaults.unshift.apply(multiDefaults, v.split(","));
                                }
                            }
                        }
                    }
                }
                ////
                var tr = moveToQuery(key);
                if (typeof tr !== "undefined") {
                    for (var elemKey in obj[i][key]) {
                        if (elemKey == "logical_op") {
                            var lopSelect = tr.children[logop_index] ? tr.children[logop_index].querySelector("select") : null;
                            if (lopSelect) lopSelect.value = obj[i][key][elemKey];
                        } else {
                            var targets = _qfAll("[name='" + elemKey + "'], [data-elem-name='" + elemKey + "']", tr);
                            targets.forEach(function (el) {
                                el.value = obj[i][key][elemKey];
                                el.dispatchEvent(new Event("change", { bubbles: true }));
                            });
                        }
                    }
                }
            } else if (key == "bracket_open") {
                openCount += 1;
                unclosedBrackets[unclosedBrackets.length] = openCount;
                var trOpen = addGroupingOpen(openCount);
                if (typeof obj[i][key]["logical_op"] !== "undefined") {
                    var trOpenSel = trOpen.children[logop_index] ? trOpen.children[logop_index].querySelector("select") : null;
                    if (trOpenSel) trOpenSel.value = obj[i][key]["logical_op"];
                }
            } else if (key == "bracket_close") {
                addGroupingClose(unclosedBrackets[unclosedBrackets.length - 1]);
                unclosedBrackets.pop();
            } else {
                console.log('ERROR: Prototype "' + key + '" does not exist');
            }
        }
    }
    addDragEvents();
    checkAllForLogOp();
}

function addDragEvents() {
    _qfAll(".dragHandle").forEach(function (handle) {
        if (handle.dataset.qfDragBound === "1") return;
        handle.dataset.qfDragBound = "1";

        handle.addEventListener("mousedown", function () {
            dragEnabled = true;
            dragStartRow = this.parentNode.parentNode;
            _qfSetCursor("none");
            getGroupPairs(dragStartRow).forEach(function (r) {
                r.classList.add("queryform_highlight_row");
            });
        });

        handle.addEventListener("mouseup", function () {
            dragEnabled = false;
            _qfSetCursor("auto");
        });
    });
}

document.documentElement.addEventListener("mousemove", function (event) {
    if (dragEnabled) {
        if (dragStartY == -1) {
            dragStartY = event.clientY;
        }
        if (dragStartY - event.clientY > dragSensitivity) {
            dragStartY = event.clientY - dragSensitivity;
            moveUp(dragStartRow);
        } else if (event.clientY - dragStartY > dragSensitivity) {
            dragStartY = event.clientY + dragSensitivity;
            moveDown(dragStartRow);
        }
    }
});

document.documentElement.addEventListener("mouseup", function () {
    dragEnabled = false;
    _qfSetCursor("auto");
    if (dragStartRow != 0) {
        getGroupPairs(dragStartRow).forEach(function (r) {
            r.classList.remove("queryform_highlight_row");
        });
    }
    if (dragEnabled) {
        dragStartY = -1;
        dragStartRow = 0;
    }
});

function getGroupPairs(td) {
    if (!td || !td.getAttribute || td.getAttribute("data-group-id") === null) {
        return td ? [td] : [];
    }
    return _qfAll("[data-group-id='" + td.getAttribute("data-group-id") + "']");
}

document.addEventListener("DOMContentLoaded", function () {
    "use strict";
    buildDropdown();

    var queryForm = _qfFirst("#queryForm");
    if (queryForm) {
        queryForm.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                return false;
            }
        });
        queryForm.addEventListener("submit", function (e) {
            //pause submission
            e.preventDefault();
            //apply correct name attributes to form inputs
            buildNames();
            //continue form submission
            this.submit();
            return false;
        });
    }

    _qfInitDropit(_qfFirst("#savedDropdown"), {
        action: "mouseenter",
        beforeShow: function () {
            var submenu = _qfFirst("#savedDropdown .dropit-submenu");
            if (submenu) {
                submenu.style.left = "";
                submenu.style.right = "";
                var vw = window.innerWidth || document.documentElement.clientWidth;
                if (vw <= 760) {
                    var anchor = _qfFirst("#savedDropdown .dropit-trigger") || _qfFirst("#savedDropdown");
                    var rect = anchor ? anchor.getBoundingClientRect() : { left: 0 };
                    submenu.style.position = "absolute";
                    submenu.style.top = "100%";
                    submenu.style.left = 8 - rect.left + "px";
                    submenu.style.right = "auto";
                    submenu.style.width = Math.max(240, vw - 16) + "px";
                    submenu.style.removeProperty("inset-inline-start");
                    submenu.style.removeProperty("inset-inline-end");
                } else {
                    submenu.style.position = "";
                    submenu.style.top = "";
                    submenu.style.right = "";
                    submenu.style.setProperty("inset-inline-start", "-371px");
                    submenu.style.width = "600px";
                }
            }
        },
    });

    if (typeof jsonData !== "undefined") loadFromObj(jsonData);
});

function buildNames() {
    _qfAll("#queryTable input, #queryTable select, #queryTable textarea").forEach(function (input) {
        if (input.hasAttribute("name") && !input.hasAttribute("data-elem-name")) {
            input.setAttribute("data-elem-name", input.getAttribute("name"));
        }
        input.removeAttribute("name");
    });

    var i = 0;
    _qfAll("#queryTable .queryrow").forEach(function (tr) {
        if (typeof tr.getAttribute("data-row-type") !== "undefined" && tr.getAttribute("data-row-type") !== null) {
            var type = tr.getAttribute("data-row-type");
            var lopSelect = tr.children[logop_index] ? tr.children[logop_index].querySelector("select") : null;
            if (lopSelect && checkForLogOp(tr) && _qfVisible(lopSelect)) {
                lopSelect.setAttribute("name", "form[query][" + i + "][" + type + "][logical_op]");
            }
            var inputUsed = false;
            var fieldCell = tr.children[field_index];
            if (fieldCell) {
                _qfAll("input, select, textarea", fieldCell).forEach(function (el) {
                    if (el.hasAttribute("data-elem-name")) {
                        var elemName = el.getAttribute("data-elem-name");
                        var rowTypeHolder = el.closest("[data-row-type]");
                        var type = rowTypeHolder ? rowTypeHolder.getAttribute("data-row-type") : null;
                        var placeholder;
                        if (typeof Ptypes[type] !== "undefined") {
                            placeholder = Ptypes[type]["placeholder"];
                            var dataType = type.split("_");
                            dataType = dataType[1];
                            if (typeof dataType !== "undefined") {
                                if (dataType == "multiselect") {
                                    //replace JSON value with comma delimited values
                                    try {
                                        var obj = JSON.parse(el.getAttribute("value"));
                                        var values = [];
                                        for (var item in obj) {
                                            values.push(obj[item].value);
                                        }
                                        if (values.length == 0) throw "no values";
                                        el.setAttribute("value", values.join(","));
                                    } catch (e) {
                                        if (el.getAttribute("value") == "[]") {
                                            el.setAttribute("value", "");
                                        }
                                    }
                                }
                            }
                        }

                        if (typeof placeholder !== "undefined" && placeholder != "") {
                            elemName = elemName.replace(placeholder + "_", "");
                        }
                        inputUsed = true;
                        el.setAttribute("name", "form[query][" + i + "][" + type + "][" + elemName + "]");
                    }
                });
            }
            if (inputUsed) {
                i++;
            }
        }
    });
}

function handleAddClick(elementType) {
    moveToQuery(elementType);
}

function removeFromQuery(tr) {
    if (tr.getAttribute("data-row-type") == "bracket_open") {
        _qfAll("#queryTable .querybody [data-group-id='" + tr.getAttribute("data-group-id") + "']").forEach(function (r) {
            r.remove();
        });
        openCount -= 1;
    } else if (tr.getAttribute("data-row-type") == "randsubset_limitnumber") {
        tr.remove();
        limitUsed = false;
        buildDropdown();
    } else {
        tr.remove();
    }
    checkAllForLogOp();
}

function moveUp(tr) {
    if (tr.getAttribute("data-row-type") == "bracket_close") {
        var open = _qfFirst("[data-group-id='" + tr.getAttribute("data-group-id") + "'][data-row-type='bracket_open']");
        if (open && _qfRowPos(tr) < _qfRowPos(open) + 2) {
            // do nothing
        } else {
            var prev = _qfPrev(tr);
            if (prev && prev.getAttribute("data-group-id") === null) {
                _qfInsertBefore(tr, prev);
            } else if (prev) {
                var prevOpen = _qfFirst("[data-group-id='" + prev.getAttribute("data-group-id") + "'][data-row-type='bracket_open']");
                if (prevOpen) _qfInsertBefore(tr, prevOpen);
            }
        }
    } else if (tr.getAttribute("data-row-type") == "bracket_open") {
        var prevRow = _qfPrev(tr);
        var closeRow = _qfFirst("[data-group-id='" + tr.getAttribute("data-group-id") + "'][data-row-type='bracket_close']");
        if (prevRow && closeRow) {
            _qfInsertAfter(prevRow, closeRow);
        }
    } else {
        var prev2 = _qfPrev(tr);
        if (prev2) _qfInsertBefore(tr, prev2);
    }
    checkAllForLogOp();
}

function checkAllForLogOp() {
    _qfAll("#queryTable .querybody .queryrow").forEach(function (me) {
        var lopCell = me.children[logop_index];
        if (!lopCell) return;
        if (checkForLogOp(me)) {
            if (!lopCell.querySelector("select")) {
                me.replaceChild(_qfCellFromHtml(logicalOpPrototype), lopCell);
            }
        } else {
            if (lopCell.querySelector("select")) {
                me.replaceChild(_qfCellFromHtml(logicalOpEmptyCell), lopCell);
            }
        }
    });
}

function moveDown(tr) {
    if (tr.getAttribute("data-row-type") == "bracket_open") {
        var close = _qfFirst("[data-group-id='" + tr.getAttribute("data-group-id") + "'][data-row-type='bracket_close']");
        if (close) {
            var afterClose = _qfNext(close);
            if (afterClose) _qfInsertBefore(tr, afterClose);
        }
    } else if (tr.getAttribute("data-row-type") == "bracket_close") {
        var next = _qfNext(tr);
        if (!next) {
            // do nothing
        } else if (next.getAttribute("data-row-type") != "bracket_close" && next.getAttribute("data-row-type") != "bracket_open") {
            _qfInsertAfter(tr, next);
        } else if (next.getAttribute("data-row-type") == "bracket_close") {
            //do nothing
        } else {
            var nextClose = _qfFirst("[data-group-id='" + next.getAttribute("data-group-id") + "'][data-row-type='bracket_close']");
            if (nextClose) _qfInsertAfter(tr, nextClose);
        }
    } else {
        var next2 = _qfNext(tr);
        if (next2) _qfInsertAfter(tr, next2);
    }
    checkAllForLogOp();
}

//returns added row
function moveToQuery(elementType) {
    if (elementType == "randsubset_limitnumber") {
        var trLimit = _qfRowFromHtml(
            '<div class="orsee-listrow queryrow" data-row-type="' +
                elementType +
                '"><div class="orsee-listcell">&nbsp;</div><div class="orsee-listcell">&nbsp;</div><div class="orsee-listcell">' +
                Ptypes[elementType]["html"] +
                "</div></div>"
        );
        _qfApplyCompactFieldStyles(trLimit);
        trLimit.appendChild(_qfCellFromHtml(deletionPrototype));
        var tfoot = _qfFoot();
        if (tfoot) tfoot.appendChild(trLimit);
        limitUsed = true;
        buildDropdown();
        return trLimit;
    } else if (elementType == "brackets") {
        addGrouping();
    } else {
        var tr = _qfRowFromHtml(
            '<div class="orsee-listrow queryrow" data-row-type="' +
                elementType +
                '"><div class="orsee-listcell queryField"><div class="orsee-query-inline">' +
                Ptypes[elementType]["html"] +
                "</div></div></div>"
        );
        _qfAll("*", tr).forEach(function (el) {
            if (typeof el.getAttribute("id") !== "undefined" && el.getAttribute("id") !== null) {
                el.setAttribute("id", el.getAttribute("id").replace(Ptypes[elementType]["placeholder"], "query_item_" + queryCount));
            }
            if (typeof el.getAttribute("class") !== "undefined" && el.getAttribute("class") !== null) {
                el.setAttribute("class", el.getAttribute("class").replace(Ptypes[elementType]["placeholder"], "query_item_" + queryCount));
            }
            //remove placeholder from name
            if (typeof el.getAttribute("name") !== "undefined" && el.getAttribute("name") !== null) {
                el.setAttribute("name", el.getAttribute("name").replace(Ptypes[elementType]["placeholder"] + "_", ""));
            }
        });

        _qfApplyCompactFieldStyles(tr);
        tr.appendChild(_qfCellFromHtml(deletionPrototype));
        //add the modified prototype row to the query table
        var tbody = _qfBody();
        if (tbody) tbody.appendChild(tr);
        //add the OR/AND dropdown box to the row
        var logicalOpPrototypeCopy = _qfCellFromHtml(logicalOpPrototype);
        if (checkForLogOp(tr)) {
            tr.insertBefore(logicalOpPrototypeCopy, tr.firstChild);
        } else {
            tr.insertBefore(_qfCellFromHtml(logicalOpEmptyCell), tr.firstChild);
        }
        tr.insertBefore(_qfCellFromHtml(positionPrototype), tr.firstChild);
        //initiate the javascript in the prototype
        //replace placeholder with an ID
        var re = new RegExp(Ptypes[elementType]["placeholder"], "gi");
        var tmpJS = Ptypes[elementType]["jsEval"].replace(re, "query_item_" + queryCount);
        ("use strict");
        var tmp = eval(tmpJS);
        evaldCode["query_item_" + queryCount] = tmp;
        if (typeof window.orseeTagPickerInit === "function") {
            window.orseeTagPickerInit();
        }
        queryCount++;
        addDragEvents();
        return tr;
    }
}

function buildDropdown() {
    _qfRemoveAll("#protoDropdown li");
    for (var type in Ptypes) {
        var item = document.createElement("li");
        var a = document.createElement("a");
        a.textContent = Ptypes[type]["displayName"];
        a.href = "#";
        (function (t) {
            a.addEventListener("click", function (e) {
                e.preventDefault();
                handleAddClick(t);
            });
        })(Ptypes[type]["type"]);
        item.appendChild(a);

        if (!limitUsed || type != "randsubset_limitnumber") {
            var proto = _qfFirst("#protoDropdown");
            if (proto) proto.appendChild(item);
        }
    }

    _qfInitDropit(_qfFirst("#addDropdown"), {
        action: "mouseenter",
        beforeShow: function () {
            // kept intentionally empty to match original behavior
        },
    });
}

//checks to see if the logical and/or oparators are required
function checkForLogOp(element) {
    var prev = _qfPrev(element);
    var attribute = prev ? prev.getAttribute("data-row-type") : undefined;
    if (attribute == "bracket_open" || typeof attribute === "undefined" || attribute === null) {
        return false;
    } else if (element.getAttribute("data-row-type") == "bracket_close") {
        return false;
    } else {
        return true;
    }
}

//returns group open row
function addGroupingOpen(target) {
    if (typeof target === "undefined") {
        openCount += 1;
        target = openCount;
    }
    var trOpen = _qfRowFromHtml(
        '<div class="orsee-listrow queryrow" data-group-id="' +
            target +
            '" data-row-type="bracket_open"><div class="orsee-listcell">(<input type="hidden" data-elem-name="type" value="open"></div></div>'
    );
    var tbody = _qfBody();
    if (tbody) tbody.appendChild(trOpen);

    if (checkForLogOp(trOpen)) {
        trOpen.insertBefore(_qfCellFromHtml(logicalOpPrototype), trOpen.firstChild);
    } else {
        trOpen.insertBefore(_qfCellFromHtml(logicalOpEmptyCell), trOpen.firstChild);
    }

    trOpen.insertBefore(_qfCellFromHtml(positionPrototypeOpenBracket), trOpen.firstChild);
    trOpen.appendChild(_qfCellFromHtml(deletionPrototype));
    return trOpen;
}

function addGroupingClose(target) {
    if (typeof target === "undefined") {
        target = openCount;
    }
    var trClose = _qfRowFromHtml(
        '<div class="orsee-listrow queryrow" data-group-id="' +
            target +
            '" data-row-type="bracket_close"><div class="orsee-listcell">)<input type="hidden" data-elem-name="type" value="close"></div></div>'
    );
    var tbody = _qfBody();
    if (tbody) tbody.appendChild(trClose);

    trClose.insertBefore(_qfCellFromHtml('<div class="orsee-listcell">&nbsp;</div>'), trClose.firstChild);
    trClose.insertBefore(_qfCellFromHtml(positionPrototypeCloseBracket), trClose.firstChild);
    trClose.appendChild(_qfCellFromHtml('<div class="orsee-listcell">&nbsp;</div>'));
}

function addGrouping() {
    addGroupingOpen();
    addGroupingClose();
    addDragEvents();
    checkAllForLogOp();
}
