// Run with: node tests/add-track-modal.cjs
// theme.js の「Now Playing 楽曲追加モーダル」セクションだけを読み込み、
// 送信フロー (成功 / サーバーエラー / 通信エラー / 設定不足 / グリッド無し) と
// サムネイルプレビュー・フォーカス移動を検証する。
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

class ClassList {
  constructor() { this.values = new Set(); }
  add(name) { this.values.add(name); }
  remove(name) { this.values.delete(name); }
  contains(name) { return this.values.has(name); }
}

class Element {
  constructor(id) {
    this.id = id || '';
    this.attributes = {};
    this.dataset = {};
    this.style = {};
    this.classList = new ClassList();
    this.listeners = {};
    this.hidden = true;
    this.disabled = false;
    this.value = '';
    this.textContent = '';
    this.inserted = [];
    this.fields = [];
  }
  addEventListener(type, handler) { (this.listeners[type] = this.listeners[type] || []).push(handler); }
  dispatch(type) {
    const event = { type, defaultPrevented: false, preventDefault() { event.defaultPrevented = true; } };
    (this.listeners[type] || []).forEach(handler => handler(event));
    return event;
  }
  setAttribute(name, value) { this.attributes[name] = value; }
  removeAttribute(name) { delete this.attributes[name]; }
  insertAdjacentHTML(position, html) {
    this.inserted.push({ position, html });
    this.firstElementChild = new Element('inserted-card');
  }
  focus() { this.focused = true; }
  reset() { this.resetCalled = true; this.fields = []; }
  // 実 DOM と同じく src プロパティと属性を同期させる
  set src(value) { this.attributes.src = value; }
  get src() { return this.attributes.src; }
}

class FakeFormData {
  constructor(form) { this.values = new Map(form instanceof Element ? form.fields : []); }
  set(name, value) { this.values.set(name, value); }
  get(name) { return this.values.get(name); }
  has(name) { return this.values.has(name); }
}

/** theme.js のセクションが参照する DOM を最小構成で用意する */
function createDocument({ withGrid = true, withOpenTrigger = true } = {}) {
  const registry = new Map();
  const document = {
    getElementById(id) {
      if (id === 'gallery-cards-container' && !withGrid) return null;
      if (!registry.has(id)) registry.set(id, new Element(id));
      return registry.get(id);
    },
    querySelectorAll: selector => (withOpenTrigger && selector === '[data-open-dialog="dialog-nowplaying-add-track"]' ? [document.getElementById('nowplaying-add-track-open')] : []),
    querySelector: () => null,
    body: new Element('body'),
  };
  return { document, registry };
}

const config = {
  ajaxUrl: '/wp-admin/admin-ajax.php',
  action: 'wawahz_add_track',
  nonce: 'test-nonce',
  messages: { sending: '追加しています…', added: '楽曲を追加しました。', error: '楽曲を追加できませんでした。' },
};
const window = { wawahzNowPlaying: config, location: { reload() { window.reloaded = true; } } };

const timers = [];
const setTimeoutStub = (handler, delay) => { timers.push({ handler, delay }); return timers.length; };
const runTimers = () => { timers.splice(0).forEach(timer => timer.handler()); };

const fetchCalls = [];
let nextResponse = { success: true, data: { id: 42, html: '<div class="nowplaying-card" data-youtube-id="abcdefghijk"></div>', message: '楽曲を追加しました。ギャラリーの先頭に表示しています。' } };
let failure = null;
const fetch = (url, options) => {
  fetchCalls.push({ url, options });
  if (failure) return Promise.reject(failure);
  return Promise.resolve({ json: async () => nextResponse });
};

const source = fs.readFileSync(path.join(__dirname, '../js/theme.js'), 'utf8');
const section = source.slice(source.indexOf('  const addTrackForm ='), source.lastIndexOf('});'));
assert.ok(section.includes('addTrackForm.addEventListener'), 'Add-track section found in theme.js');

function boot(document) {
  const context = vm.createContext({ document, window, FormData: FakeFormData, fetch, setTimeout: setTimeoutStub, clearTimeout, URL, URLSearchParams, Element });
  vm.runInContext(section, context);
  return {
    get: id => document.getElementById(id),
  };
}

const fields = [
  ['action', 'wawahz_add_track'],
  ['nonce', 'test-nonce'],
  ['title', '雨音のスケッチ'],
  ['youtube_url', 'https://www.youtube.com/watch?v=abcdefghijk'],
  ['status', 'publish'],
];
const flush = () => new Promise(resolve => setImmediate(resolve));

