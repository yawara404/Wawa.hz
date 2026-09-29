/**
 * Falling Survivor — タッチ端末用の画面内操作キー
 *
 * 単体ページ (Works ページの「別タブで遊ぶ」) の START とタッチ用十字キー。
 * 押した内容をキーボードイベントに変換してゲーム (sketch.js) へ渡す。
 *
 *  - START は単体ページのキャンバス上側に配置し、十字キーはタッチ端末だけに表示する
 *  - Works ページのミニゲーム枠 (iframe) では親ページ側に同じ役割のパッドがあるため表示しない
 *  - sketch.js には手を入れず、押下時間を最低 130ms 確保して 1 フレーム未満のタップも拾う
 */
(function () {
  'use strict';

  var pad = document.querySelector('[data-game-pad]');
  if (!pad) return;

  // iframe (親ページのミニゲーム枠) の中では親ページのパッドに任せる
  try {
    if (window.self !== window.top) return;
  } catch (error) {
    return;
  }

  var EventCtor = window.KeyboardEvent;
  if (!EventCtor) return;

  var MIN_HOLD_MS = 130;
  var KEY_CODES = {
    ' ': 'Space',
    ArrowUp: 'ArrowUp',
    ArrowDown: 'ArrowDown',
    ArrowLeft: 'ArrowLeft',
    ArrowRight: 'ArrowRight',
  };

  /** ゲーム (p5play) は window のキーボードイベントを見ているため、そこへ直接配送する */
  function sendKey(key, isDown) {
    window.dispatchEvent(new EventCtor(isDown ? 'keydown' : 'keyup', {
      key: key,
      code: KEY_CODES[key] || key,
      bubbles: true,
      cancelable: true,
    }));
  }

  var coarsePointer = window.matchMedia('(pointer: coarse)');

  function syncPad() {
    var show = coarsePointer.matches;
    pad.hidden = !show;
    // キーに隠れないよう、canvas の下側へキーの高さぶんの余白を空ける (index.html の CSS)
    document.documentElement.classList.toggle('is-touch-pad', show);
  }

  syncPad();
  if (typeof coarsePointer.addEventListener === 'function') {
    coarsePointer.addEventListener('change', syncPad);
  } else if (typeof coarsePointer.addListener === 'function') {
    coarsePointer.addListener(syncPad); // 古い Safari 向け
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-game-key]'), function (button) {
    var key = button.dataset.gameKey;
    var pressedAt = 0;
    var releaseTimer = 0;

    function press(event) {
      event.preventDefault();
      if (releaseTimer) {
        window.clearTimeout(releaseTimer);
        releaseTimer = 0;
      }
      pressedAt = Date.now();
      sendKey(key, true);
    }

    function release(event) {
      if (event) event.preventDefault();
      var wait = Math.max(0, MIN_HOLD_MS - (Date.now() - pressedAt));
      if (releaseTimer) window.clearTimeout(releaseTimer);
      releaseTimer = window.setTimeout(function () {
        sendKey(key, false);
        releaseTimer = 0;
      }, wait);
    }

    button.addEventListener('pointerdown', press);
    button.addEventListener('pointerup', release);
    button.addEventListener('pointercancel', release);
    button.addEventListener('pointerleave', release);
    button.addEventListener('contextmenu', function (event) {
      event.preventDefault();
    });
  });
})();
