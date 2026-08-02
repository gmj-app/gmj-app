<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Enums\ImportBatchSource;
use App\Enums\ImportBatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\StoreImportBatchRequest;
use App\Http\Requests\CreatorIntelligence\UpdateImportMappingRequest;
use App\Jobs\InspectCreatorAnalyticsImport;
use App\Jobs\ProcessCreatorAnalyticsImport;
use App\Models\CreatorChannel;
use App\Models\ImportBatch;
use App\Services\CreatorIntelligence\Import\CsvColumnMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportBatchController extends Controller
{
    public function index(Request $request): View
    {
        $query = ImportBatch::with(['channel', 'uploadedBy']);
        foreach (['creator_channel_id', 'source', 'status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('snapshot_from')) {
            $query->whereDate('snapshot_date', '>=', $request->input('snapshot_from'));
        }
        if ($request->filled('snapshot_to')) {
            $query->whereDate('snapshot_date', '<=', $request->input('snapshot_to'));
        }
        if ($request->filled('uploaded_from')) {
            $query->whereDate('created_at', '>=', $request->input('uploaded_from'));
        }
        if ($request->filled('uploaded_to')) {
            $query->whereDate('created_at', '<=', $request->input('uploaded_to'));
        }
        $sort = in_array($request->input('sort'), ['created_at', 'snapshot_date', 'status', 'total_rows', 'failed_rows'], true) ? $request->input('sort') : 'created_at';

        return view('super-admin.creator-intelligence.imports.index', ['batches' => $query->orderByDesc($sort)->paginate(25)->withQueryString(), 'channels' => CreatorChannel::orderBy('channel_name')->get()]);
    }

    public function create(): View
    {
        return view('super-admin.creator-intelligence.imports.create', ['channels' => CreatorChannel::active()->orderBy('channel_name')->get(), 'sources' => ImportBatchSource::cases()]);
    }

    public function store(StoreImportBatchRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $stored = Str::uuid().'.'.$extension;
        $disk = config('creator_intelligence.import_disk');
        $path = 'creator-intelligence/imports/'.$stored;
        if (! Storage::disk($disk)->putFileAs('creator-intelligence/imports', $file, $stored)) {
            return back()->withErrors(['file' => 'The upload could not be stored.']);
        }
        try {
            $batch = ImportBatch::create(['creator_channel_id' => $request->integer('creator_channel_id'), 'uploaded_by_user_id' => $request->user()->id, 'source' => $request->validated('source'), 'original_filename' => Str::limit(basename($file->getClientOriginalName()), 255, ''), 'stored_filename' => $stored, 'storage_disk' => $disk, 'storage_path' => $path, 'snapshot_date' => $request->validated('snapshot_date'), 'status' => ImportBatchStatus::Uploaded]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
        InspectCreatorAnalyticsImport::dispatch($batch->id);

        return redirect()->route('superadmin.creator-intelligence.imports.show', $batch)->with('success', 'Import uploaded and queued for inspection.');
    }

    public function show(Request $request, ImportBatch $importBatch): View
    {
        $rows = $importBatch->rows()->with(['video', 'snapshot'])->when($request->filled('row_status'), fn ($q) => $q->where('status', $request->input('row_status')))->orderBy('row_number')->paginate(50)->withQueryString();

        return view('super-admin.creator-intelligence.imports.show', ['batch' => $importBatch->load(['channel', 'uploadedBy']), 'rows' => $rows]);
    }

    public function mapping(ImportBatch $importBatch): View
    {
        return view('super-admin.creator-intelligence.imports.mapping', ['batch' => $importBatch, 'fields' => CsvColumnMapper::CANONICAL_FIELDS]);
    }

    public function updateMapping(UpdateImportMappingRequest $request, ImportBatch $importBatch): RedirectResponse
    {
        $importBatch->update(['column_mapping' => $request->validatedMapping(), 'status' => ImportBatchStatus::Ready, 'error_summary' => null]);

        return redirect()->route('superadmin.creator-intelligence.imports.show', $importBatch)->with('success', 'Column mapping saved.');
    }

    public function process(ImportBatch $importBatch): RedirectResponse
    {
        $claimed = ImportBatch::whereKey($importBatch->id)->where('status', ImportBatchStatus::Ready->value)->update(['status' => ImportBatchStatus::Queued->value]);
        if (! $claimed) {
            return back()->withErrors(['process' => 'This import is not ready or is already being processed.']);
        }
        ProcessCreatorAnalyticsImport::dispatch($importBatch->id);

        return back()->with('success', 'Import queued for processing.');
    }

    public function errors(ImportBatch $importBatch): View
    {
        return view('super-admin.creator-intelligence.imports.errors', ['batch' => $importBatch, 'rows' => $importBatch->rows()->where('status', 'failed')->orderBy('row_number')->paginate(100)]);
    }

    public function failedRows(ImportBatch $importBatch): StreamedResponse
    {
        $columns = $importBatch->detected_columns ?? [];
        $normalized = CsvColumnMapper::CANONICAL_FIELDS;

        return response()->streamDownload(function () use ($importBatch, $columns, $normalized): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, array_merge(['row_number', 'status', 'message'], $columns, array_map(fn ($field) => 'normalized_'.$field, $normalized)));
            foreach ($importBatch->rows()->where('status', 'failed')->orderBy('row_number')->cursor() as $row) {
                fputcsv($out, array_merge([$row->row_number, $row->status->value, $row->message], array_map(fn ($column) => $row->raw_data[$column] ?? null, $columns), array_map(fn ($field) => $row->normalized_data[$field] ?? null, $normalized)));
            }
            fclose($out);
        }, 'import-'.$importBatch->id.'-failed-rows.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
