<?php
/**
 * Shared line-item rows for request forms (create + edit).
 * @var array $items item list
 * @var array|null $existing pre-filled request_items rows (edit mode)
 */
$existing = $existing ?? [];
$itemOpts = '';
foreach ($items as $it) {
    $itemOpts .= '<option value="' . (int)$it['id'] . '">' . Security::e($it['item_code']) . ' - ' . Security::e($it['name']) . '</option>';
}

/** Build one line-item <tr>. */
function sw_item_row(string $opts, ?int $selectedId, int $qty): string
{
    if ($selectedId !== null) {
        $opts = str_replace('value="' . $selectedId . '"', 'value="' . $selectedId . '" selected', $opts);
    }
    return '<tr class="item-row">
      <td><select name="item_id[]" class="form-select">' . $opts . '</select></td>
      <td><input type="number" name="qty[]" class="form-control" min="1" value="' . max(1, $qty) . '"></td>
      <td style="width:50px"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()"><i class="bi bi-x"></i></button></td>
    </tr>';
}
?>
<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Line Items</span>
    <div class="d-flex gap-2 align-items-center">
      <input type="search" id="itemSearch" class="form-control form-control-sm" style="max-width:260px;" placeholder="Filter items..." aria-label="Filter items">
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItemRow()"><i class="bi bi-plus-lg"></i> Add Row</button>
    </div>
  </div>
  <div class="card-body p-0">
    <table class="table mb-0" id="itemsTable">
      <thead><tr><th style="width:55%">Item</th><th style="width:20%">Requested Qty</th><th></th></tr></thead>
      <tbody id="itemsBody">
        <?php
        if (empty($existing)): ?>
          <?= sw_item_row($itemOpts, null, 1) ?>
        <?php else: foreach ($existing as $ri): ?>
          <?= sw_item_row($itemOpts, (int)$ri['item_id'], (int)$ri['requested_qty']) ?>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
var ITEM_OPTIONS = `<?= $itemOpts ?>`;
function addItemRow() {
  var tbody = document.getElementById('itemsBody');
  var tr = document.createElement('tr');
  tr.className = 'item-row';
  tr.innerHTML = '<td><select name="item_id[]" class="form-select">' + ITEM_OPTIONS + '</select></td>' +
                 '<td><input type="number" name="qty[]" class="form-control" min="1" value="1"></td>' +
                 '<td style="width:50px"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()"><i class="bi bi-x"></i></button></td>';
  tbody.appendChild(tr);
  var s = document.getElementById('itemSearch'); if (s) s.dispatchEvent(new Event('input'));
}
var swItemSearch = document.getElementById('itemSearch');
if (swItemSearch) {
  swItemSearch.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#itemsBody select[name="item_id[]"] option').forEach(function (opt) {
      opt.style.display = opt.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
  });
}
</script>