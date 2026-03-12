

'use strict';

const qs = (selector, rootEl = document) => rootEl.querySelector(selector);

const inputEl =
  qs("input[name='q']") ||
  qs("input[name='query']") ||
  qs("input[type='search']") ||
  document.getElementById('search') ||
  document.getElementById('searchInput') ||
  qs('.search-input');

const formEl = inputEl?.closest('form') || qs('form.search-form') || document.getElementById('searchForm');

const buildUrl = (queryString) => {
  const url = new URL(`${window.location.origin}/search`);
  url.searchParams.set('q', queryString);
  return url.toString();
};

const go = (queryString) => {
  const query = String(queryString || '').trim();
  if (!query) return;
  window.location.href = buildUrl(query);
};

if (formEl) {
  formEl.addEventListener('submit', (e) => {
    const queryValue = inputEl?.value || formEl.querySelector("input[type='search']")?.value || '';
    if (String(queryValue).trim()) {
      if (!formEl.getAttribute('action')) {
        e.preventDefault();
        go(queryValue);
      }
    }
  });
}

document.addEventListener('click', (e) => {
  const btn = e.target?.closest?.('[data-search-submit]') || null;
  if (!btn) return;
  e.preventDefault();
  go(inputEl?.value || '');
});
