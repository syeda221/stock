@extends('layouts.app')

<style>
    .signature-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 40px;
    }

    .signature-table td {
        border: 1px solid #000;
        text-align: center;
        padding: 20px 10px;
        height: 110px;
        vertical-align: bottom;
        font-size: 13px;
    }

    .signature-title {
        font-weight: bold;
        margin-bottom: 30px;
        display: block;
    }

    .dispatch-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        border-bottom: 2px solid #333;
        padding-bottom: 15px;
    }

    .company-info {
        font-size: 14px;
    }

    .document-title {
        text-align: right;
    }
</style>

@section('content')
<div class="card shadow-sm print-area">
    <div class="card-header d-flex justify-content-between align-items-center no-print">
        <strong>Dispatch Details</strong>
        <button onclick="window.print()" class="btn btn-sm btn-secondary"><i class="bi bi-printer me-1"></i>Print</button>
    </div>

    <div class="card-body p-4">
        {{-- HEADER --}}
        <table class="table table-borderless mb-4">
            <tr>
                <td width="60%">
                    <img src="{{ asset('logo.png') }}" alt="Company Logo" style="max-height: 150px;" class="mb-2"><br>
                    <h5 class="mb-1 fw-bold" style="color: #333;">SPC WARE HOUSE</h5>
                    <p class="mb-0" style="font-size: 14px; color: #555;">
                        KACHA SADIQ ABAD ROAD NEAR ZOO RYK<br>
                        CELL # 03008636277
                    </p>
                </td>
                <td width="40%" class="text-end align-bottom">
                    <h2 class="mb-1 fw-bold text-uppercase" style="color: #444;">Dispatch Details</h2>
                    <div>Date: {{ $stockOut->created_at->format('d.m.Y H:i') }}</div>
                </td>
            </tr>
        </table>

        {{-- SUMMARY DETAILS --}}
        <div class="row mb-4">
            <div class="col-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th style="width: 150px;">Dispatch To:</th>
                        <td>{{ optional($stockOut->customer)->name ?? optional($stockOut->toWarehouse)->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Vehicle #:</th>
                        <td>{{ $stockOut->vehicle_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Vehicle In Time:</th>
                        <td>{{ optional($stockOut->vehicle_in_time)->format('d.m.Y H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Vehicle Out Time:</th>
                        <td>{{ optional($stockOut->vehicle_out_time)->format('d.m.Y H:i') ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table class="table table-sm table-borderless text-end">
                    <tr>
                        <th>Date:</th>
                        <td>{{ $stockOut->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Dispatched Invoice #:</th>
                        <td>{{ $stockOut->dispatched_invoice_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Remarks:</th>
                        <td>{{ $stockOut->remarks ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ITEMS --}}
        <table class="table table-bordered table-sm" style="font-size: 11px;">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Vendor Batch</th>
                    <th>SAP Batch</th>
                    <th>PO #</th>
                    <th>IBD #</th>
                    <th>STO #</th>
                    <th>Packing</th>
                    <th>Pack Size</th>
                    <th>UOM</th>
                    <th>Units Dispatched</th>
                    <th>Dispatch Qty</th>
                    <th>MFG Date</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $totalUnits = 0;
                    $totalQty = 0; 
                @endphp
                @foreach($stockOut->items as $item)
                    @php 
                        $totalUnits += $item->units_dispatch;
                        $totalQty += $item->dispatch_quantity; 
                    @endphp
                    <tr>
                        <td>{{ optional($item->product)->item_code ?? '-' }}</td>
                        <td>{{ optional($item->product)->name ?? '-' }}</td>
                        <td>{{ $item->vendor_batch ?? '-' }}</td>
                        <td>{{ $item->sap_batch ?? '-' }}</td>
                        <td>{{ $item->po_resolved ?? $item->po_no ?? optional($item->sourceStockInItem)->po_no ?? '-' }}</td>
                        <td>{{ $item->ibd_no ?? optional($item->sourceStockInItem)->ibd_no ?? '-' }}</td>
                        <td>{{ $item->sto_no ?? '-' }}</td>
                        <td class="text-center">{{ optional(optional($item->product)->packingType)->name ?? '-' }}</td>
                        <td class="text-end">{{ $item->pack_size_snapshot }}</td>
                        <td class="text-center">{{ $item->uom_resolved ?? optional(optional($item->product)->uom)->name ?? '-' }}</td>
                        <td class="text-end">{{ $item->units_dispatch }}</td>
                        <td class="text-end fw-bold">{{ $item->dispatch_quantity }}</td>
                        <td>{{ optional($item->mfg_date)->format('d.m.Y') ?? '-' }}</td>
                        <td>{{ optional($item->expiry_date)->format('d.m.Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold table-light">
                    <td colspan="10" class="text-end">TOTAL:</td>
                    <td class="text-end">{{ $totalUnits }}</td>
                    <td class="text-end">{{ number_format($totalQty, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        {{-- SIGNATURES --}}
        <div class="row mt-5 pt-4">
            <div class="col-4 text-center">
                ______________________________<br>
                <b>DISPATCHER NAME & SIG:</b>
            </div>
            <div class="col-4 text-center">
                ______________________________<br>
                <b>Driver Signature</b>
            </div>
            <div class="col-4 text-center">
                ______________________________<br>
                <b>Receiver Signature</b>
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    .no-print { display:none !important; }
    body { background:#fff; margin: 0; padding: 0; }
    .card { border:none; box-shadow: none !important; }
    .card-body { padding: 0 !important; }
    table { font-size:10px !important; }
    @page { margin: 1cm; size: landscape; }
}
</style>
@endsection
