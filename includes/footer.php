</div><!-- /.content -->
    </main>
</div><!-- /.app -->

<?php // Shared JS: loader, phone inputs, filter drawer, search suggestions ?>
<script src="<?= e(base_url('assets/js/app.js')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
<script>
(function () {
  if (!('serviceWorker' in navigator)) return;
  var swUrl = <?= json_encode(base_url('sw.js')) ?>;
  window.addEventListener('load', function () {
    navigator.serviceWorker.register(swUrl).catch(function () {});
  });
})();
</script>
</body>
</html>
