<?php
// app/Views/layouts/auth_footer.php
?>
        </div><!-- /.auth-form-wrap -->
    </main>

</div><!-- /.auth-shell -->

<script>
(function () {
    'use strict';

    // ---------- Toggle de senha ----------
    document.querySelectorAll('.auth-password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = this.parentElement.querySelector('input');
            if (!input) return;
            var isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            var icon = this.querySelector('i');
            if (icon) icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });

    // Tema: ver public/js/theme.js

})();
</script>
<script src="<?= ViewHelper::asset('js/theme.js') ?>"></script>
<script src="<?= ViewHelper::asset('js/carregando.js') ?>"></script>

</body>
</html>