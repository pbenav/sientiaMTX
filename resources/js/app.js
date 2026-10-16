import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import { Passkeys } from '@laravel/passkeys';

window.__ = window.__ || function(key, replace = {}) {
    let str = key;
    if (replace && typeof replace === 'object') {
        for (const [k, v] of Object.entries(replace)) {
            str = String(str).replace(':' + k, v);
        }
    }
    return str;
};

window.Alpine = Alpine;
Alpine.plugin(collapse);
window.Passkeys = Passkeys;

// Sientia Floating Draggable Trait (Global registration)
Alpine.data('floatingDraggable', () => ({
    isDragging: false,
    hasDragged: false,
    startX: 0,
    startY: 0,
    isVisible: false,

    init() {
        const checkScroll = (e) => {
            const s = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            let targetScroll = 0;
            if (e && e.target && e.target !== document && e.target !== window && typeof e.target.scrollTop === 'number') {
                targetScroll = e.target.scrollTop || 0;
            }
            const maxScroll = Math.max(s, targetScroll);
            this.isVisible = maxScroll > 50;
            
            if (this.isVisible) {
                this.$el.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                this.$el.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                this.$el.style.opacity = '1';
                this.$el.style.pointerEvents = 'auto';
                this.$el.style.visibility = 'visible';
            } else {
                this.$el.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                this.$el.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                this.$el.style.opacity = '0';
                this.$el.style.pointerEvents = 'none';
                this.$el.style.visibility = 'hidden';
            }
        };
        
        window.addEventListener('scroll', checkScroll, { passive: true, capture: true });
        document.addEventListener('scroll', checkScroll, { passive: true, capture: true });
        window.addEventListener('resize', checkScroll, { passive: true });

        // Global mouse/touch move and up listeners for fluid dragging everywhere
        window.addEventListener('mousemove', (e) => { if (this.isDragging) this.drag(e); });
        window.addEventListener('touchmove', (e) => { if (this.isDragging) this.drag(e); }, { passive: false });
        window.addEventListener('mouseup', () => { if (this.isDragging) this.stopDrag(); });
        window.addEventListener('touchend', () => { if (this.isDragging) this.stopDrag(); });
        
        // Initial checks
        checkScroll();
        setTimeout(checkScroll, 50);
        setTimeout(checkScroll, 200);
        setTimeout(checkScroll, 500);
    },

    startDrag(e) {
        if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input')) return;

        this.isDragging = true;
        this.$el.style.transition = 'none';

        const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
        const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

        const rect = this.$el.getBoundingClientRect();

        if (!this.hasDragged) {
            this.$el.style.bottom = 'auto';
            this.$el.style.right = 'auto';
            this.$el.style.transform = 'none';
            this.$el.style.left = rect.left + 'px';
            this.$el.style.top = rect.top + 'px';
            this.hasDragged = true;
        }

        this.startX = clientX - rect.left;
        this.startY = clientY - rect.top;
    },

    drag(e) {
        if (!this.isDragging) return;
        if (e.cancelable) e.preventDefault();

        const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
        const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

        let newLeft = clientX - this.startX;
        let newTop = clientY - this.startY;

        const rect = this.$el.getBoundingClientRect();
        const maxLeft = window.innerWidth - rect.width;
        const maxTop = window.innerHeight - rect.height;

        newLeft = Math.max(0, Math.min(newLeft, maxLeft));
        newTop = Math.max(0, Math.min(newTop, maxTop));

        this.$el.style.left = newLeft + 'px';
        this.$el.style.top = newTop + 'px';
    },

    stopDrag() {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.$el.style.transition = 'all 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
    }
}));

// Sientia Table Horizontal Drag Scroll Trait (Global registration)
Alpine.data('tableScrollDrag', () => ({
    isDown: false,
    startX: 0,
    scrollLeft: 0,
    hasMoved: false,
    hasOverflow: false,

    init() {
        this.checkOverflow();
        const check = () => this.checkOverflow();
        window.addEventListener('resize', check, { passive: true });
        if (window.ResizeObserver) {
            new ResizeObserver(check).observe(this.$el);
        }
    },

    checkOverflow() {
        this.hasOverflow = this.$el.scrollWidth > this.$el.clientWidth;
    },

    handleMouseDown(e) {
        if (e.button !== 0) return;
        if (e.target.closest('input, select, button, a, label, textarea, [role="button"], [contenteditable="true"]')) {
            return;
        }

        this.isDown = true;
        this.hasMoved = false;
        this.startX = e.pageX - this.$el.offsetLeft;
        this.scrollLeft = this.$el.scrollLeft;
    },

    handleMouseMove(e) {
        if (!this.isDown) return;
        const x = e.pageX - this.$el.offsetLeft;
        const walk = (x - this.startX);

        if (Math.abs(walk) > 4) {
            this.hasMoved = true;
            e.preventDefault();
            this.$el.scrollLeft = this.scrollLeft - walk;
        }
    },

    handleMouseUp() {
        if (!this.isDown) return;
        this.isDown = false;
        setTimeout(() => {
            this.hasMoved = false;
        }, 60);
    }
}));

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
