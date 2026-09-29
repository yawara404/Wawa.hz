/**
 * Wawa.hz: Works ページ最下部のミニゲーム枠
 * - games/falling-survivor/ (p5play 製 "Falling Survivor") を iframe で遅延読み込みする
 * - タッチ端末では親ページ側のパッドから iframe 内へキー操作を転送する
 * - 画面外 / タブ非表示のあいだはゲームの描画ループを止める
 */
document.addEventListener('DOMContentLoaded', () => {
  const section = document.querySelector('[data-mini-game]');
  if (!section) return;

  const stage = section.querySelector('[data-mini-game-stage]');
  const placeholder = section.querySelector('[data-mini-game-placeholder]');
  const frameHost = section.querySelector('[data-mini-game-frame]');
  const startBtn = section.querySelector('[data-mini-game-start]');
  const resetBtn = section.querySelector('[data-mini-game-reset]');
  const pad = section.querySelector('[data-mini-game-pad]');
  if (!stage || !frameHost || !startBtn) return;

  // 1フレーム未満で離したすばやいタップでも p5play が検知できるよう、押下状態を少し保持する
  const MIN_HOLD_MS = 130;
  const KEY_CODES = {
    ' ': 'Space',
    ArrowUp: 'ArrowUp',
    ArrowDown: 'ArrowDown',
    ArrowLeft: 'ArrowLeft',
    ArrowRight: 'ArrowRight',
  };

  let iframe = null;
  let gameApi = null;
  let onScreen = true;
  let pendingStart = false;
  let pendingStartDeadline = 0;
  let startRetryTimer = 0;

  /* ---------------------------------------------------------------
     ゲームの読み込みと起動
     --------------------------------------------------------------- */
  const api = () => {
    if (!iframe || !iframe.contentWindow) return null;
    try {
      gameApi = iframe.contentWindow.wawahzMiniGame || null;
    } catch (_) {
      gameApi = null; // 別オリジンになった場合は制御しない
    }
    return gameApi;
  };

  const pauseGame = () => {
    const game = api();
    if (game && typeof game.pause === 'function') game.pause();
  };

  const resumeGame = () => {
    if (!onScreen || document.hidden) return;
    const game = api();
    if (game && typeof game.resume === 'function') game.resume();
  };

  const mount = () => {
    if (iframe) return;
    iframe = document.createElement('iframe');
    iframe.className = 'mini-game-iframe';
    iframe.title = `${startBtn.dataset.gameTitle || 'Mini Game'} を遊ぶ`;
    iframe.setAttribute('scrolling', 'no');
    iframe.setAttribute('allow', 'fullscreen');
    iframe.setAttribute('allowfullscreen', '');
    iframe.addEventListener('load', () => {
      gameApi = null;
      if (!onScreen || document.hidden) {
        pauseGame();
      } else {
        try { iframe.focus(); } catch (_) { /* フォーカス不可の環境では無視 */ }
      }
      flushPendingStart();
    });
    iframe.src = startBtn.dataset.gameSrc;
    frameHost.appendChild(iframe);
  };

  const showGame = () => {
    mount();
    if (placeholder) placeholder.hidden = true;
    frameHost.hidden = false;
    if (resetBtn) resetBtn.hidden = false;
    if (iframe) {
      try { iframe.focus(); } catch (_) { /* フォーカス不可の環境では無視 */ }
    }
  };

  startBtn.addEventListener('click', (event) => {
    event.preventDefault();
    showGame();
  });

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      if (!iframe) return;
      const game = api();
      if (game && typeof game.restart === 'function') {
        game.restart();
      } else if (iframe.contentWindow) {
        iframe.contentWindow.location.reload();
      }
      try { iframe.focus(); } catch (_) { /* フォーカス不可の環境では無視 */ }
    });
  }

  /* ---------------------------------------------------------------
     タッチ操作パッド (キーボードの無い端末向け)
     --------------------------------------------------------------- */
  const sendKey = (key, isDown) => {
    if (!iframe || !iframe.contentWindow) return;
    const win = iframe.contentWindow;
    const EventCtor = win.KeyboardEvent || window.KeyboardEvent;
    if (!EventCtor) return;
    // イベントは iframe 側の realm で生成し、ゲームの window へ直接配送する
    win.dispatchEvent(new EventCtor(isDown ? 'keydown' : 'keyup', {
      key,
      code: KEY_CODES[key] || key,
      bubbles: true,
      cancelable: true,
    }));
  };

  // START はゲームの読み込み前にも押せる。準備が済むまで入力を保持して一度だけ送る。
  function flushPendingStart() {
    if (!pendingStart) return;
    if (Date.now() > pendingStartDeadline) {
      pendingStart = false;
      if (startRetryTimer) window.clearTimeout(startRetryTimer);
      startRetryTimer = 0;
      return;
    }
    const game = api();
    let ready = false;
    try {
      ready = Boolean(game && iframe.contentWindow.canvas && iframe.contentWindow.frameCount > 0);
    } catch (_) { /* 読み込み中は次回に再確認する */ }
    if (ready) {
      pendingStart = false;
      if (startRetryTimer) window.clearTimeout(startRetryTimer);
      startRetryTimer = 0;
      sendKey(' ', true);
      window.setTimeout(() => sendKey(' ', false), MIN_HOLD_MS);
      return;
    }
    if (!startRetryTimer) {
      startRetryTimer = window.setTimeout(() => {
        startRetryTimer = 0;
        flushPendingStart();
      }, 50);
    }
  }

  if (pad) {
    // タッチ端末に加えて、スマホ幅 (モバイルレイアウト) のときもキーを出しておく
    const coarsePointer = window.matchMedia('(pointer: coarse)');
    const narrowScreen = window.matchMedia('(max-width: 760px)');
    const syncPad = () => { pad.hidden = !(coarsePointer.matches || narrowScreen.matches); };
    syncPad();
    [coarsePointer, narrowScreen].forEach((query) => {
      if (typeof query.addEventListener === 'function') {
        query.addEventListener('change', syncPad);
      } else if (typeof query.addListener === 'function') {
        query.addListener(syncPad); // 古い Safari 向け
      }
    });

    pad.querySelectorAll('[data-game-key]').forEach((button) => {
      const key = button.dataset.gameKey;
      if (key === ' ') {
        button.addEventListener('click', (event) => {
          event.preventDefault();
          showGame();
          pendingStart = true;
          pendingStartDeadline = Date.now() + 30000;
          flushPendingStart();
        });
        return;
      }
      let pressedAt = 0;
      let releaseTimer = 0;

      const press = (event) => {
        event.preventDefault();
        if (releaseTimer) {
          window.clearTimeout(releaseTimer);
          releaseTimer = 0;
        }
        pressedAt = Date.now();
        sendKey(key, true);
      };

      const release = (event) => {
        if (event) event.preventDefault();
        const wait = Math.max(0, MIN_HOLD_MS - (Date.now() - pressedAt));
        if (releaseTimer) window.clearTimeout(releaseTimer);
        releaseTimer = window.setTimeout(() => {
          sendKey(key, false);
          releaseTimer = 0;
        }, wait);
      };

      button.addEventListener('pointerdown', press);
      button.addEventListener('pointerup', release);
      button.addEventListener('pointercancel', release);
      button.addEventListener('pointerleave', release);
      button.addEventListener('contextmenu', (event) => event.preventDefault());
    });
  }

  /* ---------------------------------------------------------------
     画面外 / タブ非表示では止める (スクロールで離れてもCPUを消費しない)
     --------------------------------------------------------------- */
  if (typeof IntersectionObserver !== 'undefined') {
    new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        onScreen = entry.isIntersecting;
        if (onScreen) {
          resumeGame();
        } else {
          pauseGame();
        }
      });
    }, { threshold: 0 }).observe(stage);
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      pauseGame();
    } else {
      resumeGame();
    }
  });
});
