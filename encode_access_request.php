<?php
require __DIR__ . "/includes/auth.php";
requireMonitoringAuthentication();
require "config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";
require __DIR__ . "/includes/access_request_repository.php";

$company = resolveCompanyConfig($_GET["company"] ?? $_POST["company"] ?? null, $companyConfigs);
ensureAccessRequestTable($pdo, $company);

$companyDealerOptions = $company["access_request_dealers"] ?? [];
$accessRequestToday = getAccessRequestManilaToday();
// IT and the super admin each encode for their own side, the way the monitoring form locks "Processed by".
$encoderSide = getAccessRequestEncoderSide();
$encoderSideOptions = getAccessRequestEncoderSideOptions();
$formValues = [
    "requester_name" => "",
    "dealer" => count($companyDealerOptions) === 1 ? (string) $companyDealerOptions[0] : "",
    "department" => "",
    "dmis_username" => "",
    "position" => "",
    "date_of_request" => $accessRequestToday,
    "modules" => [],
    "description" => "",
    "requested_by" => $encoderSide,
];
$formErrors = [];

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $formValues = [
        "requester_name" => uppercaseText(normalizeSearchFilter($_POST["requester_name"] ?? "")),
        "dealer" => normalizeAllowedFilter($_POST["dealer"] ?? "", $companyDealerOptions),
        "department" => normalizeAllowedFilter($_POST["department"] ?? "", $departmentOptions),
        "dmis_username" => normalizeSearchFilter($_POST["dmis_username"] ?? ""),
        "position" => normalizeSearchFilter($_POST["position"] ?? ""),
        "date_of_request" => normalizeDateFilter($_POST["date_of_request"] ?? ""),
        "modules" => normalizeAccessRequestModules($_POST["modules"] ?? [], $moduleOptions),
        "description" => trim((string) ($_POST["description"] ?? "")),
        // The side is taken from the signed-in account, not the posted value, so it cannot be misattributed.
        "requested_by" => $encoderSide,
    ];

    if (!isValidAccessRequestCsrfToken($_POST["csrf_token"] ?? null)) {
        $formErrors[] = "Your form session expired. Review the details and save again.";
    }

    $formErrors = [...$formErrors, ...validateAccessRequestValues($formValues)];

    if ($formErrors === []) {
        try {
            $referenceNo = insertAccessRequest($pdo, $company, [
                "requester_name" => $formValues["requester_name"],
                "dealer" => $formValues["dealer"],
                "department" => $formValues["department"],
                "dmis_username" => $formValues["dmis_username"],
                "position" => $formValues["position"],
                "date_of_request" => $formValues["date_of_request"],
                "module" => implode(", ", $formValues["modules"]),
                "description" => $formValues["description"],
                "requested_by" => $formValues["requested_by"],
                "encoded_by" => getAccessRequestPortalUserName(),
                "status" => $accessRequestStatusOptions[0],
                "submitted_ip" => getAccessRequestClientIp(),
            ]);
            $newRequestId = (int) $pdo->lastInsertId();

            $redirectUrl = $newRequestId > 0
                ? buildUrl("access_request_view.php", [
                    "company" => $company["key"],
                    "id" => $newRequestId,
                    "saved" => "encoded",
                ])
                : buildUrl("access_requests.php", ["company" => $company["key"], "q" => $referenceNo]) . "#access-request-summary";

            header("Location: " . $redirectUrl);
            exit;
        } catch (Throwable $exception) {
            $formErrors[] = "The access request could not be encoded. Please try again.";
        }
    }
}

$accessRequestsUrl = buildUrl("access_requests.php", ["company" => $company["key"]]) . "#access-request-summary";
$mitsubishiUrl = buildUrl("encode_access_request.php", ["company" => "mitsubishi"]);
$hyundaiUrl = buildUrl("encode_access_request.php", ["company" => "hyundai"]);
$headerKicker = $company["company_name"];
$headerTitle = "Encode Access Request";
$showCompanySwitch = true;
$csrfToken = getAccessRequestCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Encode Access Request · <?= e($company["company_name"]) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <link rel="shortcut icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= e(buildVersionedAssetPath("assets/css/index.css")) ?>">
</head>
<body class="company-<?= e($company["key"]) ?> page-ticket-monitoring page-access-requests">
<?php require __DIR__ . "/includes/partials/page_header.php"; ?>

