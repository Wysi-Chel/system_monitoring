<?php
require __DIR__ . "/includes/auth.php";
require "config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";
require __DIR__ . "/includes/access_request_repository.php";

startMonitoringSession();

$portalBase = "/micei_mis";
$publicPortalUrl = $portalBase . "/public_requests.php";
$accessRequestDealerOptions = getAccessRequestDealerOptions($companyConfigs);
$formValues = [
    "requester_name" => "",
    "dealer" => "",
    "department" => "",
    "dmis_username" => "",
    "modules" => [],
    "description" => "",
    "requested_by" => "",
    "privacy_consent" => false,
];
$formErrors = [];

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (trim((string) ($_POST["website"] ?? "")) !== "") {
        http_response_code(422);
        echo "Submission rejected.";
        exit;
    }

    $formValues = [
        "requester_name" => uppercaseText(normalizeSearchFilter($_POST["requester_name"] ?? "")),
        "dealer" => normalizeAllowedFilter($_POST["dealer"] ?? "", $accessRequestDealerOptions),
        "department" => normalizeAllowedFilter($_POST["department"] ?? "", $departmentOptions),
        "dmis_username" => normalizeSearchFilter($_POST["dmis_username"] ?? ""),
        "modules" => normalizeAccessRequestModules($_POST["modules"] ?? [], $moduleOptions),
        "description" => trim((string) ($_POST["description"] ?? "")),
        "requested_by" => uppercaseText(normalizeSearchFilter($_POST["requested_by"] ?? "")),
        "privacy_consent" => !empty($_POST["privacy_consent"]),
    ];

    if (!isValidAccessRequestCsrfToken($_POST["csrf_token"] ?? null)) {
        $formErrors[] = "Your form session expired. Please review the details and submit again.";
    }

    if (
        $formValues["requester_name"] === ""
        || $formValues["dealer"] === ""
        || $formValues["department"] === ""
        || $formValues["dmis_username"] === ""
        || $formValues["description"] === ""
        || $formValues["requested_by"] === ""
    ) {
        $formErrors[] = "Complete all required fields.";
    }

    if ($formValues["modules"] === []) {
        $formErrors[] = "Select at least one module.";
    }

    if (
        getAccessRequestTextLength($formValues["requester_name"]) > ACCESS_REQUEST_NAME_MAX_LENGTH
        || getAccessRequestTextLength($formValues["requested_by"]) > ACCESS_REQUEST_NAME_MAX_LENGTH
        || getAccessRequestTextLength($formValues["dmis_username"]) > ACCESS_REQUEST_USERNAME_MAX_LENGTH
    ) {
        $formErrors[] = "A name or the DMIS username is too long.";
    }

    if (getAccessRequestTextLength($formValues["description"]) > ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH) {
        $formErrors[] = "Keep the description under " . ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH . " characters.";
    }

    if (!$formValues["privacy_consent"]) {
        $formErrors[] = "Confirm the accuracy declaration before submitting.";
    }

    if ($formErrors === [] && !isAccessRequestSubmissionAllowed()) {
        $formErrors[] = "Please wait a moment before submitting another access request.";
    }

    $company = $formValues["dealer"] !== ""
        ? resolveAccessRequestCompanyForDealer($formValues["dealer"], $companyConfigs)
        : null;

    if ($formErrors === [] && $company !== null) {
        try {
            ensureAccessRequestTable($pdo, $company);
            $referenceNo = insertAccessRequest($pdo, $company, [
                "requester_name" => $formValues["requester_name"],
                "dealer" => $formValues["dealer"],
                "department" => $formValues["department"],
                "dmis_username" => $formValues["dmis_username"],
                "module" => implode(", ", $formValues["modules"]),
                "description" => $formValues["description"],
                "requested_by" => $formValues["requested_by"],
                "status" => $accessRequestStatusOptions[0],
                "submitted_ip" => getAccessRequestClientIp(),
            ]);
            markAccessRequestSubmission();

            header("Location: public_access_request.php?" . http_build_query(["submitted" => $referenceNo]));
            exit;
        } catch (Throwable $exception) {
            $formErrors[] = "The access request could not be submitted. Please try again.";
        }
    }
}

