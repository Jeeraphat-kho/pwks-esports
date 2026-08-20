/**
 * FREE FIRE ESPORTS - โรงเรียนพร้าววิทยาคม
 * Frontend JavaScript Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    initParticleCanvas();
    initNavbarScroll();
    initCardTilt();
    initRegistrationForm();
    initAdminLoginForm();
});

/* ==========================================================================
   1. Dynamic Background Ember/Sparks Particles
   ========================================================================== */
function initParticleCanvas() {
    const canvas = document.getElementById('particleCanvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];

    function resizeCanvas() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    class SparkParticle {
        constructor() {
            this.reset();
        }

        reset() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.size = Math.random() * 2.2 + 0.8;
            this.speedX = (Math.random() - 0.5) * 0.8;
            this.speedY = -(Math.random() * 1.2 + 0.4); // ลอยขึ้นด้านบน
            
            // ประกายไฟสีส้ม แดง และเหลืองทองของ Free Fire
            const colors = [
                'rgba(255, 85, 0, ',    // FF Orange
                'rgba(255, 24, 68, ',   // FF Red
                'rgba(255, 152, 0, ',   // Amber
                'rgba(255, 183, 3, '    // Gold
            ];
            const colorBase = colors[Math.floor(Math.random() * colors.length)];
            this.alpha = Math.random() * 0.7 + 0.3;
            this.color = `${colorBase}${this.alpha})`;
            this.decay = Math.random() * 0.005 + 0.002;
        }

        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            this.alpha -= this.decay;

            if (this.alpha <= 0 || this.y < -10 || this.x < -10 || this.x > width + 10) {
                this.reset();
                this.y = height + 10;
            }
        }

        draw() {
            ctx.save();
            ctx.globalAlpha = this.alpha;
            ctx.fillStyle = this.color;
            ctx.shadowBlur = 12;
            ctx.shadowColor = 'rgba(255, 85, 0, 0.8)';
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    }

    const particleCount = Math.min(Math.floor(window.innerWidth / 20), 65);
    for (let i = 0; i < particleCount; i++) {
        particles.push(new SparkParticle());
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);
        for (let p of particles) {
            p.update();
            p.draw();
        }
        requestAnimationFrame(animate);
    }
    animate();
}

/* ==========================================================================
   2. Navbar Scroll Effect
   ========================================================================== */
function initNavbarScroll() {
    const navbar = document.querySelector('.custom-navbar');
    if (!navbar) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 25) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

/* ==========================================================================
   3. 3D Tilt Effect on Cards
   ========================================================================== */
function initCardTilt() {
    const tiltCards = document.querySelectorAll('.tilt-card, .esports-card');
    tiltCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            const rotateX = (-y / rect.height) * 8;
            const rotateY = (x / rect.width) * 8;
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });
}

/* ==========================================================================
   4. Registration Form Controller (register.html)
   ========================================================================== */
function initRegistrationForm() {
    const regForm = document.getElementById('registrationForm');
    if (!regForm) return;

    // --- Dynamic Class Selection (ม.ต้น vs ม.ปลาย) ---
    const levelRadios = document.querySelectorAll('input[name="education_level"]');
    const classSelects = document.querySelectorAll('.player-class-select');

    const juniorClasses = [
        { val: 'ม.1', label: 'มัธยมศึกษาปีที่ 1 (ม.1)' },
        { val: 'ม.2', label: 'มัธยมศึกษาปีที่ 2 (ม.2)' },
        { val: 'ม.3', label: 'มัธยมศึกษาปีที่ 3 (ม.3)' }
    ];

    const seniorClasses = [
        { val: 'ม.4', label: 'มัธยมศึกษาปีที่ 4 (ม.4)' },
        { val: 'ม.5', label: 'มัธยมศึกษาปีที่ 5 (ม.5)' },
        { val: 'ม.6', label: 'มัธยมศึกษาปีที่ 6 (ม.6)' },
        { val: 'ปวช.', label: 'ประกาศนียบัตรวิชาชีพ (ปวช.)' }
    ];

    function updateClassDropdowns(selectedLevel) {
        const classes = selectedLevel === 'junior' ? juniorClasses : seniorClasses;
        classSelects.forEach(select => {
            const currentVal = select.getAttribute('data-prefill') || select.value;
            select.innerHTML = '<option value="">-- เลือกชั้นเรียน --</option>';
            classes.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.val;
                opt.textContent = c.label;
                if (currentVal === c.val) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
            select.disabled = false;
        });
        updateLiveSummary();
    }

    levelRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.checked) {
                updateClassDropdowns(e.target.value);
                // Clear validation error on level container
                document.getElementById('levelError')?.classList.remove('d-block');
            }
        });
    });

    // Check if level was pre-selected (e.g. form reload)
    const initialCheckedLevel = document.querySelector('input[name="education_level"]:checked');
    if (initialCheckedLevel) {
        updateClassDropdowns(initialCheckedLevel.value);
    }

    // --- Team Logo Upload & Preview ---
    const logoDropzone = document.getElementById('logoDropzone');
    const logoInput = document.getElementById('teamLogoInput');
    const logoPreviewBox = document.getElementById('logoPreviewBox');
    const logoPreviewImg = document.getElementById('logoPreviewImg');
    const logoPreviewName = document.getElementById('logoPreviewName');
    const logoPreviewSize = document.getElementById('logoPreviewSize');
    const removeLogoBtn = document.getElementById('removeLogoBtn');

    if (logoDropzone && logoInput) {
        logoDropzone.addEventListener('click', () => logoInput.click());

        logoDropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            logoDropzone.classList.add('dragover');
        });

        logoDropzone.addEventListener('dragleave', () => {
            logoDropzone.classList.remove('dragover');
        });

        logoDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            logoDropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleLogoFile(e.dataTransfer.files[0]);
            }
        });

        logoInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleLogoFile(e.target.files[0]);
            }
        });

        if (removeLogoBtn) {
            removeLogoBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                logoInput.value = '';
                logoPreviewBox.style.display = 'none';
                logoDropzone.style.display = 'block';
                logoPreviewImg.src = '';
            });
        }
    }

    function handleLogoFile(file) {
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('กรุณาเลือกไฟล์รูปภาพที่ถูกต้อง (PNG, JPG, JPEG, WEBP เท่านั้น)');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('ขนาดไฟล์ต้องไม่เกิน 5 MB');
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            logoPreviewImg.src = event.target.result;
            logoPreviewName.textContent = file.name;
            logoPreviewSize.textContent = `${(file.size / 1024).toFixed(1)} KB`;
            logoDropzone.style.display = 'none';
            logoPreviewBox.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    }

    // --- Live Summary Synchronization (Section 08) ---
    function updateLiveSummary() {
        // Level
        const selectedLevelRadio = document.querySelector('input[name="education_level"]:checked');
        const summaryLevel = document.getElementById('summaryLevel');
        if (summaryLevel) {
            summaryLevel.textContent = selectedLevelRadio 
                ? (selectedLevelRadio.value === 'junior' ? 'มัธยมศึกษาตอนต้น (ม.1 - ม.3)' : 'มัธยมศึกษาตอนปลาย / ปวช. (ม.4-6 / ปวช.1-3)')
                : 'ยังไม่ได้เลือก';
        }

        // Team Name
        const teamNameInput = document.getElementById('teamName');
        const summaryTeamName = document.getElementById('summaryTeamName');
        if (summaryTeamName) {
            summaryTeamName.textContent = teamNameInput?.value.trim() || '—';
        }

        // Advisor
        const advisorInput = document.getElementById('teacherAdvisor');
        const summaryAdvisor = document.getElementById('summaryAdvisor');
        if (summaryAdvisor) {
            summaryAdvisor.textContent = advisorInput?.value.trim() || '—';
        }

        // 5 Players (1-4 Main, 5 Substitute Optional)
        for (let i = 1; i <= 5; i++) {
            const title = document.getElementById(`p${i}_title`)?.value || '';
            const fname = document.getElementById(`p${i}_fname`)?.value.trim() || '';
            const lname = document.getElementById(`p${i}_lname`)?.value.trim() || '';
            const ign = document.getElementById(`p${i}_ign`)?.value.trim() || '';
            const pclass = document.getElementById(`p${i}_class`)?.value || '';
            const room = document.getElementById(`p${i}_room`)?.value || '';
            const targetEl = document.getElementById(`summaryPlayer${i}`);

            if (targetEl) {
                const ignBadge = ign ? `<span class="badge bg-dark text-warning border border-warning border-opacity-50 ms-1">${ign}</span>` : '';
                const fallbackText = (i === 5) ? '<span class="text-muted fst-italic">(ไม่มีผู้เล่นสำรอง)</span>' : `(ยังไม่ได้กรอกข้อมูลผู้เล่นที่ ${i})`;
                const namePart = (fname || lname) ? `${title} ${fname} ${lname} ${ignBadge}`.trim() : fallbackText;
                const classPart = (pclass && room) 
                    ? (pclass === 'ปวช.' ? `ปวช. ปี ${room}` : `${pclass}/${room}`) 
                    : (pclass ? pclass : '');
                
                targetEl.innerHTML = `
                    <span><strong>${i === 5 ? 'สำรอง' : `ผู้เล่น ${i}`}:</strong> ${namePart}</span>
                    <span class="badge ${i === 5 ? 'bg-secondary' : 'bg-warning text-dark'}">${classPart || '—'}</span>
                `;
            }
        }
    }

    // Attach real-time listeners for all inputs
    regForm.addEventListener('input', updateLiveSummary);
    regForm.addEventListener('change', updateLiveSummary);

    // Initial trigger
    updateLiveSummary();

    // --- Frontend Form Validation & Submit Simulation ---
    regForm.addEventListener('submit', (e) => {
        e.preventDefault();

        let isValid = true;
        let firstInvalidElement = null;

        // Reset previous validation states
        regForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        regForm.querySelectorAll('.form-feedback-error').forEach(el => el.style.display = 'none');

        // 1. Check Level Selection
        const selectedLevel = document.querySelector('input[name="education_level"]:checked');
        const levelError = document.getElementById('levelError');
        if (!selectedLevel) {
            isValid = false;
            if (levelError) levelError.style.display = 'flex';
            if (!firstInvalidElement) firstInvalidElement = document.getElementById('levelSection');
        }

        // 2. Helper validation function
        function validateField(inputEl, condition, errorMsg) {
            if (!inputEl) return;
            const errorContainer = inputEl.closest('.form-group, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-12')?.querySelector('.form-feedback-error');
            
            if (!condition) {
                isValid = false;
                inputEl.classList.add('is-invalid');
                if (errorContainer) {
                    errorContainer.textContent = errorMsg;
                    errorContainer.style.display = 'flex';
                }
                if (!firstInvalidElement) firstInvalidElement = inputEl;
            }
        }

        // Team info validation
        const teamName = document.getElementById('teamName');
        validateField(teamName, teamName && teamName.value.trim().length > 0, 'กรุณากรอกชื่อทีม');

        const teacherAdvisor = document.getElementById('teacherAdvisor');
        validateField(teacherAdvisor, teacherAdvisor && teacherAdvisor.value.trim().length > 0, 'กรุณากรอกชื่อ-นามสกุลครูที่ปรึกษา');

        // Players validation (Only 1 to 4 Main Players required, 5 is completely optional)
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^0[0-9]{8,9}$/;

        for (let i = 1; i <= 4; i++) {
            const roleText = (i === 1) ? 'กัปตันทีม' : `ผู้เล่นคนที่ ${i}`;
            
            const title = document.getElementById(`p${i}_title`);
            const fname = document.getElementById(`p${i}_fname`);
            const lname = document.getElementById(`p${i}_lname`);
            const ign = document.getElementById(`p${i}_ign`);
            const pclass = document.getElementById(`p${i}_class`);
            const room = document.getElementById(`p${i}_room`);
            const num = document.getElementById(`p${i}_num`);
            const email = document.getElementById(`p${i}_email`);
            const phone = document.getElementById(`p${i}_phone`);

            validateField(title, title && title.value !== '', `กรุณาเลือกคำนำหน้า${roleText}`);
            validateField(fname, fname && fname.value.trim().length > 0, `กรุณากรอกชื่อ${roleText}`);
            validateField(lname, lname && lname.value.trim().length > 0, `กรุณากรอกนามสกุล${roleText}`);
            validateField(ign, ign && ign.value.trim().length > 0, `กรุณากรอกชื่อในเกม (IGN) ของ${roleText}`);
            validateField(pclass, pclass && pclass.value !== '', `กรุณาเลือกชั้นเรียน${roleText}`);
            validateField(room, room && room.value !== '', `กรุณาเลือกห้องเรียน${roleText}`);
            validateField(num, num && num.value.trim().length > 0 && parseInt(num.value) > 0, `กรุณากรอกเลขที่${roleText}`);
            validateField(email, email && emailRegex.test(email.value.trim()), `กรุณากรอกอีเมลที่ถูกต้องของ${roleText}`);
            validateField(phone, phone && phoneRegex.test(phone.value.trim().replace(/-/g, '')), `กรุณากรอกเบอร์โทร 10 หลักของ${roleText}`);
        }

        // 3. Agreement Checkbox
        const confirmCheck = document.getElementById('confirmCheck');
        const confirmCheckError = document.getElementById('confirmCheckError');
        if (confirmCheck && !confirmCheck.checked) {
            isValid = false;
            confirmCheck.classList.add('is-invalid');
            if (confirmCheckError) confirmCheckError.style.display = 'flex';
            if (!firstInvalidElement) firstInvalidElement = confirmCheck;
        }

        if (!isValid) {
            if (firstInvalidElement) {
                firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (typeof firstInvalidElement.focus === 'function') {
                    firstInvalidElement.focus();
                }
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณาตรวจสอบและกรอกข้อมูลที่จำเป็นให้ครบทุกช่อง',
                    background: '#0a0f24',
                    color: '#ffffff',
                    confirmButtonColor: '#ff5500',
                    confirmButtonText: 'ตกลง'
                });
            }
            return;
        }

        // SweetAlert2 Confirmation Dialog before submitting to PHP Backend
        const teamNameVal = teamName ? teamName.value.trim() : '';
        const levelText = selectedLevel.value === 'junior' ? 'มัธยมศึกษาตอนต้น (ม.1 - ม.3)' : 'มัธยมศึกษาตอนปลาย / ปวช. (ม.4 - ม.6 / ปวช.1 - ปวช.3)';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'ยืนยันการส่งใบสมัคร?',
                html: `<div class="text-start p-2" style="font-size: 15px; color: #cbd5e1;">
                         <p class="mb-1"><strong class="text-white">ชื่อทีม:</strong> <span class="text-warning">${teamNameVal}</span></p>
                         <p class="mb-1"><strong class="text-white">ระดับชั้น:</strong> <span class="text-info">${levelText}</span></p>
                         <p class="mb-0 text-muted small mt-2">สมาชิกในทีมทุกคนยอมรับกฎกติกาการแข่งขัน</p>
                       </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-paper-plane me-1"></i> ยืนยันการสมัคร',
                cancelButtonText: 'กลับไปแก้ไข',
                background: '#0a0f24',
                color: '#ffffff',
                confirmButtonColor: '#ff5500',
                cancelButtonColor: '#334155',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังส่งข้อมูล...',
                        text: 'กรุณารอสักครู่ ระบบกำลังบันทึกข้อมูลเข้าสู่ฐานข้อมูล',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => Swal.showLoading(),
                        background: '#0a0f24',
                        color: '#ffffff'
                    });
                    regForm.submit();
                }
            });
        } else {
            if (confirm(`ยืนยันการสมัครทีม ${teamNameVal}?`)) {
                regForm.submit();
            }
        }
    });
}

/* ==========================================================================
   5. Admin Login Prototype (admin/login.html)
   ========================================================================== */
function initAdminLoginForm() {
    const adminForm = document.getElementById('adminLoginForm');
    if (!adminForm) return;

    adminForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const userInput = document.getElementById('adminUsername');
        const passInput = document.getElementById('adminPassword');

        if (!userInput.value.trim() || !passInput.value.trim()) {
            alert('กรุณากรอก Username และ Password ของผู้ดูแลระบบ');
            return;
        }

        // Show prototype notification
        const adminModalEl = document.getElementById('adminPrototypeModal');
        if (adminModalEl && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(adminModalEl);
            modal.show();
        } else {
            alert('ระบบต้นแบบ Frontend Prototype:\nระบบการเข้าสู่ระบบจริงจะพร้อมใช้งานเมื่อเชื่อมต่อระบบหลังบ้าน (Backend & Database)');
        }
    });
}
