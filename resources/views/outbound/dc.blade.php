@extends('layouts.app')

@section('content')
<div class="card shadow-sm print-container">
    <div class="card-header d-flex justify-content-between">
        <strong>Gate Pass</strong>
        <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>
    </div>

    <div class="card-body">

        <!-- Header: Logo and Company Info -->
        <div class="row mb-4 align-items-center">
            <div class="col-sm-6">
                <img src="{{ asset('logo.png') }}" alt="Company Logo" style="max-height: 80px;">
            </div>
            <div class="col-sm-6 text-end">
                <h5 class="mb-1 fw-bold" style="color: #333;">SPC WARE HOUSE</h5>
                <p class="mb-0" style="font-size: 14px; color: #555;">
                    KACHA SADIQ ABAD ROAD NEAR ZOO RYK<br>
                    <strong>Cell #:</strong> 03008636277
                </p>
            </div>
        </div>

        <hr class="mb-4" style="border-top: 2px solid #eee;">

        <!-- DC Details -->
        <div class="row mb-4">
            <div class="col-sm-6">
                <table class="table table-sm table-borderless mb-0" style="font-size: 14px;">
                    <tr>
                        <td style="width: 140px; font-weight: 600; color: #555;">Date & Time:</td>
                        <td>{{ $stockOut->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Outbound Invoice #:</td>
                        <td><span class="badge bg-light text-dark border">{{ $stockOut->dispatched_invoice_no ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Source Type:</td>
                        <td>{{ strtoupper($stockOut->source_type ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Dispatch To #:</td>
                        <td>{{ $stockOut->da_no ?? '-' }}</td>
                    </tr>

                    <tr>
                        <td style="font-weight: 600; color: #555;">Vendor:</td>
                        <td>{{ optional($stockOut->vendor)->name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-sm-6">
                <table class="table table-sm table-borderless mb-0" style="font-size: 14px;">
                    <tr>
                        <td style="width: 140px; font-weight: 600; color: #555;">Transporter:</td>
                        <td>{{ optional($stockOut->transporter)->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Vehicle #:</td>
                        <td>{{ $stockOut->vehicle_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Vehicle Size:</td>
                        <td>{{ $stockOut->vehicle_size ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Driver Name:</td>
                        <td>{{ $stockOut->driver_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Driver Mobile:</td>
                        <td>{{ $stockOut->driver_mobile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Seal #:</td>
                        <td>{{ $stockOut->seal_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #555;">Gate Pass No.:</td>
                        <td>{{ $stockOut->gatepass_no ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%; text-align: center;">#</th>
                    <th style="width: 20%; text-align: left;">Item Code</th>
                    <th style="width: 40%; text-align: left;">Description</th>
                    <th style="width: 15%; text-align: left;">Batch</th>
                    <th style="width: 20%; text-align: right;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockOut->items as $i => $item)
                <tr>
                    <td style="text-align: center;">{{ $i+1 }}</td>
                    <td style="text-align: left;">{{ optional($item->product)->item_code ?? '-' }}</td>
                    <td style="text-align: left;">{{ optional($item->product)->name ?? '-' }}</td>
                    <td style="text-align: left;">{{ $item->sap_batch ?? '-' }}</td>
                    <td style="text-align: right;">{{ $item->dispatch_quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-4">
                <b>DRIVER NAME, CNIC # & SIG:</b><br><br>
                ______________________________
            </div>
            <div class="col-4 text-center">
                <b>SECURITY GUARD SIG:</b><br><br>
                ______________________________
            </div>
            <div class="col-4 text-end">
                <b>DISPATCHER NAME & SIG:</b><br><br>
                ______________________________
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    .btn, .card-header { display: none !important; }
    @page { margin: 1cm; } /* Equal margins around the page */
    body { background: #fff; }
    .card { border: none !important; box-shadow: none !important; }
}
.print-container {
    width: 100%;
    margin: 0 auto;
}
.table td, .table th {
    vertical-align: middle;
}
</style>
@endsection
