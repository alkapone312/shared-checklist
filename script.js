function fillText(selector, value) {
    document.querySelectorAll(selector).forEach(el => el.textContent = value);
}

function fillHref(selector, value) {
    document.querySelectorAll(selector).forEach(el => el.href = value);
}

function fillSrc(selector, value) {
    document.querySelectorAll(selector).forEach(el => el.src = value);
}

async function loadMetadata() {
    try {
        const data = await (await fetch("metadata.json")).json();
        fillText('.domain-name', data.domainName);
        fillHref('.main-page', data.mainPage);
        fillHref('.source-link', data.sourceLink);
        fillSrc('.logo', data.logoSrc);
    } catch {
        console.warn("metadata.json could not be loaded.");
    }
}

loadMetadata();