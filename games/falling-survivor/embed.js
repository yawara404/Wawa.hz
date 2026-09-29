/**
 * Falling Survivor — iframe 埋め込み用アダプタ
 *
 * sketch.js (ゲーム本体) には手を入れず、埋め込み時にだけ必要な次の処理を担当する。
 *  - 親ページ (Works ページ下部のミニゲーム枠) から制御するための window.wawahzMiniGame API を公開
 *  - タブが非表示 / 画面外のあいだは p5 のループを止めて CPU を節約
 *  - 矢印キー・スペースキーによる iframe 内スクロールを抑止
 */
(function () {
  'use strict';

  var SCROLL_KEYS = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', ' '];

  window.addEventListener('keydown', function (event) {
    if (SCROLL_KEYS.indexOf(event.key) !== -1) {
      event.preventDefault();
    }
  }, { passive: false });

  // キャンバス上の右クリックメニューで操作が中断しないよう抑止する。
  window.addEventListener('contextmenu', function (event) {
    if (window.canvas && window.canvas.contains(event.target)) {
      event.preventDefault();
    }
  });

  /** 実行中の p5 インスタンス (グローバルモード) を返す。 */
  function p5Instance() {
    return window.p5 && window.p5.instance ? window.p5.instance : null;
  }

  window.wawahzMiniGame = {
    get paused() {
      var instance = p5Instance();
      return instance ? !instance.isLooping() : false;
    },
    /** 描画ループ停止 (ゲームの経過時間も止まる) */
    pause: function () {
      var instance = p5Instance();
      if (instance && instance.isLooping()) {
        instance.noLoop();
      }
    },
    /** 描画ループ再開 */
    resume: function () {
      var instance = p5Instance();
      if (instance && !instance.isLooping()) {
        instance.loop();
      }
    },
    /** タイトル画面へ戻して最初から遊べる状態にする */
    restart: function () {
      if (typeof window.initTitle === 'function') {
        window.initTitle();
      }
      // sketch.js の scene はグローバル let 宣言 (window のプロパティではない) なので直接代入する
      if (typeof scene !== 'undefined') {
        scene = 'title';
      }
      window.wawahzMiniGame.resume();
    },
    /** キーボード操作を受けられるようキャンバスへフォーカスを当てる */
    focusGame: function () {
      var canvas = window.canvas;
      if (canvas && typeof canvas.focus === 'function') {
        canvas.focus();
      }
    }
  };

  // タブが裏に回ったらループを止める (戻ったら再開)
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      window.wawahzMiniGame.pause();
    } else {
      window.wawahzMiniGame.resume();
    }
  });

  window.addEventListener('load', function () {
    window.wawahzMiniGame.focusGame();
  });
})();
