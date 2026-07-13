<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= esc($invoice['invoice_number']) ?></title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #333; margin: 0; padding: 30px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #1e2a38; padding-bottom: 15px; margin-bottom: 20px; }
        .hotel-name { font-size: 22px; font-weight: bold; color: #1e2a38; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; color: #1e2a38; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .info-box { width: 48%; }
        .info-box h4 { margin-bottom: 5px; font-size: 12px; text-transform: uppercase; color: #888; }
        table.detail-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.detail-table th, table.detail-table td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
        table.detail-table th { background: #f4f6f9; }
        .text-right { text-align: right; }
        .totals { width: 300px; margin-left: auto; }
        .totals table { width: 100%; }
        .totals td { padding: 5px 0; }
        .totals .grand-total { font-size: 16px; font-weight: bold; border-top: 2px solid #1e2a38; padding-top: 8px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; color: #fff; }
        .status-paid { background: #198754; }
        .status-partial { background: #ffc107; color: #333; }
        .status-unpaid { background: #dc3545; }
        .status-refunded { background: #6c757d; }
        .payment-history { margin-top: 25px; }
        .payment-history h4 { font-size: 13px; text-transform: uppercase; color: #888; margin-bottom: 8px; }
        .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()">🖨️ Print</button>
    </div>

    <div class="header">
        <div>
            <div class="hotel-name"><?= esc($settings['hotel_name'] ?? 'Hotel Management System') ?></div>
            <div><?= esc($settings['address'] ?? '') ?></div>
            <div><?= esc($settings['phone'] ?? '') ?> <?= !empty($settings['email']) ? '| ' . esc($settings['email']) : '' ?></div>
        </div>
        <div class="invoice-title">
            <h2>INVOICE</h2>
            <div><?= esc($invoice['invoice_number']) ?></div>
            <div><?= esc(date('d M Y', strtotime($invoice['created_at']))) ?></div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h4>Ditagihkan Kepada</h4>
            <div><strong><?= esc($invoice['customer_name']) ?></strong></div>
            <div><?= esc($invoice['customer_phone']) ?></div>
            <div><?= esc($invoice['customer_address'] ?? '-') ?></div>
        </div>
        <div class="info-box" style="text-align: right;">
            <h4>Detail Booking</h4>
            <div>No. Booking: <strong><?= esc($invoice['booking_number']) ?></strong></div>
            <div>Kamar: <?= esc($invoice['room_number']) ?> (<?= esc($invoice['room_type_name']) ?>)</div>
            <div>Check In: <?= esc(date('d M Y', strtotime($invoice['check_in_date']))) ?></div>
            <div>Check Out: <?= esc(date('d M Y', strtotime($invoice['check_out_date']))) ?></div>
        </div>
    </div>

    <table class="detail-table">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= esc($item['description']) ?></td>
                    <td class="text-right"><?= esc($item['quantity']) ?></td>
                    <td class="text-right">Rp <?= number_format($item['unit_price'], 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($item['amount'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="text-right">Rp <?= number_format($invoice['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Pajak</td>
                <td class="text-right">Rp <?= number_format($invoice['tax_amount'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Service Charge</td>
                <td class="text-right">Rp <?= number_format($invoice['service_charge_amount'], 0, ',', '.') ?></td>
            </tr>
            <tr class="grand-total">
                <td>Total</td>
                <td class="text-right">Rp <?= number_format($invoice['total_amount'], 0, ',', '.') ?></td>
            </tr>
        </table>
    </div>

    <div>
        <span class="status-badge status-<?= esc($invoice['status']) ?>">
            <?= strtoupper(esc($invoice['status'])) ?>
        </span>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="payment-history">
            <h4>Riwayat Pembayaran</h4>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>No. Pembayaran</th>
                        <th>Metode</th>
                        <th>Tanggal</th>
                        <th class="text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= esc($p['payment_number']) ?></td>
                            <td><?= strtoupper(esc($p['method'])) ?></td>
                            <td><?= esc(date('d M Y H:i', strtotime($p['paid_at']))) ?></td>
                            <td class="text-right">Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="footer">
        Terima kasih telah menginap di <?= esc($settings['hotel_name'] ?? 'hotel kami') ?>.<br>
        Invoice ini dibuat otomatis oleh sistem.
    </div>

</body>
</html>