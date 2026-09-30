<div class="d-flex flex-wrap gap-2 mb-3 no-print">
    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('reports/appointments.php')) ?>">Appointments</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('reports/revenue.php')) ?>">Revenue</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('reports/staff_performance.php')) ?>">Staff performance</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('reports/popular_services.php')) ?>">Popular services</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('reports/outstanding_payments.php')) ?>">Outstanding payments</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('reports/low_stock.php')) ?>">Low stock</a>
    <button class="btn btn-sm btn-dark ms-auto" onclick="window.print()">Print report</button>
</div>
