<?php
/**
 * Shared Admin Footer & Scripts Component
 */
?>
    <!-- Global Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content esports-modal border-danger">
                <div class="modal-header esports-modal-header border-danger border-opacity-25">
                    <h5 class="modal-title font-chakra fw-bold text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> ยืนยันการลบข้อมูล
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="deleteConfirmForm" method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="">
                    <div class="modal-body text-center py-3">
                        <p class="text-white mb-1">คุณแน่ใจหรือไม่ว่าต้องการลบ</p>
                        <p class="fw-bold text-warning fs-6" id="deleteItemName">รายการนี้</p>
                        <p class="text-muted small mb-0">การดำเนินการนี้ไม่สามารถเรียกคืนข้อมูลได้</p>
                    </div>
                    <div class="modal-footer esports-modal-footer justify-content-center">
                        <button type="button" class="btn btn-sm btn-secondary font-chakra px-3" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-sm btn-danger font-chakra px-3">
                            <i class="fa-solid fa-trash-can me-1"></i> ยืนยันลบ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery (Required by Summernote) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Fancybox 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <!-- Summernote Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
    <!-- Admin Interactions JS -->
    <script src="../assets/js/admin.js"></script>

</body>
</html>
