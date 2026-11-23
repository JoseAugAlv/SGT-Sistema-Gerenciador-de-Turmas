<script>
// Máscara de telefone: (11) 99999-8888 ou (11) 9999-8888
document.querySelectorAll('[data-mascara-tel]').forEach(function (el) {
    el.addEventListener('input', function () {
        var d = el.value.replace(/\D/g, '').slice(0, 11);
        if (d.length > 10)     el.value = '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
        else if (d.length > 6) el.value = '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
        else if (d.length > 2) el.value = '(' + d.slice(0, 2) + ') ' + d.slice(2);
        else el.value = d;
    });
});
</script>
