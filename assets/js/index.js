(function () {
    var root = document.documentElement;
    var themeToggle = document.getElementById("theme-toggle");
    var recordForm = document.getElementById("record-form");
    var ticketRecordForm = document.getElementById("ticket-record-form");
    var modal = document.getElementById("saved-modal");
    var okButton = document.getElementById("saved-modal-ok");
    var sidebarToggle = document.getElementById("sidebar-toggle");
    var mobileSidebarToggle = document.getElementById("mobile-sidebar-toggle");
    var sidebarScrim = document.getElementById("sidebar-scrim");
    var appSidebar = document.getElementById("app-sidebar");
    var scrollRestoreKey = "systemMonitoringSummaryScroll";

    if (sidebarToggle) {
        var updateSidebarToggle = function () {
            var isCollapsed = root.classList.contains("sidebar-collapsed");
            sidebarToggle.setAttribute("aria-expanded", isCollapsed ? "false" : "true");
            sidebarToggle.setAttribute("aria-label", isCollapsed ? "Expand sidebar" : "Collapse sidebar");
            sidebarToggle.setAttribute("title", isCollapsed ? "Expand sidebar" : "Collapse sidebar");
        };

        sidebarToggle.addEventListener("click", function () {
            root.classList.toggle("sidebar-collapsed");

            try {
                window.localStorage.setItem(
                    "systemMonitoringSidebar",
                    root.classList.contains("sidebar-collapsed") ? "collapsed" : "expanded"
                );
            } catch (error) {
            }

            updateSidebarToggle();
        });

        updateSidebarToggle();
    }

    if (mobileSidebarToggle && sidebarScrim && appSidebar) {
        var setMobileSidebarOpen = function (isOpen) {
            root.classList.toggle("sidebar-open", isOpen);
            mobileSidebarToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
            sidebarScrim.hidden = !isOpen;
            document.body.style.overflow = isOpen ? "hidden" : "";
        };

        mobileSidebarToggle.addEventListener("click", function () {
            setMobileSidebarOpen(!root.classList.contains("sidebar-open"));
        });

        sidebarScrim.addEventListener("click", function () {
            setMobileSidebarOpen(false);
        });

        appSidebar.addEventListener("click", function (event) {
            if (event.target.closest("a") && window.matchMedia("(max-width: 760px)").matches) {
                setMobileSidebarOpen(false);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && root.classList.contains("sidebar-open")) {
                setMobileSidebarOpen(false);
                mobileSidebarToggle.focus();
            }
        });

        window.addEventListener("resize", function () {
            if (!window.matchMedia("(max-width: 760px)").matches) {
                setMobileSidebarOpen(false);
            }
        });
    }

    var monthPickers = document.querySelectorAll("[data-month-picker]");
    if (monthPickers.length > 0) {
        var monthPickerNames = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        var closeMonthPickers = function (exceptPicker) {
            for (var pickerIndex = 0; pickerIndex < monthPickers.length; pickerIndex += 1) {
                if (monthPickers[pickerIndex] === exceptPicker) {
                    continue;
                }

                var pickerPopover = monthPickers[pickerIndex].querySelector("[data-month-picker-popover]");
                var pickerTrigger = monthPickers[pickerIndex].querySelector("[data-month-picker-trigger]");
                if (pickerPopover) {
                    pickerPopover.hidden = true;
                }
                if (pickerTrigger) {
                    pickerTrigger.setAttribute("aria-expanded", "false");
                }
            }
        };

        for (var monthPickerIndex = 0; monthPickerIndex < monthPickers.length; monthPickerIndex += 1) {
            (function (picker) {
                var input = picker.querySelector("[data-month-picker-input]");
                var trigger = picker.querySelector("[data-month-picker-trigger]");
                var label = picker.querySelector("[data-month-picker-label]");
                var popover = picker.querySelector("[data-month-picker-popover]");
                var yearLabel = picker.querySelector("[data-month-picker-year]");
                var previousButton = picker.querySelector("[data-month-picker-previous]");
                var nextButton = picker.querySelector("[data-month-picker-next]");
                var currentButton = picker.querySelector("[data-month-picker-current]");
                var clearButton = picker.querySelector("[data-month-picker-clear]");
                var monthButtons = picker.querySelectorAll("[data-month-picker-month]");
                var displayYear = parseInt(picker.getAttribute("data-display-year"), 10) || new Date().getFullYear();

                if (!input || !trigger || !label || !popover || !yearLabel) {
                    return;
                }

                var formatSelectedMonth = function () {
                    var match = /^(\d{4})-(\d{2})$/.exec(input.value);
                    if (!match) {
                        return "Select month";
                    }

                    var monthIndex = parseInt(match[2], 10) - 1;
                    return monthPickerNames[monthIndex] + " " + match[1];
                };

                var renderPicker = function () {
                    yearLabel.textContent = displayYear;
                    label.textContent = formatSelectedMonth();

                    for (var buttonIndex = 0; buttonIndex < monthButtons.length; buttonIndex += 1) {
                        var monthValue = monthButtons[buttonIndex].getAttribute("data-month-picker-month");
                        var selectedValue = displayYear + "-" + monthValue;
                        monthButtons[buttonIndex].setAttribute(
                            "aria-pressed",
                            input.value === selectedValue ? "true" : "false"
                        );
                    }
                };

                var closePicker = function () {
                    popover.hidden = true;
                    trigger.setAttribute("aria-expanded", "false");
                };

                var selectMonth = function (year, month) {
                    displayYear = year;
                    input.value = year + "-" + month;
                    renderPicker();
                    closePicker();
                    trigger.focus();
                };

                trigger.addEventListener("click", function () {
                    var willOpen = popover.hidden;
                    closeMonthPickers(picker);
                    popover.hidden = !willOpen;
                    trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");

                    if (willOpen) {
                        var selectedMatch = /^(\d{4})-(\d{2})$/.exec(input.value);
                        if (selectedMatch) {
                            displayYear = parseInt(selectedMatch[1], 10);
                        }
                        renderPicker();

                        var selectedButton = picker.querySelector('[data-month-picker-month][aria-pressed="true"]');
                        (selectedButton || monthButtons[0]).focus();
                    }
                });

                if (previousButton) {
                    previousButton.addEventListener("click", function () {
                        displayYear -= 1;
                        renderPicker();
                    });
                }

                if (nextButton) {
                    nextButton.addEventListener("click", function () {
                        displayYear += 1;
                        renderPicker();
                    });
                }

                for (var monthButtonIndex = 0; monthButtonIndex < monthButtons.length; monthButtonIndex += 1) {
                    monthButtons[monthButtonIndex].addEventListener("click", function () {
                        selectMonth(displayYear, this.getAttribute("data-month-picker-month"));
                    });
                }

                if (currentButton) {
                    currentButton.addEventListener("click", function () {
                        var currentDate = new Date();
                        var currentMonth = String(currentDate.getMonth() + 1).padStart(2, "0");
                        selectMonth(currentDate.getFullYear(), currentMonth);
                    });
                }

                if (clearButton) {
                    clearButton.addEventListener("click", function () {
                        input.value = "";
                        renderPicker();
                        closePicker();
                        trigger.focus();
                    });
                }

                picker.addEventListener("keydown", function (event) {
                    if (event.key === "Escape" && !popover.hidden) {
                        event.preventDefault();
                        closePicker();
                        trigger.focus();
                    }
                });

                renderPicker();
            }(monthPickers[monthPickerIndex]));
        }

        document.addEventListener("click", function (event) {
            for (var pickerIndex = 0; pickerIndex < monthPickers.length; pickerIndex += 1) {
                if (monthPickers[pickerIndex].contains(event.target)) {
                    return;
                }
            }

            closeMonthPickers(null);
        });
    }

    var getScrollY = function () {
        return window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
    };

    var saveSummaryScrollPosition = function () {
        try {
            window.sessionStorage.setItem(scrollRestoreKey, JSON.stringify({
                path: window.location.pathname,
                y: getScrollY(),
                savedAt: Date.now()
            }));
        } catch (error) {
        }
    };

    var restoreSummaryScrollPosition = function () {
        var savedScroll = null;

        try {
            savedScroll = JSON.parse(window.sessionStorage.getItem(scrollRestoreKey) || "null");
            window.sessionStorage.removeItem(scrollRestoreKey);
        } catch (error) {
            return;
        }

        if (
            !savedScroll
            || savedScroll.path !== window.location.pathname
            || typeof savedScroll.y !== "number"
            || Date.now() - savedScroll.savedAt > 60000
        ) {
            return;
        }

        var restore = function () {
            window.scrollTo(0, savedScroll.y);
        };

        window.setTimeout(restore, 0);
        window.setTimeout(restore, 100);
    };

    var isSummaryActionForm = function (form) {
        return form && (
            form.classList.contains("monitoring-action-form")
            || form.classList.contains("ticket-status-form")
            || form.classList.contains("ticket-delete-form")
        );
    };

    document.addEventListener("change", function (event) {
        var field = event.target;

        if (!field || !field.form || !isSummaryActionForm(field.form) || field.value === "") {
            return;
        }

        saveSummaryScrollPosition();
    }, true);

    document.addEventListener("submit", function (event) {
        if (event.target && event.target.classList.contains("ticket-delete-form")) {
            var ticketNumber = event.target.getAttribute("data-ticket-number") || "this ticket";
            if (!window.confirm("Delete " + ticketNumber + "? This cannot be undone.")) {
                event.preventDefault();
                return;
            }
        }

        if (
            event.target
            && event.target.hasAttribute("data-memo-issued-confirm")
            && !window.confirm("Confirm memo issuance for the selected date?")
        ) {
            event.preventDefault();
            var memoIssuedCheckbox = event.target.querySelector('input[type="checkbox"]');
            if (memoIssuedCheckbox) {
                memoIssuedCheckbox.checked = false;
            }
            return;
        }

        if (isSummaryActionForm(event.target)) {
            saveSummaryScrollPosition();
        }
    }, true);

    restoreSummaryScrollPosition();

    var memoPrintLinks = document.querySelectorAll("[data-memo-print-link]");
    var bindMemoPrintLink = function (link) {
        if (
            typeof window.fetch !== "function"
            || !window.URL
            || typeof window.URL.createObjectURL !== "function"
        ) {
            return;
        }

        link.addEventListener("click", function (event) {
            event.preventDefault();
            if (link.getAttribute("aria-busy") === "true") {
                return;
            }

            link.setAttribute("aria-busy", "true");
            saveSummaryScrollPosition();
            var filename = "monitoring_memo.docx";

            window.fetch(link.href, { credentials: "same-origin", cache: "no-store" })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error("Memo download failed.");
                    }

                    var disposition = response.headers.get("Content-Disposition") || "";
                    var filenameMatch = /filename="?([^";]+)"?/i.exec(disposition);
                    if (filenameMatch && filenameMatch[1]) {
                        filename = filenameMatch[1];
                    }

                    return response.blob();
                })
                .then(function (memoBlob) {
                    var objectUrl = window.URL.createObjectURL(memoBlob);
                    var downloadLink = document.createElement("a");
                    downloadLink.href = objectUrl;
                    downloadLink.download = filename;
                    downloadLink.style.display = "none";
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();

                    window.setTimeout(function () {
                        window.URL.revokeObjectURL(objectUrl);
                        window.location.reload();
                    }, 500);
                })
                .catch(function () {
                    link.removeAttribute("aria-busy");
                    window.alert("Unable to generate the memo right now.");
                });
        });
    };

    for (var memoLinkIndex = 0; memoLinkIndex < memoPrintLinks.length; memoLinkIndex += 1) {
        bindMemoPrintLink(memoPrintLinks[memoLinkIndex]);
    }

    var applyUppercaseBehavior = function (fields) {
        for (var index = 0; index < fields.length; index += 1) {
            fields[index].addEventListener("input", function () {
                this.value = this.value.toUpperCase();
            });

            if (fields[index].value) {
                fields[index].value = fields[index].value.toUpperCase();
            }
        }
    };

    if (recordForm) {
        var recordUppercaseFields = recordForm.querySelectorAll('input[type="text"], textarea');
        var ticketInput = document.getElementById("ticket");
        var ticketMonitoringLink = document.getElementById("ticket-monitoring-link");
        var userNameInput = document.getElementById("user-name");
        var incidentReportInput = document.getElementById("incident-report-image");
        var offenseInput = document.getElementById("offense");
        var pendingStatusInput = recordForm.querySelector('input[name="status[]"][value="Pending"]');
        var classificationInputs = recordForm.querySelectorAll('input[name="classification"]');
        var userErrorMessage = "USER is required when CLASSIFICATION is USER ERROR.";
        var incidentReportMessage = "An incident report attachment is required for this user's first User Error.";
        var userErrorCounts = {};

        try {
            userErrorCounts = JSON.parse(recordForm.dataset.userErrorCounts || "{}");
        } catch (error) {
            userErrorCounts = {};
        }

        applyUppercaseBehavior(recordUppercaseFields);

        if (offenseInput && pendingStatusInput && offenseInput.dataset.incidentReportOffense) {
            var updateIncidentReportStatus = function () {
                if (
                    offenseInput.value.trim().toUpperCase() === offenseInput.dataset.incidentReportOffense.trim().toUpperCase()
                ) {
                    pendingStatusInput.checked = true;
                }
            };

            offenseInput.addEventListener("input", updateIncidentReportStatus);
            offenseInput.addEventListener("change", updateIncidentReportStatus);
            recordForm.addEventListener("submit", updateIncidentReportStatus);
            updateIncidentReportStatus();
        }

        if (classificationInputs.length > 0 && (userNameInput || incidentReportInput)) {
            var updateUserErrorValidation = function () {
                var hasUserError = false;

                for (var inputIndex = 0; inputIndex < classificationInputs.length; inputIndex += 1) {
                    if (
                        classificationInputs[inputIndex].value === "User Error"
                        && classificationInputs[inputIndex].checked
                    ) {
                        hasUserError = true;
                        break;
                    }
                }

                if (userNameInput) {
                    userNameInput.setCustomValidity(
                        hasUserError && !userNameInput.value.trim() ? userErrorMessage : ""
                    );
                }

                if (incidentReportInput) {
                    var hasExistingAttachment = incidentReportInput.dataset.hasExistingAttachment === "true";
                    var normalizedUserName = userNameInput ? userNameInput.value.trim().toUpperCase() : "";
                    var previousUserErrorCount = Number(userErrorCounts[normalizedUserName] || 0);
                    if (
                        normalizedUserName !== ""
                        && normalizedUserName === recordForm.dataset.currentUserErrorName
                    ) {
                        previousUserErrorCount = Math.max(0, previousUserErrorCount - 1);
                    }
                    var isFirstUserError = hasUserError && previousUserErrorCount === 0;
                    var attachmentIsRequired = isFirstUserError && !hasExistingAttachment;
                    incidentReportInput.required = attachmentIsRequired;
                    incidentReportInput.setAttribute("aria-required", attachmentIsRequired ? "true" : "false");
                    incidentReportInput.setCustomValidity(
                        attachmentIsRequired && incidentReportInput.files.length === 0
                            ? incidentReportMessage
                            : ""
                    );
                }
            };

            for (var classificationIndex = 0; classificationIndex < classificationInputs.length; classificationIndex += 1) {
                classificationInputs[classificationIndex].addEventListener("change", updateUserErrorValidation);
            }

            if (userNameInput) {
                userNameInput.addEventListener("input", updateUserErrorValidation);
            }
            if (incidentReportInput) {
                incidentReportInput.addEventListener("change", updateUserErrorValidation);
            }
            recordForm.addEventListener("submit", updateUserErrorValidation);
            updateUserErrorValidation();
        }

        if (ticketInput && ticketMonitoringLink && ticketMonitoringLink.dataset.baseHref) {
            ticketMonitoringLink.addEventListener("click", function () {
                var targetUrl = new URL(ticketMonitoringLink.dataset.baseHref, window.location.href);
                var ticketValue = ticketInput.value.trim();

                if (ticketValue !== "") {
                    targetUrl.searchParams.set("ticket_number", ticketValue);
                    targetUrl.searchParams.set("q", ticketValue);
                } else {
                    targetUrl.searchParams.delete("ticket_number");
                    targetUrl.searchParams.delete("q");
                }

                ticketMonitoringLink.href = targetUrl.toString();
            });
        }
    }

    if (ticketRecordForm) {
        var ticketUppercaseFields = ticketRecordForm.querySelectorAll('#ticket-number, #ticket-created-by');
        var ticketDateCreated = document.getElementById("ticket-date-created");
        var ticketAgePreview = document.getElementById("ticket-age-preview");

        applyUppercaseBehavior(ticketUppercaseFields);

        if (ticketDateCreated && ticketAgePreview) {
            var updateTicketAgePreview = function () {
                if (!ticketDateCreated.value) {
                    ticketAgePreview.value = "";
                    return;
                }

                var createdDate = new Date(ticketDateCreated.value + "T00:00:00");
                var today = new Date();
                today.setHours(0, 0, 0, 0);

                if (isNaN(createdDate.getTime()) || createdDate > today) {
                    ticketAgePreview.value = "0 day(s)";
                    return;
                }

                var diffMs = today.getTime() - createdDate.getTime();
                var days = Math.floor(diffMs / 86400000);
                ticketAgePreview.value = days + " day(s)";
            };

            ticketDateCreated.addEventListener("input", updateTicketAgePreview);
            ticketDateCreated.addEventListener("change", updateTicketAgePreview);
            ticketRecordForm.addEventListener("reset", function () {
                window.setTimeout(updateTicketAgePreview, 0);
            });
            updateTicketAgePreview();
        }
    }

    if (themeToggle) {
        var updateThemeToggle = function () {
            var isDark = root.classList.contains("dark-theme");
            themeToggle.setAttribute("aria-pressed", isDark ? "true" : "false");
            themeToggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
            themeToggle.setAttribute("title", isDark ? "Switch to light mode" : "Switch to dark mode");
        };

        themeToggle.addEventListener("click", function () {
            root.classList.toggle("dark-theme");

            try {
                window.localStorage.setItem("systemMonitoringTheme", root.classList.contains("dark-theme") ? "dark" : "light");
            } catch (error) {
            }

            updateThemeToggle();
        });

        updateThemeToggle();
    }

    if (!modal || !okButton) {
        return;
    }

    document.body.classList.add("modal-open");

    var closeModal = function () {
        modal.style.display = "none";
        document.body.classList.remove("modal-open");

        if (window.history && typeof window.history.replaceState === "function" && typeof URL === "function") {
            var url = new URL(window.location.href);
            var successParams = ["saved", "updated", "deleted", "identification_number", "ticket_number"];
            for (var index = 0; index < successParams.length; index += 1) {
                url.searchParams.delete(successParams[index]);
            }
            var query = url.searchParams.toString();
            var nextUrl = url.pathname + (query ? "?" + query : "") + url.hash;
            window.history.replaceState({}, document.title, nextUrl);
        }
    };

    okButton.addEventListener("click", function (event) {
        event.preventDefault();
        closeModal();
    });

    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal.style.display !== "none") {
            closeModal();
        }
    });

    okButton.focus();
}());
