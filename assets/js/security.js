// Perlindungan Anti-Inspect Element & Proteksi Masukan Token
(function() {
    'use strict';

    // 1. Blokir Klik Kanan (Context Menu)
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    }, false);

    // 2. Blokir Shortcut DevTools (F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, Ctrl+U, Ctrl+S)
    document.addEventListener('keydown', function(e) {
        // F12
        if (e.keyCode === 123 || e.key === 'F12') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }

        // Ctrl + Shift + I / J / C
        if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c' || e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }

        // Ctrl + U (View Source), Ctrl + S (Save Page)
        if (e.ctrlKey && (e.key === 'u' || e.key === 'U' || e.key === 's' || e.key === 'S' || e.keyCode === 85 || e.keyCode === 83)) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, false);

    // 3. Deteksi pembukaan DevTools via ukuran window resize threshold
    let devtoolsOpen = false;
    const threshold = 160;
    const checkDevTools = function() {
        const widthDiff = window.outerWidth - window.innerWidth > threshold;
        const heightDiff = window.outerHeight - window.innerHeight > threshold;
        if ((widthDiff || heightDiff) && !devtoolsOpen) {
            devtoolsOpen = true;
            try {
                console.clear();
                console.warn('%cAKSES DITOLAK: Fitur Inspect Element dan Developer Tools dinonaktifkan demi integritas pemilu.', 'color:red;font-size:16px;font-weight:bold;');
            } catch(e) {}
        } else if (!widthDiff && !heightDiff) {
            devtoolsOpen = false;
        }
    };
    window.addEventListener('resize', checkDevTools);
    setInterval(checkDevTools, 1500);

    // 4. Sanitasi Ketat Input Token (Hanya Alfanumerik dan Strip: a-z, A-Z, 0-9, -)
    // Menolak karakter script/HTML: < > " ' ` / \ & % = ( ) ; [ ] { } dll.
    document.addEventListener('DOMContentLoaded', function() {
        const tokenInput = document.getElementById('pemilih');
        if (!tokenInput) return;

        // Cegah karakter non-token saat mengetik
        tokenInput.addEventListener('keypress', function(e) {
            // Izinkan tombol kontrol (Backspace, Tab, Enter, panah)
            if (e.ctrlKey || e.altKey || e.metaKey || e.key.length > 1) return;
            
            if (!/^[a-zA-Z0-9\-]$/.test(e.key)) {
                e.preventDefault();
                return false;
            }
        });

        // Bersihkan jika ada input yang ditempel (paste/drag-drop/autocomplete)
        const sanitizeInput = function() {
            const original = tokenInput.value;
            // Hanya izinkan alfanumerik dan strip, maksimal 50 karakter
            const cleaned = original.replace(/[^a-zA-Z0-9\-]/g, '').slice(0, 50);
            if (original !== cleaned) {
                tokenInput.value = cleaned;
            }
        };

        tokenInput.addEventListener('input', sanitizeInput);
        tokenInput.addEventListener('paste', function(e) {
            setTimeout(sanitizeInput, 10);
        });
    });
})();

