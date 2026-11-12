(function () {
    "use strict";

    function htmlEscape(value) {
        return String(value).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    function buildLabel(value, text, iconClass, iconBefore, labelClass) {
        var iconHtml = "";
        if (iconClass) {
            iconHtml = '<i class="' + htmlEscape(iconClass) + '" style="padding: 0 0.3em;"></i>';
        }
        var label = document.createElement("div");
        label.className = "switchy-labels";
        if (labelClass) {
            label.classList.add(labelClass);
        }
        label.setAttribute("swvalue", value);
        if (iconBefore) {
            label.innerHTML = iconHtml + htmlEscape(text);
        } else {
            label.innerHTML = htmlEscape(text) + iconHtml;
        }
        return label;
    }

    function SwitchyNative(selectElement) {
        this.select = selectElement;
        this.options = Array.prototype.slice.call(selectElement.options);
        this.count = this.options.length;
        this.isRtl = false;
        this.dragging = false;
        this.dragPointerId = null;
        this.dragLeft = 0;
        this.lastIndex = this.getSelectedIndex();

        if (this.count < 2) {
            return;
        }
        if (this.select.dataset.switchyEnhanced === "1") {
            return;
        }
        if (this.hasExistingSwitchyMarkup()) {
            this.select.dataset.switchyEnhanced = "1";
            return;
        }

        this.select.dataset.switchyEnhanced = "1";
        this.render();
        this.isRtl = window.getComputedStyle(this.select).direction === "rtl";
        this.bind();
        this.updateFromSelect(false);
    }

    SwitchyNative.prototype.hasExistingSwitchyMarkup = function () {
        var next = this.select.nextElementSibling;
        var prev = this.select.previousElementSibling;
        if (next && next.classList.contains("switchy-container")) {
            return true;
        }
        if (prev && prev.classList.contains("switchy-labels") && next && next.classList.contains("switchy-container")) {
            return true;
        }
        return false;
    };

    SwitchyNative.prototype.getSelectedIndex = function () {
        var index = this.select.selectedIndex;
        if (index < 0) {
            index = 0;
        }
        if (index >= this.count) {
            index = this.count - 1;
        }
        return index;
    };

    SwitchyNative.prototype.render = function () {
        var first = this.options[0];
        var last = this.options[this.count - 1];
        var firstIcon = this.select.getAttribute("firsticon");
        var secondIcon = this.select.getAttribute("secondicon");

        this.firstLabel = buildLabel(first.value, first.text, firstIcon, true, "switchy-labels--off");
        this.lastLabel = buildLabel(last.value, last.text, secondIcon, false, "switchy-labels--on");

        this.container = document.createElement("div");
        this.container.className = "switchy-container";
        this.container.tabIndex = 0;
        this.container.setAttribute("role", "switch");

        this.bar = document.createElement("div");
        this.bar.className = "switchy-bar";

        this.slider = document.createElement("div");
        this.slider.className = "switchy-slider";

        this.bar.appendChild(this.slider);
        this.container.appendChild(this.bar);

        this.select.style.display = "none";

        this.select.parentNode.insertBefore(this.firstLabel, this.select.nextSibling);
        this.select.parentNode.insertBefore(this.container, this.firstLabel.nextSibling);
        this.select.parentNode.insertBefore(this.lastLabel, this.container.nextSibling);
    };

    SwitchyNative.prototype.bind = function () {
        var self = this;

        this.select.addEventListener("change", function () {
            self.updateFromSelect(true);
        });

        this.firstLabel.addEventListener("click", function () {
            self.setByValue(self.options[0].value, true);
        });

        this.lastLabel.addEventListener("click", function () {
            self.setByValue(self.options[self.count - 1].value, true);
        });

        this.bar.addEventListener("click", function (event) {
            var index = self.indexFromClientX(event.clientX);
            self.setByIndex(index, true);
        });

        this.container.addEventListener("keydown", function (event) {
            var index = self.getSelectedIndex();
            if (event.key === "ArrowLeft" || event.key === "ArrowDown") {
                event.preventDefault();
                self.setByIndex(Math.max(0, index - 1), true);
            } else if (event.key === "ArrowRight" || event.key === "ArrowUp") {
                event.preventDefault();
                self.setByIndex(Math.min(self.count - 1, index + 1), true);
            } else if (event.key === "Home") {
                event.preventDefault();
                self.setByIndex(0, true);
            } else if (event.key === "End") {
                event.preventDefault();
                self.setByIndex(self.count - 1, true);
            } else if (event.key === " " || event.key === "Enter") {
                event.preventDefault();
                var next = index + 1;
                if (next >= self.count) {
                    next = 0;
                }
                self.setByIndex(next, true);
            }
        });

        this.slider.addEventListener("pointerdown", function (event) {
            event.preventDefault();
            self.dragging = true;
            self.dragPointerId = event.pointerId;
            self.slider.setPointerCapture(event.pointerId);
            self.slider.style.transition = "";
            self.dragLeft = self.slider.offsetLeft;
        });

        this.slider.addEventListener("pointermove", function (event) {
            var minLeft;
            var maxLeft;
            if (!self.dragging || self.dragPointerId !== event.pointerId) {
                return;
            }
            minLeft = 0;
            maxLeft = Math.max(0, self.bar.clientWidth - self.slider.offsetWidth);
            self.dragLeft += event.movementX;
            if (self.dragLeft < minLeft) {
                self.dragLeft = minLeft;
            }
            if (self.dragLeft > maxLeft) {
                self.dragLeft = maxLeft;
            }
            self.slider.style.left = self.dragLeft + "px";
        });

        this.slider.addEventListener("pointerup", function (event) {
            if (!self.dragging || self.dragPointerId !== event.pointerId) {
                return;
            }
            self.dragging = false;
            self.slider.releasePointerCapture(event.pointerId);
            self.dragPointerId = null;
            self.setByIndex(self.indexFromSliderLeft(), true);
        });

        this.slider.addEventListener("pointercancel", function (event) {
            if (!self.dragging || self.dragPointerId !== event.pointerId) {
                return;
            }
            self.dragging = false;
            self.dragPointerId = null;
            self.updateFromSelect(false);
        });

        window.addEventListener("resize", function () {
            self.updateFromSelect(false);
        });
    };

    SwitchyNative.prototype.indexFromClientX = function (clientX) {
        var rect = this.bar.getBoundingClientRect();
        var width = rect.width;
        var step;
        var offsetX;
        if (this.count <= 1 || width <= 0) {
            return 0;
        }
        step = width / (this.count - 1);
        offsetX = this.isRtl ? rect.right - clientX : clientX - rect.left;
        return Math.max(0, Math.min(this.count - 1, Math.round(offsetX / step)));
    };

    SwitchyNative.prototype.indexFromSliderLeft = function () {
        var maxLeft = Math.max(0, this.bar.clientWidth - this.slider.offsetWidth);
        var center = this.slider.offsetLeft + this.slider.offsetWidth / 2;
        var normalized = maxLeft > 0 ? center / maxLeft : 0;
        if (this.isRtl) {
            normalized = 1 - normalized;
        }
        return Math.max(0, Math.min(this.count - 1, Math.round(normalized * (this.count - 1))));
    };

    SwitchyNative.prototype.sliderLeftForIndex = function (index) {
        var maxLeft = Math.max(0, this.bar.clientWidth - this.slider.offsetWidth);
        var left;
        var edgeInset = 3;
        if (this.count <= 1) {
            return 0;
        }
        left = Math.round((index / (this.count - 1)) * maxLeft);
        if (this.isRtl) {
            left = Math.max(0, maxLeft - left);
            if (index === 0 && left > edgeInset) {
                left -= edgeInset;
                left = Math.min(maxLeft, left + 1);
            }
        } else if (index === this.count - 1 && left > 1) {
            left -= 2;
        }
        return left;
    };

    SwitchyNative.prototype.setByValue = function (value, triggerChange) {
        var index = 0;
        var i;
        for (i = 0; i < this.options.length; i++) {
            if (this.options[i].value === value) {
                index = i;
                break;
            }
        }
        this.setByIndex(index, triggerChange);
    };

    SwitchyNative.prototype.setByIndex = function (index, triggerChange) {
        if (index < 0) {
            index = 0;
        }
        if (index >= this.count) {
            index = this.count - 1;
        }
        this.select.selectedIndex = index;
        this.lastIndex = index;
        this.updateFromSelect(true);
        if (triggerChange) {
            this.select.dispatchEvent(new Event("change", { bubbles: true }));
        }
    };

    SwitchyNative.prototype.updateFromSelect = function (animated) {
        var index = this.getSelectedIndex();
        var left = this.sliderLeftForIndex(index);
        var firstIndex = 0;
        var lastIndex = this.count - 1;
        var barColor = "var(--color-border-strong)";

        if (index === firstIndex) {
            barColor = "var(--color-yesnoswitch-no)";
        } else if (index === lastIndex) {
            barColor = "var(--color-yesnoswitch-yes)";
        }

        if (animated) {
            this.slider.style.transition = "left 140ms ease";
            this.bar.style.transition = "background-color 140ms ease";
        } else {
            this.slider.style.transition = "";
            this.bar.style.transition = "";
        }

        this.slider.style.left = left + "px";
        this.bar.style.backgroundColor = barColor;
        this.container.setAttribute("aria-checked", index === lastIndex ? "true" : "false");
        this.container.setAttribute("data-switchy-index", String(index));
        this.lastIndex = index;
    };

    function enhanceAll(root) {
        var scope = root || document;
        var selects = scope.querySelectorAll("select[data-elem-name='yesnoswitch']");
        var i;
        for (i = 0; i < selects.length; i++) {
            new SwitchyNative(selects[i]);
        }
    }

    window.osmeaSwitchyEnhanceAll = enhanceAll;

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () {
            enhanceAll(document);
        });
    } else {
        enhanceAll(document);
    }
})();
