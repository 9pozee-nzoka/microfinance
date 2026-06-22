{{--
    Reusable export button bar.
    Usage: @include('reports._partials.export-buttons')
--}}
<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
    <button type="submit" name="export" value="pdf" class="btn btn-outline" style="color:#DC2626; border-color:#DC2626;" title="Export PDF">
        <i class="fas fa-file-pdf"></i> PDF
    </button>
    <button type="submit" name="export" value="excel" class="btn btn-outline" style="color:#16A34A; border-color:#16A34A;" title="Export Excel">
        <i class="fas fa-file-excel"></i> Excel
    </button>
    <button type="submit" name="export" value="csv" class="btn btn-outline" style="color:#2563EB; border-color:#2563EB;" title="Export CSV">
        <i class="fas fa-file-csv"></i> CSV
    </button>
    <button type="submit" name="export" value="word" class="btn btn-outline" style="color:#2563EB; border-color:#2563EB;" title="Export Word">
        <i class="fas fa-file-word"></i> Word
    </button>
</div>
