/**
 * Acessibilidade global: escala de fonte no elemento raiz (html) para que rem
 * (Bootstrap, menu, cabeçalho) acompanhe; alto contraste em html.
 */
(function () {
    'use strict';

    var STORAGE_FONT = 'fontSize';
    var STORAGE_CONTRAST = 'highContrast';
    var MIN_FONT = 12;
    var MAX_FONT = 32;
    var DEFAULT_FONT = 16;

    function parseStoredFont() {
        var n = parseInt(localStorage.getItem(STORAGE_FONT), 10);
        if (isNaN(n)) return DEFAULT_FONT;
        if (n < MIN_FONT) return MIN_FONT;
        if (n > MAX_FONT) return MAX_FONT;
        return n;
    }

    var currentFontSize = parseStoredFont();
    var highContrast = localStorage.getItem(STORAGE_CONTRAST) === 'true';

    function applySettings() {
        var root = document.documentElement;
        root.style.fontSize = currentFontSize + 'px';
        document.body.classList.remove('high-contrast');
        if (highContrast) {
            root.classList.add('high-contrast');
        } else {
            root.classList.remove('high-contrast');
        }
    }

    function bindButtons() {
        var btnIncrease = document.getElementById('font-increase');
        var btnReset = document.getElementById('font-reset');
        var btnDecrease = document.getElementById('font-decrease');
        var btnContrast = document.getElementById('contrast-toggle');

        if (btnIncrease) {
            btnIncrease.addEventListener('click', function () {
                if (currentFontSize < MAX_FONT) {
                    currentFontSize = Math.min(MAX_FONT, currentFontSize + 2);
                    localStorage.setItem(STORAGE_FONT, String(currentFontSize));
                    applySettings();
                }
            });
        }
        if (btnDecrease) {
            btnDecrease.addEventListener('click', function () {
                if (currentFontSize > MIN_FONT) {
                    currentFontSize = Math.max(MIN_FONT, currentFontSize - 2);
                    localStorage.setItem(STORAGE_FONT, String(currentFontSize));
                    applySettings();
                }
            });
        }
        if (btnReset) {
            btnReset.addEventListener('click', function () {
                currentFontSize = DEFAULT_FONT;
                localStorage.removeItem(STORAGE_FONT);
                applySettings();
            });
        }
        if (btnContrast) {
            btnContrast.addEventListener('click', function () {
                highContrast = !highContrast;
                localStorage.setItem(STORAGE_CONTRAST, highContrast ? 'true' : 'false');
                applySettings();
            });
        }
    }

    function init() {
        currentFontSize = parseStoredFont();
        highContrast = localStorage.getItem(STORAGE_CONTRAST) === 'true';
        bindButtons();
        applySettings();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