$submittedReference = trim((string) ($_GET["submitted"] ?? ""));
if (!preg_match('/^DAR-[A-Z]+-\d{4}-\d{4}$/', $submittedReference)) {
    $submittedReference = "";
}
$csrfToken = getAccessRequestCsrfToken();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DMIS Access Request · MICEI</title>
    <link rel="icon" type="image/png" href="<?= e($portalBase) ?>/assets/img/favicon.png">
    <meta name="theme-color" content="#bf1f2f">
    <script src="<?= e($portalBase) ?>/assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= e($portalBase) ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= e($portalBase) ?>/assets/css/public-requests.css">
    <style>
        .access-module-hint { margin-left: 4px; color: var(--muted); font-size: .7rem; font-weight: 500; }
        .access-module-options { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .access-module-option { align-items: center; padding: 11px 13px; color: inherit; border: 1px solid #d7dce2; border-radius: 12px; background: #fff; font-size: .8rem; font-weight: 650; }
        .access-module-option input { margin: 0; }
        .access-module-option:has(input:checked) { border-color: var(--red); background: #fff6f7; }
        html[data-theme="dark"] .access-module-option { border-color: #39414b; background: rgba(34,40,47,.94); }
        html[data-theme="dark"] .access-module-option:has(input:checked) { border-color: #75404a; background: #352329; }
    </style>
    <script src="<?= e($portalBase) ?>/assets/js/theme.js" defer></script>
</head>
<body class="public-request-body">
<main class="public-request-shell">
    <nav class="public-nav">
        <a class="public-brand" href="<?= e($publicPortalUrl) ?>"><span><img src="<?= e($portalBase) ?>/assets/img/favicon.png" alt=""></span><span><small>MICEI</small><strong>Public Request Portal</strong></span></a>
        <div>
            <button class="micei-theme-toggle compact" type="button" data-theme-toggle aria-pressed="false">
                <svg class="theme-icon theme-icon-sun" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="theme-icon theme-icon-moon" aria-hidden="true" viewBox="0 0 24 24"><path d="M20.5 15.4A9 9 0 0 1 8.6 3.5 9 9 0 1 0 20.5 15.4Z"/></svg>
                <span data-theme-label>Dark mode</span>
            </button>
            <a class="btn btn-secondary btn-sm" href="<?= e($publicPortalUrl) ?>">All forms</a>
        </div>
    </nav>

    <?php if ($submittedReference !== ""): ?>
        <section class="public-success">
            <span class="success-check">✓</span>
            <span class="public-kicker">Request received</span>
            <h1>Your DMIS access request is now pending review.</h1>
            <strong class="tracking-reference"><?= e($submittedReference) ?></strong>
            <div class="public-success-actions"><a class="btn btn-primary" href="<?= e($publicPortalUrl) ?>">Return to request portal</a><a class="btn btn-secondary" href="public_access_request.php">Submit another request</a></div>
        </section>
    <?php else: ?>
        <header class="public-form-header">
            <span class="public-kicker">DMIS Access Request</span>
            <h1>Request DMIS system access</h1>
        </header>
        <?php if ($formErrors !== []): ?><div class="public-errors"><strong>Please review the following:</strong><ul><?php foreach ($formErrors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <form class="public-request-form" method="post" action="public_access_request.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="public-honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 1</span><h2>User details</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group">
                        <label for="access-requester-name">Name <span class="required">*</span></label>
                        <input type="text" id="access-requester-name" name="requester_name" maxlength="<?= e(ACCESS_REQUEST_NAME_MAX_LENGTH) ?>" value="<?= e($formValues["requester_name"]) ?>" autocomplete="name" required>
                    </div>
                    <div class="form-group">
                        <label for="access-dmis-username">DMIS username <span class="required">*</span></label>
                        <input type="text" id="access-dmis-username" name="dmis_username" maxlength="<?= e(ACCESS_REQUEST_USERNAME_MAX_LENGTH) ?>" value="<?= e($formValues["dmis_username"]) ?>" autocomplete="off" required>
                    </div>
                    <div class="form-group">
                        <label for="access-dealer">Dealer <span class="required">*</span></label>
                        <select id="access-dealer" name="dealer" required>
                            <option value="">Select dealer</option>
                            <?php foreach ($accessRequestDealerOptions as $option): ?>
                            <option value="<?= e($option) ?>"<?= $formValues["dealer"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="access-department">Department <span class="required">*</span></label>
                        <select id="access-department" name="department" required>
                            <option value="">Select department</option>
                            <?php foreach ($departmentOptions as $option): ?>
                            <option value="<?= e($option) ?>"<?= $formValues["department"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 2</span><h2>Access being requested</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group full" role="group" aria-labelledby="access-module-label">
                        <label id="access-module-label">Modules <span class="required">*</span><small class="access-module-hint">Select one or more</small></label>
                        <div class="access-module-options">
                            <?php foreach ($moduleOptions as $optionIndex => $option): ?>
                            <label class="public-consent access-module-option" for="access-module-<?= e($optionIndex) ?>"><input type="checkbox" id="access-module-<?= e($optionIndex) ?>" name="modules[]" value="<?= e($option) ?>"<?= in_array($option, $formValues["modules"], true) ? " checked" : "" ?>><span><?= e($option) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label for="access-description">Description <span class="required">*</span></label>
                        <textarea id="access-description" name="description" rows="5" maxlength="<?= e(ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH) ?>" placeholder="Describe the specific access needed and the reason for the request" required><?= e($formValues["description"]) ?></textarea>
                    </div>
                </div>
            </section>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 3</span><h2>Requested By</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group full">
                        <label for="access-requested-by">Name <span class="required">*</span></label>
                        <input type="text" id="access-requested-by" name="requested_by" maxlength="<?= e(ACCESS_REQUEST_NAME_MAX_LENGTH) ?>" value="<?= e($formValues["requested_by"]) ?>" placeholder="Manager name" autocomplete="off" required>
                    </div>
                    <label class="public-consent full"><input type="checkbox" name="privacy_consent" value="1" required<?= $formValues["privacy_consent"] ? " checked" : "" ?>><span>I confirm that the information above is accurate and that I am authorized to request this system access.</span></label>
                </div>
            </section>

            <div class="public-submit-bar"><span>Submission status will start as <strong>Pending</strong>.</span><button class="btn btn-primary" type="submit">Submit access request</button></div>
        </form>
    <?php endif; ?>
    <footer class="public-footer"><span>MICEI Information Technology Department</span><span>Protected request intake</span></footer>
</main>
</body>
</html>
