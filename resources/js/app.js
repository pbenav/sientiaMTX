import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import { Passkeys } from '@laravel/passkeys';

window.Alpine = Alpine;
Alpine.plugin(collapse);
window.Passkeys = Passkeys;

// Dispatch the alpine:init event so that Blade-defined components can register themselves
document.dispatchEvent(new CustomEvent('alpine:init'));

Alpine.start();

window.openQrModal = function(qrSvgBase64, downloadName) {
    if (typeof Swal === 'undefined') {
        const link = document.createElement('a');
        link.href = 'data:image/svg+xml;base64,' + qrSvgBase64;
        link.download = downloadName;
        link.click();
        return;
    }
    
    Swal.fire({
        title: 'Código QR',
        html: `<div class="flex justify-center p-6 bg-white rounded-xl shadow-inner mb-2">
                 <img src="data:image/svg+xml;base64,${qrSvgBase64}" class="w-64 h-64 object-contain" alt="QR Code">
               </div>`,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-download mr-2"></i> Descargar QR',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#4f46e5',
        customClass: {
            popup: 'rounded-2xl dark:bg-gray-800 dark:text-gray-100 border border-gray-100 dark:border-gray-700',
            title: 'text-xl font-bold font-heading'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const link = document.createElement('a');
            link.href = 'data:image/svg+xml;base64,' + qrSvgBase64;
            link.download = downloadName;
            link.click();
        }
    });
};
