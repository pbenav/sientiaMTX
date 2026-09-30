with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_block = """        /* Forzar que el cursor del ratón nunca desaparezca en modales y backdrops de SweetAlert2 */
        body.swal2-shown,
        body.swal2-shown *,
        .swal2-container,
        .swal2-container *,
        .swal2-popup,
        .swal2-modal,
        .swal2-backdrop-show {
            cursor: auto !important;
            pointer-events: auto !important;
        }"""

new_block = """        /* Forzar que el cursor del ratón nunca desaparezca en modales y backdrops de SweetAlert2,
           pero excluimos explícitamente los Toasts para que no bloqueen la interfaz. */
        body.swal2-shown:not(.swal2-toast-shown) .swal2-container,
        body.swal2-shown:not(.swal2-toast-shown) .swal2-container *,
        body.swal2-shown:not(.swal2-toast-shown) .swal2-popup,
        body.swal2-shown:not(.swal2-toast-shown) .swal2-modal,
        body.swal2-shown:not(.swal2-toast-shown) .swal2-backdrop-show {
            cursor: auto !important;
            pointer-events: auto !important;
        }"""

if old_block in content:
    content = content.replace(old_block, new_block)
    with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Replaced!")
else:
    print("Block not found!")
