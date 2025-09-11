const byId = id => document.getElementById(id);

function randId() { 
    return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)); 
}

function timeStr(ts) { 
    return new Date(ts*1000).toLocaleString(); 
}

function moveInMap(map, key, newIndex) {
    const entries = [...map.entries()];
    const oldIndex = entries.findIndex(([k]) => k === key);
    if (oldIndex === -1) {
        return map;
    }

    const [entry] = entries.splice(oldIndex, 1);

    if (newIndex < 0) {
        newIndex = 0;
    }

    if (newIndex > entries.length) {
        newIndex = entries.length;
    }

    entries.splice(newIndex, 0, entry);

    return new Map(entries);
}

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