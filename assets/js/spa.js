/**
 * spa.js — Navegación fluida sin recarga, estilo Facebook
 */
(function () {
    'use strict';

    if (!document.startViewTransition) return;

    const CONTENT_SELECTORS = [
        '.hub-contenedor',
        '.dash-main', 
        '.fb-feed',
        '.pf-container',
        '.lk-container',
        '.emp-container',
        'main:not([class])'
    ];

    const bar = document.createElement('div');
    bar.className = 'spa-load-bar';
    bar.innerHTML = '<div></div>';
    document.body.appendChild(bar);

    const st = document.createElement('style');
    st.textContent = `
        .spa-load-bar{position:fixed;top:0;left:0;width:100%;height:3px;z-index:99999;pointer-events:none}
        .spa-load-bar div{height:100%;width:0;background:linear-gradient(90deg,#3B5BDB,#8B5CF6);transition:width 1.2s cubic-bezier(.2,0,.1,1);box-shadow:0 0 8px rgba(59,91,219,.5)}
        .spa-load-bar.loading div{width:70%}
        .spa-load-bar.done div{width:100%;opacity:0;transition:width .4s ease,opacity .4s ease .3s}
        ::view-transition-old(root){animation:vt-out .12s ease forwards}
        ::view-transition-new(root){animation:vt-in .2s ease forwards}
        @keyframes vt-out{from{opacity:1;transform:scale(1)}to{opacity:.95;transform:scale(.995)}}
        @keyframes vt-in{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
    `;
    document.head.appendChild(st);

    function findContent(doc) {
        for (const sel of CONTENT_SELECTORS) {
            const el = doc.querySelector(sel);
            if (el) return el;
        }
        return doc.querySelector('main') || doc.body;
    }

    function findCurrentContent() {
        for (const sel of CONTENT_SELECTORS) {
            const el = document.querySelector(sel);
            if (el && !el.closest('#spa-content')) return el;
        }
        return document.querySelector('main') || document.body;
    }

    function loadStyles(doc) {
        const newLinks = [];
        const existingHrefs = new Set();
        document.querySelectorAll('link[rel="stylesheet"]').forEach(l => existingHrefs.add(l.getAttribute('href')));

        // Cargar nuevos CSS primero (antes de quitar los viejos)
        const promises = [];
        doc.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || existingHrefs.has(href)) return;
            
            const p = new Promise(resolve => {
                const l = document.createElement('link');
                l.rel = 'stylesheet'; l.href = href;
                l.setAttribute('data-spa', '1');
                l.onload = () => resolve();
                l.onerror = () => resolve(); // continuar aunque falle
                document.head.appendChild(l);
            });
            promises.push(p);
            newLinks.push(href);
        });

        return Promise.all(promises).then(() => {
            // Ahora sí, quitar estilos que ya no se usan
            document.querySelectorAll('link[data-spa]').forEach(l => {
                if (!newLinks.includes(l.getAttribute('href'))) l.remove();
            });
            document.querySelectorAll('style[data-spa]').forEach(s => s.remove());

            // Estilos inline
            doc.querySelectorAll('style').forEach(s => {
                if (!s.textContent.trim()) return;
                const ns = document.createElement('style');
                ns.textContent = s.textContent;
                ns.setAttribute('data-spa', '1');
                document.head.appendChild(ns);
            });
        });
    }

    function runScripts(doc) {
        doc.querySelectorAll('script:not([src])').forEach(s => {
            if (!s.textContent.trim()) return;
            try { new Function(s.textContent)(); } catch (e) {}
        });
        doc.querySelectorAll('script[src]').forEach(s => {
            const src = s.getAttribute('src');
            if (!src || document.querySelector(`script[src="${src}"]`)) return;
            const ns = document.createElement('script');
            ns.src = src; ns.setAttribute('data-spa', '1');
            document.body.appendChild(ns);
        });
    }

    let navigating = false;
    let abortController = null;

    async function navigate(url, push = true) {
        // Cancelar navegación anterior
        if (abortController) abortController.abort();
        if (navigating) return;
        navigating = true;
        abortController = new AbortController();
        
        bar.classList.add('loading');
        try {
            const resp = await fetch(url, { signal: abortController.signal });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);

            const html = await resp.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            const title = doc.querySelector('title');
            if (title) document.title = title.textContent;

            // Cargar CSS nuevo primero (sin quitar el viejo aún)
            await loadStyles(doc);

            const newContent = findContent(doc);
            const currentContent = findCurrentContent();

            if (!newContent || !currentContent) {
                window.location.href = url;
                return;
            }

            // Limpiar scripts de la página anterior
            document.querySelectorAll('script[data-spa]').forEach(s => s.remove());

            await document.startViewTransition(() => {
                currentContent.innerHTML = newContent.innerHTML;
            }).finished;

            bar.classList.remove('loading');
            bar.classList.add('done');
            setTimeout(() => bar.classList.remove('done'), 600);

            if (push) history.pushState({ url }, '', url);
            setTimeout(() => runScripts(doc), 150);

        } catch (e) {
            if (e.name !== 'AbortError') window.location.href = url;
        } finally {
            navigating = false;
            abortController = null;
        }
    }

    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') ||
            href.startsWith('http') || href.startsWith('//') ||
            link.hasAttribute('download') || link.hasAttribute('target') ||
            link.closest('.pf-editor-overlay') ||
            link.classList.contains('no-spa') || link.classList.contains('link-salir') ||
            link.classList.contains('btn-logout')) return;
        e.preventDefault();
        navigate(href);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.url) navigate(e.state.url, false);
    });

    console.log('🚀 SPA activado');
})();
