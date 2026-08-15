<?php /** @var string $base @var string $appName */ $appName = $appName ?? 'StockWise'; ?>
  <footer class="text-center text-muted py-4 small">
        &copy; <?= date('Y') ?> <?= Security::e($appName) ?> - Inventory &amp; Procurement Management System
  </footer>
</div><!-- /content-col -->
  </div><!-- /row -->
</div><!-- /container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= $base ?>assets/js/app.js?v=<?= filemtime(BASE_PATH . 'public/assets/js/app.js') ?>"></script>
</body>
</html>
