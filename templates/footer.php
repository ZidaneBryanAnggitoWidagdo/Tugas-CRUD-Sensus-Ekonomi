<?php if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); } ?>
  </main>
</div>

<footer class="app-footer">
  <p><strong><?= e(APP_NAME) ?> <?= e(APP_YEAR) ?></strong> — <?= e(APP_NAME_LONG) ?></p>
  <p>Data dikelola oleh <?= e(APP_ORG) ?>. Seluruh aktivitas penggunaan tercatat dalam jalur audit.</p>
</footer>

<script src="<?= e(u('assets/js/app.js')) ?>"></script>
</body>
</html>
