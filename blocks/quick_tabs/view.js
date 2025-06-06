(function() {
'use strict';

const WINDOW_WIDTH_STOP = 768;

const HEADER_TO_CONTENTS = new WeakMap();

const LocationHash = (function() {
    const CHUNK_SEPARATOR = '|';
    function getCurrent()
    {
        const result = {
            others: null,
            tabs: [],
        };
        const hash = window.decodeURIComponent(window.location.hash.replace(/^#/, '')).replace(/^#/, '');
        hash.split(CHUNK_SEPARATOR).filter((chunk) => chunk !== '').forEach((chunk) => {
            const match = chunk.match(/^qt(\d+):([^:#\|]+)$/);
            if (match === null) {
                if (result.others === null) {
                    result.others = chunk;
                } else {
                    result.others += '|' + chunk;
                }
            } else {
                result.tabs[parseInt(match[1], 10)] = window.decodeURIComponent(match[2]);
            }
        });
        return result;
    }
    function setCurrent(data)
    {
        if (!data) {
            return;
        }
        const chunks = [];
        if (typeof data.others === 'string') {
            chunks.push(data.others);
        }
        if (Array.isArray(data.tabs)) {
            data.tabs.forEach((value, index) => {
                chunks.push('qt' + index + ':' + window.encodeURIComponent(value));
            });
        }
        if (chunks.length === 0) {
            try {
                window.history.replaceState(null, '', ' ');
            } catch (e) {
                const restoreX = window.document.body.scrollLeft;
                const restoreY = window.document.body.scrollTop;
                window.location.hash = '';
                window.document.body.scrollLeft = restoreX;
                window.document.body.scrollTop = restoreY;
            }
        } else {
            const hash = chunks.join('|');
            try {
                window.history.replaceState(null, '', '#' + hash);
            } catch (e) {
                window.location.hash = hash;
            }
        }
    }
    return {
        get(quickTabsIndex, handleToTabIndexMap) {
            const data = getCurrent();
            if (data === null || typeof data.tabs[quickTabsIndex] === undefined || !(data.tabs[quickTabsIndex] in handleToTabIndexMap)) {
                return 0;
            }
            return handleToTabIndexMap[data.tabs[quickTabsIndex]];
        },
        set(quickTabsIndex, headerHandle) {
            const data = getCurrent();
            if (data === null) {
                return;
            }
            data.tabs[quickTabsIndex] = headerHandle;
            setCurrent(data);
        }
    };
})();

/**
 * @param {string} opener
 * @param {string} closer
 *
 * @returns {HTMLElement}
 */
function buildTemporaryWrapper(opener, closer)
{
    const container = document.createElement('div');
    container.innerHTML = opener + '<div class="simpleTabsTemporaryWrapper"></div>' + closer;
    return container.children.length === 1 ? container.children[0] : container;
}

/**
 * @constructor
 * @param {HTMLElement} container
 * @param {number} index
 */
function QuickTabs(container, index)
{
    if (container.dataset.quickTabsInitialized) {
        throw new Error('QuickTabs already initialized for this container');
    }
    container.dataset.quickTabsInitialized = 'true';
    const openTags = container.querySelectorAll(':scope >.simpleTabsOpen');
    const firstOpenTag = openTags[0];
    const wrapperOpen = firstOpenTag.dataset.wrapperOpen || '';
    const wrapperClose = firstOpenTag.dataset.wrapperClose || '';
    this.handleToTabIndexMap = {};
    this.index = index;
    this.headersContainer = document.createElement('ul');
    this.headersContainer.className = 'simpleTabs clearfix';
    this.contentsContainer = document.createElement('div');
    this.contentsContainer.className = 'simpleTabsContainer';
    if (wrapperOpen === '' && wrapperClose === '') {
        container.insertBefore(headersContainer, firstOpenTag);
        container.insertBefore(contentsContainer, firstOpenTag);
    } else {
        const wrapper = buildTemporaryWrapper(wrapperOpen, wrapperClose);
        container.insertBefore(wrapper, firstOpenTag);
        const temporaryWrapper = wrapper.querySelector(':scope .simpleTabsTemporaryWrapper');
        temporaryWrapper.parentNode.insertBefore(this.headersContainer, temporaryWrapper);
        temporaryWrapper.parentNode.insertBefore(this.contentsContainer, temporaryWrapper);
        temporaryWrapper.parentNode.removeChild(temporaryWrapper);
    }
    openTags.forEach((openTag, tabIndex ) => {
        const title = openTag.dataset.tabTitle || '';
        const header = document.createElement('li');
        const a = document.createElement('a');
        a.href = '#';
        a.textContent = title;
        a.addEventListener('click', (e) => {
            e.preventDefault();
            this.showTab(header, true);
        });
        header.appendChild(a);
        this.headersContainer.appendChild(header);
        const contents = document.createElement('div');
        contents.className = 'simpleTabsContent clearfix';
        HEADER_TO_CONTENTS.set(header, contents);
        this.contentsContainer.appendChild(contents);
        const handle = openTag.dataset.tabHandle || tabIndex.toString();
        this.handleToTabIndexMap[handle] = tabIndex;
        const titleElement = document.createElement('h2');
        titleElement.className = 'tab-title';
        titleElement.textContent = title;
        openTag.parentNode.insertBefore(titleElement, openTag.nextSibling);
        let current = openTag.nextElementSibling;
        while (current && !current.classList.contains('simpleTabsClose') && !current.classList.contains('simpleTabsOpen')) {
            const next = current.nextElementSibling;
            contents.appendChild(current);
            current = next;
        }
    });
    openTags.forEach((openTag) => openTag.parentNode.removeChild(openTag));
    container.querySelectorAll(':scope >.simpleTabsClose').forEach((closeTag) => closeTag.parentNode.removeChild(closeTag));
    this.setTabFromLocationHash();
    window.addEventListener('hashchange', () => this.setTabFromLocationHash());
}
QuickTabs.prototype = {
    setTabFromLocationHash() {
        const headerIndex = LocationHash.get(this.index, this.handleToTabIndexMap);
        const selectedHeader = this.headersContainer.querySelectorAll(':scope >li')[headerIndex];
        this.showTab(selectedHeader || this.headersContainer.querySelector(':scope >li:first-child'));
    },
    showTab(header, saveHash) {
        const headers = this.headersContainer.querySelectorAll(':scope >li');
        headers.forEach((h) => h.classList.remove('active'));
        header.classList.add('active');
        this.contentsContainer.querySelectorAll(':scope >.simpleTabsContent').forEach((content) => content.style.display = 'none');
        const contents = HEADER_TO_CONTENTS.get(header);
        if (contents) {
            contents.style.display = 'block';
        }
        if (saveHash) {
            const tabIndex = Array.from(headers).indexOf(header);
            const tabHandle = Object.keys(this.handleToTabIndexMap).find((handle) => this.handleToTabIndexMap[handle] === tabIndex) || tabIndex.toString();
            LocationHash.set(this.index, tabHandle);
        }
    }
};

function windowSizeState()
{
    const tabs = document.querySelectorAll('.simpleTabsContent');
    if (window.innerWidth < WINDOW_WIDTH_STOP) {
        tabs.forEach((tab) => tab.style.display = 'block');
    } else {
        tabs.forEach((tab) => tab.style.display = 'none');
        document.querySelectorAll('.simpleTabs li.active').forEach((activeTab) => {
            const contents = HEADER_TO_CONTENTS.get(activeTab);
            if (contents) {
                contents.style.display = 'block';
            }
        });
    }
}


function ready()
{
    const parsedContainers = [];
    let count = 0;
    document.querySelectorAll('.simpleTabsOpen:not(.editmode)').forEach(function(openTag) {
        const container = openTag.parentNode;
        if (!container || parsedContainers.includes(container)) {
            return;
        }
        try {
            new QuickTabs(container, count++);
        } catch (e) {
            console.error('Error initializing QuickTabs:', e);
        }
        parsedContainers.push(container);
    });
    windowSizeState();
    window.addEventListener('resize', () => windowSizeState(), false);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready);
} else {
    ready();
}

})();