(async () => {
  const harness = createDocument();
  const dom = boot(harness.document);
  const form = dom.get('nowplaying-add-track-form');
  const dialog = dom.get('dialog-nowplaying-add-track');
  const progress = dom.get('nowplaying-add-track-progress');
  const toast = dom.get('nowplaying-add-track-toast');
  const submit = dom.get('nowplaying-add-track-submit');
  const titleField = dom.get('nowplaying-add-track-title');
  const urlField = dom.get('nowplaying-add-track-youtube');
  const preview = dom.get('nowplaying-add-track-preview');
  const previewImg = dom.get('nowplaying-add-track-preview-img');
  const grid = dom.get('gallery-cards-container');
  form.fields = fields.slice();

  // 1. サムネイルプレビュー: URL / 動画ID → 公式サムネイル
  urlField.value = 'https://www.youtube.com/watch?v=abcdefghijk';
  urlField.dispatch('input');
  assert.equal(preview.hidden, false, 'Preview appears for a watch URL');
  assert.equal(previewImg.attributes.src, 'https://img.youtube.com/vi/abcdefghijk/hqdefault.jpg');
  for (const value of ['https://youtu.be/abcdefghijk?t=30', 'https://www.youtube.com/shorts/abcdefghijk', 'https://youtube.com/live/abcdefghijk', 'abcdefghijk']) {
    urlField.value = value;
    urlField.dispatch('input');
    assert.equal(preview.hidden, false, 'Preview stays visible for ' + value);
    assert.equal(previewImg.attributes.src, 'https://img.youtube.com/vi/abcdefghijk/hqdefault.jpg', 'Preview uses the ID for ' + value);
  }
  for (const value of ['', 'not-a-url', 'https://www.youtube.com.evil.test/watch?v=abcdefghijk', 'https://evil.test/watch?v=abcdefghijk', 'javascript:abcdefghijk', 'https://www.youtube.com/watch?v=abcdefghij']) {
    urlField.value = value;
    urlField.dispatch('input');
    assert.equal(preview.hidden, true, 'Preview is hidden for ' + value);
    assert.equal(previewImg.attributes.src, undefined, 'Preview image is cleared for ' + value);
  }

  // 2. モーダルを開くと入力欄へフォーカスする
  dom.get('nowplaying-add-track-open').dispatch('click');
  runTimers();
  assert.equal(titleField.focused, true, 'Title field receives focus when the modal opens');
  assert.equal(progress.hidden, true, 'Previous progress message is cleared on open');

  // 3. 成功: サーバー応答のカードを差し込み、モーダルを閉じてトースト表示
  dialog.classList.add('active');
  form.fields = fields.slice();
  form.dispatch('submit');
  await flush(); await flush();
  assert.equal(fetchCalls.length, 1, 'Submit posts exactly once');
  assert.equal(fetchCalls[0].url, config.ajaxUrl);
  assert.equal(fetchCalls[0].options.method, 'POST');
  assert.equal(fetchCalls[0].options.credentials, 'same-origin');
  assert.equal(fetchCalls[0].options.headers['X-Requested-With'], 'XMLHttpRequest');
  const body = fetchCalls[0].options.body;
  assert.equal(body.get('action'), 'wawahz_add_track');
  assert.equal(body.get('nonce'), 'test-nonce');
  assert.equal(body.get('title'), '雨音のスケッチ');
  assert.equal(body.get('youtube_url'), 'https://www.youtube.com/watch?v=abcdefghijk');
  assert.equal(grid.inserted.length, 1, 'Returned card HTML is inserted into the gallery');
  assert.equal(grid.inserted[0].position, 'afterbegin');
  assert.equal(grid.inserted[0].html, nextResponse.data.html);
  assert.equal(grid.firstElementChild.classList.contains('is-newly-added'), true, 'Inserted card is highlighted');
  assert.equal(form.resetCalled, true, 'Form is reset after success');
  assert.equal(dialog.classList.contains('active'), false, 'Modal closes after success');
  assert.equal(harness.document.body.style.overflow, '', 'Body scroll lock is released');
  assert.equal(toast.hidden, false, 'Toast is shown');
  assert.equal(toast.textContent, nextResponse.data.message);
  assert.equal(progress.hidden, true, 'Progress message is cleared after success');
  assert.equal(submit.disabled, false, 'Submit button is re-enabled');
  assert.equal(window.reloaded, undefined, 'No reload when the card could be inserted');
  runTimers();
  assert.equal(toast.hidden, true, 'Toast hides itself after the timeout');

  // 4. サーバーエラー: モーダルは開いたままエラーを表示し、入力値を保持する
  nextResponse = { success: false, data: { code: 'title_required', message: '曲名を入力してください。' } };
  dialog.classList.add('active');
  form.resetCalled = false;
  form.dispatch('submit');
  await flush(); await flush();
  assert.equal(progress.textContent, '曲名を入力してください。');
  assert.equal(progress.dataset.state, 'error');
  assert.equal(progress.hidden, false);
  assert.equal(dialog.classList.contains('active'), true, 'Modal stays open on failure');
  assert.equal(form.resetCalled, false, 'Form keeps the input on failure');
  assert.equal(submit.disabled, false, 'Submit button is re-enabled after failure');
  assert.equal(grid.inserted.length, 1, 'Failed request inserts no card');

  // 5. 通信エラー
  failure = new Error('network down');
  form.dispatch('submit');
  await flush(); await flush();
  assert.equal(progress.textContent, 'network down');
  assert.equal(progress.dataset.state, 'error');
  failure = null;

  // 6. 設定不足 (AJAX URL が無い画面)
  const originalAjaxUrl = config.ajaxUrl;
  config.ajaxUrl = '';
  const callsBefore = fetchCalls.length;
  form.dispatch('submit');
  await flush();
  assert.equal(fetchCalls.length, callsBefore, 'No request without an AJAX URL');
  assert.equal(progress.textContent, config.messages.error);
  config.ajaxUrl = originalAjaxUrl;

  // 7. グリッドが無い画面 (空のギャラリー) ではページを再読み込みする
  const emptyHarness = createDocument({ withGrid: false, withOpenTrigger: false });
  boot(emptyHarness.document);
  const emptyForm = emptyHarness.document.getElementById('nowplaying-add-track-form');
  emptyForm.fields = fields.slice();
  window.reloaded = false;
  nextResponse = { success: true, data: { id: 43, html: '', url: 'http://wawahz.local/?p=43', message: '追加しました。' } };
  emptyForm.dispatch('submit');
  await flush(); await flush();
  assert.equal(window.reloaded, true, 'Page reloads when there is no gallery grid to update');

  console.log('Now Playing add-track modal checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });