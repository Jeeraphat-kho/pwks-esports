<?php
/**
 * Shared User Footer for Free Fire Esports
 */
$comp_name = get_setting('competition_name', 'FREE FIRE ESPORTS TOURNAMENT โรงเรียนพร้าววิทยาคม');
?>
        <!-- Footer -->
        <footer class="custom-footer">
            <div class="container">
                <div class="row g-4 justify-content-between">
                    <div class="col-lg-5">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 16px;">
                                <i class="fa-solid fa-fire-flame-curved"></i>
                            </div>
                            <span class="footer-brand-title">PWKS FREE FIRE</span>
                        </div>
                        <p class="footer-desc">
                            <?= e($comp_name) ?> จัดทำขึ้นเพื่อส่งเสริมทักษะการทำงานเป็นทีม การวางแผนกลยุทธ์ และการใช้อีสปอร์ตอย่างสร้างสรรค์
                        </p>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <h5 class="footer-heading">เมนูลัด</h5>
                        <ul class="footer-links">
                            <li><a href="index.php"><i class="fa-solid fa-angle-right"></i> หน้าแรก</a></li>
                            <li><a href="register.php"><i class="fa-solid fa-angle-right"></i> สมัครแข่งขัน</a></li>
                            <li><a href="rules.php"><i class="fa-solid fa-angle-right"></i> กติกา</a></li>
                            <li><a href="bracket.php"><i class="fa-solid fa-angle-right"></i> สายการแข่ง</a></li>
                        </ul>
                    </div>

                    <div class="col-6 col-md-3 col-lg-3">
                        <h5 class="footer-heading">การจัดการ</h5>
                        <ul class="footer-links">
                            <li><a href="admin/login.php"><i class="fa-solid fa-lock"></i> ผู้ดูแลระบบ (Admin)</a></li>
                            <li><a href="../index.html"><i class="fa-solid fa-gamepad"></i> หน้าเลือกเกมหลัก (Portal)</a></li>
                            <li><a href="https://sc.pwks.ac.th/to/disesports" target="_blank"><i class="fa-brands fa-discord"></i> Discord การแข่งขัน</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p class="mb-0">© 2026 สภานักเรียนโรงเรียนพร้าววิทยาคม • Dev by Jeeraphat K. (SC67)</p>
                </div>
            </div>
        </footer>

    </div>

    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Fancybox 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <!-- Custom Esports Controller JS -->
    <script src="assets/js/main.js?v=<?= time() ?>"></script>
</body>
</html>
