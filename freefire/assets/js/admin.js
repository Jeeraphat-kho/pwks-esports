/**
 * FREE FIRE ESPORTS - Admin Panel JavaScript Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    initSummernoteEditor();
    initDeleteModalConfirmations();
});

/**
 * Initialize Summernote WYSIWYG Editor
 */
function initSummernoteEditor() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.summernote) {
        jQuery('.wysiwyg-editor').summernote({
            placeholder: 'เขียนรายละเอียดกติกาการแข่งขันที่นี่...',
            tabsize: 2,
            height: 320,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'hr']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }
}

/**
 * Handle confirmation dialogs for all delete actions via SweetAlert2
 */
function initDeleteModalConfirmations() {
    const deleteBtns = document.querySelectorAll('.btn-confirm-delete');
    const modalForm = document.getElementById('deleteConfirmForm');

    if (!deleteBtns.length || !modalForm) return;

    deleteBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const actionUrl = btn.getAttribute('data-action') || '';
            const itemName = btn.getAttribute('data-name') || 'รายการนี้';
            const itemId = btn.getAttribute('data-id') || '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'ยืนยันการลบข้อมูล?',
                    html: `<div class="text-start p-2" style="font-size: 15px; color: #cbd5e1;">
                             <p class="mb-1">คุณต้องการลบ <strong>${itemName}</strong> ใช่หรือไม่?</p>
                             <p class="text-danger small mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i> การดำเนินการนี้ไม่สามารถเรียกคืนข้อมูลได้</p>
                           </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> ยืนยันลบ',
                    cancelButtonText: 'ยกเลิก',
                    background: '#0a0f24',
                    color: '#ffffff',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#334155',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        modalForm.action = actionUrl;
                        const idInput = modalForm.querySelector('input[name="id"]');
                        if (idInput) idInput.value = itemId;
                        modalForm.submit();
                    }
                });
            } else {
                if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ ${itemName}?`)) {
                    modalForm.action = actionUrl;
                    const idInput = modalForm.querySelector('input[name="id"]');
                    if (idInput) idInput.value = itemId;
                    modalForm.submit();
                }
            }
        });
    });
}
