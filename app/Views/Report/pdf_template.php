<?php
    $headers = isset($headers) ? $headers : [];
    $title = isset($title) && (is_string($title) || $title instanceof \Stringable) ? $title : 'Laporan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        h2 { color: #1e2a38; margin-bottom: 4px; }
        .period { color: #666; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #1e2a38; color: #fff; }
        tr:nth-child(even) { background: #f7f7f7; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <?php
        $headers = isset($headers) ? $headers : [];
        $title = isset($title) && is_string($title) ? $title : 'Laporan';
        $startDate = isset($startDate) ? $startDate : date('Y-m-d');
        $endDate   = isset($endDate) ? $endDate : date('Y-m-d');
    ?>
    <h2><?= esc($title) ?></h2>
    <div class="period">Periode: <?= esc(date('d M Y', strtotime($startDate))) ?> s/d <?= esc(date('d M Y', strtotime($endDate))) ?></div>

    <table>
        <thead>
            <tr>
                <?php foreach ($headers as $h): ?>
                    <th><?= esc($h) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($headers) ?>" style="text-align:center; color:#999;">Tidak ada data pada periode ini.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ((array) $row as $value): ?>
                            <td><?= is_numeric($value) && $value >= 1000 ? esc(number_format($value, 0, ',', '.')) : esc($value) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>