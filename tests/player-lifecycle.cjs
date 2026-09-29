// Run with: node tests/player-lifecycle.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
class Element {
  constructor() { this.children = []; this.hidden = true; this.classList = { remove() {} }; }
  appendChild(child) { child.parent = this; this.children.push(child); }
  set textContent(value) { this.text = value; this.children = []; }
  get textContent() { return this.text; }
  setAttribute() {}
}
const elements = new Map();
const document = {
  getElementById(id) {
    if (!elements.has(id)) elements.set(id, new Element());
    return elements.get(id);
  },
  querySelectorAll: () => [],
  querySelector: () => null,
  createElement: () => new Element(),
  head: new Element(),
};
const instances = [];
let plays = 0;
const api = { Player: function(mount, options) {
  // Emulate the real API replacing its mount and deleting it on destroy.
  const parent = mount.parent;
  assert.ok(parent, 'API mount must be attached inside a persistent host');
  const frame = new Element();
  parent.children.splice(parent.children.indexOf(mount), 1, frame);
  this.destroy = () => parent.children.splice(parent.children.indexOf(frame), 1);
  this.options = options;
  instances.push(this);
} };
const window = { YT: api, location: { origin: 'http://wawahz.local' } };
const context = vm.createContext({ document, window, URLSearchParams, setTimeout: () => 0, clearTimeout });
const source = fs.readFileSync(require('node:path').join(__dirname, '../js/theme.js'), 'utf8');
vm.runInContext(source.slice(source.indexOf('  const playerModal ='), source.indexOf('  /** 投稿本文の標準メディア')), context);
const run = code => vm.runInContext(code, context);
const flush = () => new Promise(resolve => setImmediate(resolve));
(async () => {
  const host = document.getElementById('player-modal-yt-host');
  const error = document.getElementById('player-modal-error');
  run("playYouTube('abcdefghijk')"); await flush();
  assert.equal(host.children.length, 1);
  instances[0].options.events.onError({ data: 100 });
  assert.equal(error.hidden, false);
  assert.match(error.textContent, /非公開/);
  run('clearPlayerMedia()');
  assert.equal(host.children.length, 0);
  assert.equal(error.hidden, true);
  instances[0].options.events.onReady({ target: { playVideo: () => plays++ } });
  assert.equal(plays, 0, 'stale ready must not play');
  run("playYouTube('ABCDEFGHIJK')"); await flush();
  assert.equal(host.children.length, 1, 'reopen keeps a visible player');
  run('clearPlayerMedia()');
  window.YT = null;
  run("playYouTube('abcdefghijk'); clearPlayerMedia()");
  window.YT = api; window.onYouTubeIframeAPIReady(); await flush();
  assert.equal(host.children.length, 0, 'close cancels pending API load');
  run("playYouTube('abcdefghijk'); playYouTube('ABCDEFGHIJK')"); await flush();
  assert.equal(host.children.length, 1, 'rapid switch creates only the latest player');
  assert.equal(instances.at(-1).options.videoId, 'ABCDEFGHIJK');
  console.log('Player lifecycle regression checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