<main>
    <section class="card" id="encode-access-request-form">
        <div class="summary-header">
            <div>
                <h2>Encode Access Request</h2>
            </div>
            <a href="<?= e($accessRequestsUrl) ?>" class="button-link secondary icon-button" aria-label="Back to access requests" title="Back to access requests">
                <?= iconSvg("arrow-left") ?>
                <span class="sr-only">Back to access requests</span>
            </a>
        </div>

        <?php if ($formErrors !== []): ?>
        <div class="form-alert form-alert-error" role="alert">
            <?php foreach ($formErrors as $formError): ?>
            <div><?= e($formError) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form action="encode_access_request.php" method="POST" id="access-request-encode-form">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <section class="form-section">
                <div class="form-section-title">
                    <h3>User Details</h3>
                </div>

                <div class="field-grid">
                    <div class="field field-span-2">
                        <label for="encode-requester-name">Name</label>
                        <input type="text" id="encode-requester-name" name="requester_name" maxlength="<?= e(ACCESS_REQUEST_NAME_MAX_LENGTH) ?>" value="<?= e($formValues["requester_name"]) ?>" required>
                    </div>

                    <div class="field">
                        <label for="encode-dmis-username">DMIS username</label>
                        <input type="text" id="encode-dmis-username" name="dmis_username" maxlength="<?= e(ACCESS_REQUEST_USERNAME_MAX_LENGTH) ?>" value="<?= e($formValues["dmis_username"]) ?>" required>
                    </div>

                    <div class="field">
                        <label for="encode-position">Position</label>
                        <input type="text" id="encode-position" name="position" maxlength="<?= e(ACCESS_REQUEST_USERNAME_MAX_LENGTH) ?>" value="<?= e($formValues["position"]) ?>" required>
                    </div>

                    <div class="field">
                        <label for="encode-date-of-request">Date of request</label>
                        <input type="date" id="encode-date-of-request" name="date_of_request" max="<?= e($accessRequestToday) ?>" value="<?= e($formValues["date_of_request"]) ?>" required>
                    </div>

                    <div class="field">
                        <label for="encode-dealer">Dealers</label>
                        <select id="encode-dealer" name="dealer" required>
                            <option value="">Select dealer</option>
                            <?php foreach ($companyDealerOptions as $option): ?>
                            <option value="<?= e($option) ?>"<?= $formValues["dealer"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="encode-department">Department</label>
                        <select id="encode-department" name="department" required>
                            <option value="">Select department</option>
                            <?php foreach ($departmentOptions as $option): ?>
                            <option value="<?= e($option) ?>"<?= $formValues["department"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-title">
                    <h3>Access Being Requested</h3>
                </div>

                <div class="selector-grid">
                    <div class="selector-field selector-field-medium">
                        <label>Modules</label>
                        <?php renderOptionButtons("modules", $moduleOptions, true, $formValues["modules"]); ?>
                    </div>

                    <div class="selector-field selector-field-medium">
                        <label>Requested by</label>
                        <?php // Locked: the request is credited to the side of the signed-in account. ?>
                        <?php renderOptionButtons("requested_by", $encoderSideOptions, false, $formValues["requested_by"], true); ?>
                    </div>
                </div>

                <div class="field-grid">
                    <div class="field field-span-2">
                        <label for="encode-description">Description</label>
                        <textarea id="encode-description" name="description" rows="5" maxlength="<?= e(ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH) ?>" placeholder="Describe the specific access needed and the reason for the request" required><?= e($formValues["description"]) ?></textarea>
                    </div>
                </div>
            </section>

            <div class="buttons">
                <button type="submit" class="primary icon-button" aria-label="Save access request" title="Save access request">
                    <?= iconSvg("save") ?>
                    <span class="sr-only">Save access request</span>
                </button>
                <a href="<?= e($accessRequestsUrl) ?>" class="button-link secondary icon-button" aria-label="Cancel" title="Cancel">
                    <?= iconSvg("x") ?>
                    <span class="sr-only">Cancel</span>
                </a>
            </div>
        </form>
    </section>
</main>

<script src="assets/js/index.js" defer></script>
</body>
</html>
