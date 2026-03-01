<?php
$repositories = $sf_data->getRaw('repositories') ?? [];
$accessionCount = $sf_data->getRaw('accessionCount') ?? 0;
?>

<h1><?php echo __('Accession CSV Export') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Export'), 'url' => url_for(['module' => 'export', 'action' => 'index'])],
        ['title' => __('Accession CSV')]
    ]
]) ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-csv me-2"></i><?php echo __('Export Accession Records') ?></h5>
            </div>
            <div class="card-body">
                <p class="text-muted"><?php echo __('Export accession records to CSV format. The output matches the ahgIngestPlugin accession import format for round-trip compatibility.') ?></p>

                <form method="post" action="<?php echo url_for('@export_accession_csv') ?>">
                    <div class="mb-3">
                        <label for="repository_id" class="form-label"><?php echo __('Repository (optional)') ?></label>
                        <select class="form-select" id="repository_id" name="repository_id">
                            <option value=""><?php echo __('— All repositories —') ?></option>
                            <?php foreach ($repositories as $repo): ?>
                                <option value="<?php echo $repo->id ?>"><?php echo esc_entities($repo->name) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_from" class="form-label"><?php echo __('Acquisition Date From') ?></label>
                            <input type="date" class="form-control" id="date_from" name="date_from">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_to" class="form-label"><?php echo __('Acquisition Date To') ?></label>
                            <input type="date" class="form-control" id="date_to" name="date_to">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i><?php echo __('Download CSV') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Summary') ?></h5>
            </div>
            <div class="card-body">
                <p><strong><?php echo __('Total accessions:') ?></strong> <?php echo number_format($accessionCount) ?></p>
                <hr>
                <h6><?php echo __('Exported Columns') ?></h6>
                <small class="text-muted">
                    accessionNumber, title, acquisitionDate, sourceOfAcquisition,
                    locationInformation, receivedExtentUnits, scopeAndContent,
                    appraisal, archivalHistory, processingNotes,
                    acquisitionType, resourceType, processingStatus, processingPriority,
                    donorName, donorStreetAddress, donorCity, donorRegion,
                    donorCountry, donorPostalCode, donorTelephone, donorFax,
                    donorEmail, donorContactPerson, donorNote,
                    accessionEventTypes, accessionEventDates, accessionEventAgents,
                    alternativeIdentifiers, alternativeIdentifierNotes,
                    intakeNotes, intakePriority, culture
                </small>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6><?php echo __('Re-import') ?></h6>
                <p class="text-muted mb-0">
                    <?php echo __('The exported CSV can be re-imported using the Ingest wizard (Admin > Ingest > New > Accessions) or the base AtoM command:') ?>
                    <br><code>php symfony csv:accession-import filename.csv</code>
                </p>
            </div>
        </div>
    </div>
</div>
