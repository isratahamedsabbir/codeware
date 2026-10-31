<?php

namespace App\Concerns;

use App\Services\Import\CsvImporter;
use App\Support\AdminActivity;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Adds the "Import CSV" modal plumbing to an admin list component.
 *
 * The screen supplies three tiny pieces: the importer that knows the model
 * (PostCsvImporter / ProductCsvImporter), the modal's name and the noun used in
 * the result toast. Everything else — upload validation, the tally, closing on
 * success and the activity-log entry — lives here so Posts and Products behave
 * identically.
 */
trait ImportsCsv
{
    use WithFileUploads;

    /** The uploaded CSV, held in Livewire's temporary upload directory until imported. */
    public $importFile = null;

    /** Whether rows matching an existing ID/slug/SKU should overwrite it. */
    public bool $importUpdateExisting = false;

    /**
     * Whether the modal's body should be rendered. The body is left out while
     * the modal is closed so its form controls do not appear in the page (and
     * confuse things like the bulk-selection "no checkboxes yet" state); the
     * modal shell itself is always mounted so Flux can open it on demand.
     */
    public bool $importModalOpen = false;

    /**
     * The last import's tally, shown in the modal. Null until one has run.
     *
     * @var array{created: int, updated: int, skipped: int, errors: list<string>, warnings: list<string>}|null
     */
    public ?array $importResult = null;

    abstract protected function csvImporter(): CsvImporter;

    abstract protected function importModalName(): string;

    abstract protected function importEntityLabel(): string;

    public function openImport(): void
    {
        $this->importResult = null;
        $this->importFile = null;
        $this->importUpdateExisting = false;
        $this->importModalOpen = true;
        $this->resetValidation();

        $this->dispatch('open-modal', name: $this->importModalName());
    }

    public function closeImport(): void
    {
        $this->importFile = null;
        $this->importUpdateExisting = false;
        $this->importResult = null;
        $this->importModalOpen = false;
        $this->resetValidation();

        $this->dispatch('close-modal', name: $this->importModalName());
    }

    public function importCsv(): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'extensions:csv,txt', 'max:10240'],
        ], [], ['importFile' => 'CSV file']);

        try {
            $result = $this->csvImporter()->import(
                $this->importFile->getRealPath(),
                $this->importUpdateExisting,
                (int) auth()->id(),
            );
        } catch (Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Import failed: '.$e->getMessage());

            return;
        }

        $this->importResult = $result;
        $this->importFile = null;
        $this->importUpdateExisting = false;
        $this->resetValidation();

        $summary = "{$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped";

        AdminActivity::log('imported', ucfirst($this->importEntityLabel()).'s imported: '.$summary);

        $this->dispatch('notify', message: ucfirst($this->importEntityLabel()).' import finished: '.$summary.'.');
    }
}
