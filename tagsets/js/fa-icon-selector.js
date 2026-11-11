// part of orsee. see orsee.org
(function () {
    function syncSelectedRow(root, selectedIcon) {
        var options = root.querySelectorAll('[data-role="option"]');
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            if ((opt.getAttribute("data-icon") || "") === selectedIcon) {
                opt.classList.add("is-selected");
            } else {
                opt.classList.remove("is-selected");
            }
        }
    }

    function setIcon(root, icon) {
        var hidden = root.querySelector(".orsee-fa-icon-selector-value");
        var preview = root.querySelector(".orsee-fa-icon-selector-preview");
        var text = root.querySelector(".orsee-fa-icon-selector-text");
        var noneLabel = root.getAttribute("data-none-label") || "none";
        if (!hidden || !preview || !text) return;

        hidden.value = icon;
        if (icon === "") {
            preview.className = "orsee-fa-icon-selector-preview";
            text.textContent = noneLabel;
        } else {
            preview.className = "orsee-fa-icon-selector-preview fa fa-" + icon;
            text.textContent = icon;
        }
        syncSelectedRow(root, icon);
    }

    function initOne(root) {
        if (!root || root.getAttribute("data-orsee-fa-icon-selector-init") === "1") return;
        root.setAttribute("data-orsee-fa-icon-selector-init", "1");

        var toggle = root.querySelector('[data-role="toggle"]');
        var dropdown = root.querySelector('[data-role="dropdown"]');
        var search = root.querySelector('[data-role="search"]');
        var hidden = root.querySelector(".orsee-fa-icon-selector-value");
        if (!toggle || !dropdown || !search || !hidden) return;

        var current = hidden.value || "";
        if (current.substr(0, 3) === "fa-") current = current.substr(3);
        setIcon(root, current);

        toggle.addEventListener("click", function () {
            var isOpen = root.classList.contains("is-open");
            if (isOpen) {
                root.classList.remove("is-open");
            } else {
                root.classList.add("is-open");
                search.focus();
            }
        });

        search.addEventListener("input", function () {
            var q = (search.value || "").toLowerCase();
            var options = root.querySelectorAll('[data-role="option"]');
            for (var i = 0; i < options.length; i++) {
                var s = options[i].getAttribute("data-search") || "";
                options[i].style.display = q === "" || s.indexOf(q) !== -1 ? "" : "none";
            }
        });

        dropdown.addEventListener("click", function (e) {
            var option = e.target.closest('[data-role="option"]');
            if (!option) return;
            e.preventDefault();
            var icon = option.getAttribute("data-icon") || "";
            setIcon(root, icon);
            root.classList.remove("is-open");
        });

        document.addEventListener("click", function (e) {
            if (!root.contains(e.target)) root.classList.remove("is-open");
        });
    }

    function initAll() {
        var selectors = document.querySelectorAll("[data-orsee-fa-icon-selector]");
        for (var i = 0; i < selectors.length; i++) initOne(selectors[i]);
    }

    window.orseeFaIconSelectorInit = initAll;
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAll);
    } else {
        initAll();
    }
})();
