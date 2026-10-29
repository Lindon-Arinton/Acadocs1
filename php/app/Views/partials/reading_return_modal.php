<?php // "Return to encoder" with a note — opened by readingReturn(id, label) in footer.php. ?>
<div class="modal fade" id="readingReturnModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="<?= base_url('reading-program') ?>" class="ajax-form" data-confirm-title="Return to the encoder?">
        <div class="modal-header">
          <h6 class="modal-title fw-bold"><i class="bi bi-arrow-return-left me-2"></i>Return — <span id="readingReturnLabel"></span></h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="return">
          <input type="hidden" name="result_id" id="readingReturnId">
          <label class="form-label small fw-semibold" for="readingReturnNote">What needs fixing? The encoder will see this.</label>
          <textarea name="note" id="readingReturnNote" rows="3" maxlength="500" class="form-control" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger"><i class="bi bi-arrow-return-left me-1"></i>Return</button>
        </div>
      </form>
    </div>
  </div>
</div>
