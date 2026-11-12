(function (window, document) {
    "use strict";

    function createElementFromHTML(html) {
        var template = document.createElement("template");
        template.innerHTML = html.trim();
        return template.content.firstElementChild;
    }

    function ListTool(rows, table, addButton, formName) {
        this.formName = formName;
        this.rows = rows || {};
        this.elementSelector = table || null;
        this.tbody = this.elementSelector ? this.elementSelector.querySelector(".listbody") : null;
        this.addButtonSelector = addButton || null;
        this.dragEnabled = false;
        this.dragStartY = -1;
        this.dragSensitivity = 10;
        this.dragRow = null;
        this.repeatableCounters = {};

        if (!this.elementSelector || !this.tbody) {
            return;
        }

        this.rowsSorted = this.sortRows();
        this.buildDefaultList();
        this.buildDropdown();
        this.bindGlobalDragListeners();
    }

    ListTool.prototype.getSortedRow = function (name) {
        for (var i = 0; i < this.rowsSorted.length; i++) {
            if (this.rowsSorted[i].name === name) {
                return { row: this.rows[name], sortedID: i };
            }
        }
        return null;
    };

    ListTool.prototype.sortRows = function () {
        var rowsSorted = [];
        var key;
        for (key in this.rows) {
            if (Object.prototype.hasOwnProperty.call(this.rows, key)) {
                rowsSorted.push({
                    name: key,
                    fixed_position: this.rows[key].fixed_position,
                    drag: this.rows[key].allow_drag,
                    remove: this.rows[key].allow_remove,
                });
            }
        }

        rowsSorted.sort(function (a, b) {
            if (a.fixed_position < 0 && b.fixed_position < 0) {
                if (a.fixed_position > b.fixed_position) {
                    return 1;
                }
            } else if (a.fixed_position > 0 && b.fixed_position > 0) {
                if (a.fixed_position > b.fixed_position) {
                    return 1;
                }
            } else if (a.fixed_position < b.fixed_position) {
                return 1;
            }
            return -1;
        });

        var order = [
            { drag: false, positivePos: true },
            { drag: true, positivePos: true },
            { drag: true, positivePos: false },
            { drag: false, positivePos: false },
        ];

        function checkGroup(row) {
            for (var i = 0; i < order.length; i++) {
                if (row.fixed_position < 0 && order[i].positivePos === true) {
                    continue;
                }
                if (row.fixed_position >= 0 && order[i].positivePos === false) {
                    continue;
                }
                if (row.drag === false && order[i].drag === true) {
                    continue;
                }
                if (row.drag === true && order[i].drag === false) {
                    continue;
                }
                return i;
            }
            return -1;
        }

        var grouped = [];
        for (var step = 0; step < order.length; step++) {
            for (var j = 0; j < rowsSorted.length; j++) {
                if (checkGroup(rowsSorted[j]) === step) {
                    grouped.push(rowsSorted[j]);
                }
            }
        }
        return grouped;
    };

    ListTool.prototype.buildDefaultList = function () {
        for (var i = 0; i < this.rowsSorted.length; i++) {
            var rowName = this.rowsSorted[i].name;
            if (this.rows[rowName] && this.rows[rowName].on_list) {
                this.addRow(rowName, true);
            }
        }
    };

    ListTool.prototype.bindGlobalDragListeners = function () {
        var me = this;
        document.documentElement.addEventListener("mousemove", function (event) {
            me.handleMouseMove(event.clientY);
        });
        document.documentElement.addEventListener("mouseup", function () {
            me.handleMouseUp();
        });
        document.documentElement.addEventListener("touchend", function () {
            me.handleMouseUp();
        });
    };

    ListTool.prototype.bindDropdownBehavior = function (root, trigger, submenu) {
        if (!root || !trigger || !submenu) {
            return;
        }

        function hideMenu() {
            root.classList.remove("dropit-open");
            submenu.style.display = "none";
        }

        function showMenu() {
            if (trigger.hasAttribute("disabled")) {
                hideMenu();
                return;
            }
            root.classList.add("dropit-open");
            submenu.style.display = "block";
        }

        trigger.addEventListener("click", function (event) {
            event.preventDefault();
            if (root.classList.contains("dropit-open")) {
                hideMenu();
            } else {
                showMenu();
            }
        });

        root.addEventListener("mouseenter", function () {
            showMenu();
        });

        root.addEventListener("mouseleave", function () {
            hideMenu();
        });

        document.addEventListener("click", function (event) {
            if (!root.contains(event.target)) {
                hideMenu();
            }
        });
    };

    ListTool.prototype.buildDropdown = function () {
        if (!this.addButtonSelector) {
            return;
        }

        var me = this;
        var dropdownItems = this.addButtonSelector.querySelector(".dropdownItems");
        if (!dropdownItems) {
            return;
        }
        var trigger = this.addButtonSelector.querySelector(".button");
        var rootLi = this.addButtonSelector.querySelector("li");

        this.addButtonSelector.classList.add("dropit");
        if (rootLi) {
            rootLi.classList.add("dropit-trigger");
        }
        dropdownItems.classList.add("dropit-submenu");

        dropdownItems.innerHTML = "";

        var count = 0;
        var rowName;
        for (rowName in this.rows) {
            if (Object.prototype.hasOwnProperty.call(this.rows, rowName)) {
                if (!this.rows[rowName].repeatable && this.elementSelector.querySelector("[data-instance='" + rowName + "']")) {
                    continue;
                }
                var row = this.rows[rowName];
                var li = createElementFromHTML('<li><a href="#"></a></li>');
                li.querySelector("a").textContent = row.display_text;
                (function (name) {
                    li.addEventListener("click", function (event) {
                        event.preventDefault();
                        me.handleAddClick(name);
                    });
                })(rowName);
                dropdownItems.appendChild(li);
                count++;
            }
        }

        if (trigger) {
            if (count < 1) {
                trigger.setAttribute("disabled", "disabled");
            } else {
                trigger.removeAttribute("disabled");
            }
        }

        this.bindDropdownBehavior(this.addButtonSelector, trigger, dropdownItems);
    };

    ListTool.prototype.handleAddClick = function (rowName) {
        this.addRow(rowName);
        this.buildDropdown();
    };

    ListTool.prototype.handleDeleteClick = function (rowElement) {
        if (rowElement && rowElement.parentNode) {
            rowElement.parentNode.removeChild(rowElement);
        }
        this.buildDropdown();
    };

    ListTool.prototype.handleMoveMouseDown = function (rowElement) {
        this.dragRow = rowElement;
        this.dragEnabled = true;
        document.documentElement.style.cursor = "none";
        document.body.style.cursor = "none";
        var buttons = document.querySelectorAll("button");
        buttons.forEach(function (button) {
            button.style.cursor = "none";
        });
        if (this.dragRow) {
            this.dragRow.classList.add("highlight_row");
        }
    };

    ListTool.prototype.handleMouseMove = function (clientY) {
        if (!this.dragEnabled || !this.dragRow) {
            return;
        }
        if (this.dragStartY === -1) {
            this.dragStartY = clientY;
        }
        if (this.dragStartY - clientY > this.dragSensitivity) {
            this.dragStartY = clientY - this.dragSensitivity;
            this.moveRow("up", this.dragRow);
        } else if (clientY - this.dragStartY > this.dragSensitivity) {
            this.dragStartY = clientY + this.dragSensitivity;
            this.moveRow("down", this.dragRow);
        }
    };

    ListTool.prototype.handleMouseUp = function () {
        this.dragEnabled = false;
        this.dragStartY = -1;
        document.documentElement.style.cursor = "auto";
        document.body.style.cursor = "auto";
        var buttons = document.querySelectorAll("button");
        buttons.forEach(function (button) {
            button.style.cursor = "";
        });
        if (this.dragRow) {
            this.dragRow.classList.remove("highlight_row");
        }
    };

    ListTool.prototype.moveRow = function (direction, rowElement) {
        if (!rowElement || !rowElement.parentNode) {
            return;
        }
        if (direction === "up") {
            var prev = rowElement.previousElementSibling;
            if (!prev) {
                return;
            }
            var prevName = prev.getAttribute("data-basename") || prev.getAttribute("data-instance");
            if (this.rows[prevName] && this.rows[prevName].allow_drag) {
                rowElement.parentNode.insertBefore(rowElement, prev);
            }
            return;
        }
        var next = rowElement.nextElementSibling;
        if (!next) {
            return;
        }
        var nextName = next.getAttribute("data-basename") || next.getAttribute("data-instance");
        if (this.rows[nextName] && this.rows[nextName].allow_drag) {
            rowElement.parentNode.insertBefore(next, rowElement);
        }
    };

    ListTool.prototype.addRow = function (rowName, nocheck) {
        if (typeof nocheck === "undefined") {
            nocheck = false;
        }
        var row = this.rows[rowName];
        if (!row) {
            return;
        }
        var instanceName = rowName;
        if (row.repeatable) {
            if (!this.repeatableCounters[rowName]) {
                this.repeatableCounters[rowName] = 1;
            } else {
                this.repeatableCounters[rowName] += 1;
            }
            instanceName = rowName + "__" + this.repeatableCounters[rowName];
        }
        var me = this;
        var rowCols = row.cols || "";
        var rowStyle = row.row_style ? ' style="' + row.row_style + '"' : "";
        rowCols = rowCols.split("__INSTANCE__").join(instanceName);
        var rowString =
            '<div class="orsee-listrow listrow"' +
            rowStyle +
            ' data-instance="' +
            instanceName +
            '" data-basename="' +
            rowName +
            '"><div class="orsee-listcell orsee-listcell-drag"><input type="hidden" name="' +
            this.formName +
            '[]" value="' +
            instanceName +
            '" /></div>' +
            rowCols +
            '<div class="orsee-listcell orsee-listcell-action"></div></div>';
        var addRow = createElementFromHTML(rowString);

        if (row.allow_drag) {
            var moveButton = createElementFromHTML(
                '<button class="fa-bars dragHandle" style="font-family: FontAwesome; height: 2em; width: 2em; font-size: 1em; cursor: auto;" type="button"></button>'
            );
            moveButton.addEventListener("mousedown", function () {
                me.handleMoveMouseDown(addRow);
            });
            moveButton.addEventListener("touchstart", function () {
                me.handleMoveMouseDown(addRow);
            });
            moveButton.addEventListener("touchmove", function (event) {
                if (event.touches && event.touches.length === 1) {
                    me.handleMouseMove(event.touches[0].clientY);
                }
                event.preventDefault();
            });
            addRow.querySelector(".orsee-listcell-drag").appendChild(moveButton);
        }

        if (row.allow_remove) {
            var deleteIcon = createElementFromHTML('<i class="fa fa-times-circle" style="color: red;"></i>');
            deleteIcon.addEventListener("click", function () {
                me.handleDeleteClick(addRow);
            });
            addRow.querySelector(".orsee-listcell-action").appendChild(deleteIcon);
        }

        var existingRows = this.tbody.querySelectorAll("[data-instance]");
        if (nocheck || existingRows.length < 1) {
            this.tbody.appendChild(addRow);
            return;
        }

        if (existingRows.length === 1) {
            var onlyRow = this.tbody.firstElementChild;
            var onlyRowBase = onlyRow.getAttribute("data-basename") || onlyRow.getAttribute("data-instance");
            var onlyRowData = this.getSortedRow(onlyRowBase);
            var newRowData = this.getSortedRow(rowName);
            if (onlyRowData && newRowData && onlyRowData.sortedID > newRowData.sortedID) {
                this.tbody.insertBefore(addRow, onlyRow);
            } else {
                this.tbody.insertBefore(addRow, onlyRow.nextSibling);
            }
            return;
        }

        var lowestDistance = this.rowsSorted.length - 1;
        var closestID = this.rowsSorted.length - 1;
        existingRows.forEach(function (current) {
            var currentName = current.getAttribute("data-basename") || current.getAttribute("data-instance");
            var currentData = me.getSortedRow(currentName);
            var newData = me.getSortedRow(rowName);
            if (!currentData || !newData) {
                return;
            }
            if (Math.abs(currentData.sortedID - newData.sortedID) <= Math.abs(lowestDistance)) {
                closestID = currentData.sortedID;
                lowestDistance = currentData.sortedID - newData.sortedID;
            }
        });

        var subject = this.elementSelector.querySelector("[data-basename='" + this.rowsSorted[closestID].name + "']");
        if (!subject) {
            this.tbody.appendChild(addRow);
            return;
        }
        if (lowestDistance > 0) {
            this.tbody.insertBefore(addRow, subject);
        } else {
            this.tbody.insertBefore(addRow, subject.nextSibling);
        }
    };

    // Bind shared confirm-modal behavior for listtool delete icons.
    function listtoolBindDeleteConfirm(listRoots, message, afterDelete) {
        var roots = listRoots || [];
        if (!Array.isArray(roots) || roots.length === 0) {
            return;
        }
        var confirmBox = document.getElementById("orsee-public-confirm");
        var confirmText = document.getElementById("orsee-public-confirm-text");
        var confirmNo = document.getElementById("orsee-public-confirm-no");
        var confirmYes = document.getElementById("orsee-public-confirm-yes");
        if (!confirmBox || !confirmText || !confirmNo || !confirmYes) {
            return;
        }
        var pendingRow = null;

        function closeConfirm() {
            confirmBox.classList.add("is-hidden");
            pendingRow = null;
        }

        function askForDelete(event) {
            var target = event.target;
            if (!target || !target.classList || !target.classList.contains("fa-times-circle")) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            pendingRow = target.closest(".orsee-listrow");
            if (!pendingRow) {
                return;
            }
            confirmText.textContent = message;
            confirmBox.classList.remove("is-hidden");
        }

        roots.forEach(function (listRoot) {
            if (!listRoot || !listRoot.addEventListener) {
                return;
            }
            listRoot.addEventListener("click", askForDelete, true);
        });

        confirmNo.addEventListener("click", function () {
            closeConfirm();
        });

        confirmYes.addEventListener("click", function () {
            if (pendingRow && pendingRow.parentNode) {
                pendingRow.parentNode.removeChild(pendingRow);
                if (typeof afterDelete === "function") {
                    afterDelete(pendingRow);
                }
            }
            closeConfirm();
        });

        confirmBox.addEventListener("click", function (event) {
            if (event.target === confirmBox) {
                closeConfirm();
            }
        });
    }

    window.ListTool = ListTool;
    window.listtool__bind_delete_confirm = listtoolBindDeleteConfirm;
})(window, document);
