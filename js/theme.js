/**
 * Wawa.hz: Material 3 Expressive 完全統合 JavaScript
 * - テーマ切替 (Dark/Light)
 * - M3 スライドドロワー & リアルタイム検索
 * - YouTube 公式 IFrame Player API による再生モーダル (自動再生 & 安全停止)
 * - 作品詳細モーダル (Works Detail)
 * - 共通ダイアログ群 (About, SiteMap, Contact, Policy, More Menu)
 * - 画面幅プレビュー切り替え (Auto / Mobile / Desktop)
 * - モバイル Pickup カルーセル
 * - Works ギャラリー フィルターチップ
 * - Track Station 連携
 * - Now Playing 楽曲追加モーダル (専用フォーム → AJAX で追加)
 */
document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;

  // ホームの作品カード: coverで表示される画像の文字領域を測り、白黒を選択。
  // 外部画像などcanvasで読めない場合は白文字＋暗いスクリムを維持する。
  document.querySelectorAll('.home-gallery-artwork').forEach((img) => {
    const card = img.closest('.dhome-inner-card--gallery');
    if (!card) return;
    const updateTone = () => {
      if (!img.naturalWidth || !card.clientWidth || !card.clientHeight) return;
      try {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 20;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) return;
        const scale = Math.max(card.clientWidth / img.naturalWidth, card.clientHeight / img.naturalHeight);
        const width = card.clientWidth / scale;
        const height = card.clientHeight / scale;
        ctx.fillStyle = '#123a3b';
        ctx.fillRect(0, 0, 32, 20);
        ctx.drawImage(img, (img.naturalWidth - width) / 2,
          (img.naturalHeight - height) / 2 + height * 0.55,
          width, height * 0.45, 0, 0, 32, 20);
        const pixels = ctx.getImageData(0, 0, 32, 20).data;
        let luminance = 0;
        const linear = (v) => { v /= 255; return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };
        for (let i = 0; i < pixels.length; i += 4) {
          luminance += 0.2126 * linear(pixels[i]) + 0.7152 * linear(pixels[i + 1]) + 0.0722 * linear(pixels[i + 2]);
        }
        card.dataset.imageTone = luminance / (pixels.length / 4) > 0.4 ? 'light' : 'dark';
      } catch (_) {
        card.dataset.imageTone = 'dark';
      }
    };
    img.addEventListener('load', updateTone);
    if (img.complete) updateTone();
    if (typeof ResizeObserver !== 'undefined') new ResizeObserver(updateTone).observe(card);
  });

  /* =====================================================================
     0. タイトルマーキー
     はみ出すタイトルを初期位置で少し止めてから左へ流し、シームレスにループ。
     ===================================================================== */
  const MARQUEE_SELECTOR = [
    '.cat-article-title',
    '.post-article-card .card-title',
    '.work-card-title',
    '.nowplaying-title',
    '#player-modal-title'
  ].join(',');

  function applyMarquee(el) {
    if (!el) return;
    // 再計測できるように一度リセットする
    const inner = el.querySelector(':scope > .marquee__inner');
    if (inner) {
      const first = inner.querySelector('.marquee__text');
      el.textContent = first ? first.textContent : '';
      el.classList.remove('is-marqueeing');
      el.style.removeProperty('--marquee-duration');
    }
    const text = el.textContent;
    if (!text || !text.trim()) return;

    // いったん1行表示にして、はみ出すかを測る
    el.textContent = '';
    const track = document.createElement('span');
    track.className = 'marquee__inner';
    const copy1 = document.createElement('span');
    copy1.className = 'marquee__text';
    copy1.textContent = text;
    track.appendChild(copy1);
    el.appendChild(track);
    el.classList.add('is-marqueeing');

    // コピー間の余白を除いた「文字だけの幅」ではみ出しを判定する
    const padRight = parseFloat(getComputedStyle(copy1).paddingRight) || 0;
    const textWidth = copy1.getBoundingClientRect().width - padRight;
    if (textWidth <= el.clientWidth + 1) {
      // 収まっている → 元の表示に戻す
      el.textContent = text;
      el.classList.remove('is-marqueeing');
      return;
    }

    // シームレスループ用に2つ目のコピーを追加
    const copy2 = copy1.cloneNode(true);
    copy2.setAttribute('aria-hidden', 'true');
    track.appendChild(copy2);

    const copyWidth = Math.ceil(copy1.getBoundingClientRect().width) || 1;
    el.style.setProperty('--marquee-duration', Math.max(8, copyWidth / 50).toFixed(2) + 's');
  }

  function setupMarquees(rootEl) {
    (rootEl || document).querySelectorAll(MARQUEE_SELECTOR).forEach(applyMarquee);
  }

  /* =====================================================================
     1. テーマ切替 (ダーク / ライト)
     ===================================================================== */
  const themeButtons = document.querySelectorAll('.theme-toggle');

  function applyTheme(theme) {
    root.dataset.theme = theme;
    themeButtons.forEach(button => {
      button.setAttribute('aria-pressed', String(theme === 'dark'));
      button.setAttribute('aria-label', theme === 'dark' ? 'ライトモードに切り替える' : 'ダークモードに切り替える');
      const icon = button.querySelector('.material-symbols-rounded');
      if (icon) icon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    });
    try { localStorage.setItem('wawahz_theme', theme); } catch (e) {}
  }

  const savedTheme = (() => {
    try { return localStorage.getItem('wawahz_theme'); } catch (e) { return null; }
  })();
  applyTheme(savedTheme || (root.dataset.theme === 'light' ? 'light' : 'dark'));

  themeButtons.forEach(button => button.addEventListener('click', () => {
    applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
  }));

  /* =====================================================================
     2. 画面幅プレビューモード切り替え (Auto / Mobile / Desktop)
     ===================================================================== */
  const previewBtn = document.getElementById('preview-mode-btn');
  const previewText = document.getElementById('preview-mode-text');
  const appViewport = document.getElementById('app-viewport');
  const modes = ['Auto', 'Mobile', 'Desktop'];
  let currentModeIndex = 0;

  if (previewBtn && appViewport) {
    previewBtn.addEventListener('click', () => {
      currentModeIndex = (currentModeIndex + 1) % modes.length;
      const mode = modes[currentModeIndex];
      previewText.textContent = mode;

      appViewport.classList.remove('force-mobile', 'force-desktop');
      root.classList.remove('force-mobile', 'force-desktop');

      if (mode === 'Mobile') {
        appViewport.classList.add('force-mobile');
        root.classList.add('force-mobile');
      } else if (mode === 'Desktop') {
        appViewport.classList.add('force-desktop');
        root.classList.add('force-desktop');
      }
      window.dispatchEvent(new Event('resize'));
    });
  }

  /* =====================================================================
     2-2. 3画面（ホーム / カテゴリー・検索 / 情報欄）の横スライド方向を記録
     次の画面ボタン (data-slide-dir="forward") を押したときだけ進行方向を
     sessionStorage に残し、それ以外のリンク移動では消す。
     （header.php のインラインスクリプトが読み取り、html[data-slide-dir] を付ける）
     ===================================================================== */
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link) return;
    try {
      if (link.dataset.slideDir) {
        sessionStorage.setItem('wawahz_slide_dir', link.dataset.slideDir);
      } else {
        sessionStorage.removeItem('wawahz_slide_dir');
      }
    } catch (err) {}
  });

  /* ブラウザの戻る/進むで bfcache から復元されたときは、インラインスクリプトも
     View Transitions も動かないため、直前の移動と逆向きのスライドを自前で流す。 */
  window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    const app = document.getElementById('app-container');
    if (!app) return;
    let dir = '';
    try { dir = sessionStorage.getItem('wawahz_slide_dir') || ''; } catch (err) {}
    app.classList.remove('wawahz-restore-forward', 'wawahz-restore-back');
    void app.offsetWidth; // アニメーションをリセットして再生し直す
    app.classList.add(dir === 'forward' ? 'wawahz-restore-back' : 'wawahz-restore-forward');
  });

  /* =====================================================================
     3. M3 スライドドロワー (Navigation Drawer)
     ===================================================================== */
  const drawerBackdrop = document.getElementById('slide-drawer-backdrop');
  const drawerTriggers = document.querySelectorAll('[data-open-drawer]');
  const drawerCloseBtn = document.getElementById('drawer-close-btn');

  function openDrawer() {
    if (!drawerBackdrop) return;
    drawerBackdrop.classList.add('active', 'open');
    document.body.style.overflow = 'hidden';
    drawerTriggers.forEach(t => t.setAttribute('aria-expanded', 'true'));
  }

  function closeDrawer() {
    if (!drawerBackdrop) return;
    drawerBackdrop.classList.remove('active', 'open');
    document.body.style.overflow = '';
    drawerTriggers.forEach(t => t.setAttribute('aria-expanded', 'false'));
  }

  drawerTriggers.forEach(btn => btn.addEventListener('click', openDrawer));
  if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);

  document.addEventListener('click', e => {
    const trigger = e.target.closest('[data-open-drawer]');
    if (trigger) {
      e.preventDefault();
      openDrawer();
      return;
    }
    const close = e.target.closest('[data-close-drawer]');
    if (close) {
      e.preventDefault();
      closeDrawer();
      return;
    }
  });

  if (drawerBackdrop) {
    drawerBackdrop.addEventListener('click', e => {
      if (e.target === drawerBackdrop) closeDrawer();
    });
  }

  // ドロワー内の簡易インクリメンタル検索
  const drawerSearchInput = document.getElementById('drawer-search-input');
  const drawerSearchResults = document.getElementById('drawer-search-results');
  const drawerSearchClear = document.getElementById('drawer-search-clear-btn');

  if (drawerSearchInput && drawerSearchResults) {
    drawerSearchInput.addEventListener('input', () => {
      const q = drawerSearchInput.value.trim().toLowerCase();
      if (drawerSearchClear) drawerSearchClear.style.display = q ? 'block' : 'none';

      if (!q) {
        drawerSearchResults.style.display = 'none';
        drawerSearchResults.innerHTML = '';
        return;
      }

      // ページ内の記事リンクから一致するものを抽出
      const links = Array.from(document.querySelectorAll('a[href]'));
      const matches = [];
      const seenUrls = new Set();

      links.forEach(a => {
        const text = (a.textContent || '').trim();
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.includes('javascript') || seenUrls.has(href)) return;
        if (text.toLowerCase().includes(q) && text.length > 3) {
          seenUrls.add(href);
          matches.push({ title: text, url: href });
        }
      });

      if (matches.length > 0) {
        drawerSearchResults.innerHTML = matches.slice(0, 5).map(m => `
          <a href="${m.url}" class="drawer-search-result-item" style="display:block; padding:8px 12px; font-size:13px; color:var(--md-sys-color-on-surface); text-decoration:none; border-bottom:1px solid var(--md-sys-color-surface-container-high);">
            <span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle; margin-right:6px; color:var(--md-sys-color-primary);">article</span>
            ${m.title.substring(0, 35)}...
          </a>
        `).join('');
        drawerSearchResults.style.display = 'block';
      } else {
        drawerSearchResults.innerHTML = `<div style="padding:10px 12px; font-size:12px; color:var(--md-sys-color-outline);">「${q}」に一致する見出しは見つかりませんでした</div>`;
        drawerSearchResults.style.display = 'block';
      }
    });

    if (drawerSearchClear) {
      drawerSearchClear.addEventListener('click', () => {
        drawerSearchInput.value = '';
        drawerSearchClear.style.display = 'none';
        drawerSearchResults.style.display = 'none';
        drawerSearchResults.innerHTML = '';
      });
    }
  }

  // メイン検索画面 (search.php) のクリアボタン連携。
  // モバイル / デスクトップの2レイアウト分をまとめて初期化し、
  // 表示されている側の入力欄にだけフォーカスする。
  const searchInputs = Array.from(document.querySelectorAll('[data-search-input]'));
  document.querySelectorAll('[data-search-clear]').forEach((clearBtn) => {
    const bar = clearBtn.closest('.m3-search-bar');
    const input = bar ? bar.querySelector('input[name="s"]') : null;
    if (!input) return;
    input.addEventListener('input', () => {
      clearBtn.style.display = input.value.trim() ? 'inline-flex' : 'none';
    });
    clearBtn.addEventListener('click', () => {
      input.value = '';
      clearBtn.style.display = 'none';
      input.focus();
    });
  });
  const visibleSearchInput = searchInputs.find((input) => input.offsetParent !== null);
  if (visibleSearchInput && !visibleSearchInput.value) {
    visibleSearchInput.focus();
  }

  /* =====================================================================
     4. Now Playing YouTube 公式再生モーダル
     ===================================================================== */
  const playerModal = document.getElementById('dialog-nowplaying-player');
  const playerHost = document.getElementById('player-modal-yt-host');
  const playerError = document.getElementById('player-modal-error');
  const playerMediaSlot = document.getElementById('player-modal-media-slot');
  const playerVideoContainer = document.getElementById('player-modal-video-container');
  const playerBadge = document.getElementById('player-modal-badge');
  const playerTitle = document.getElementById('player-modal-title');
  const playerArtist = document.getElementById('player-modal-artist');
  const playerYear = document.getElementById('player-modal-year');
  const playerMood = document.getElementById('player-modal-mood');
  const playerCommentary = document.getElementById('player-modal-commentary');
  const playerYtExternal = document.getElementById('player-modal-yt-external');
  const playerExternalLabel = document.getElementById('player-modal-external-label');
  const playerCloseBtns = document.querySelectorAll('#player-modal-close-btn, #player-modal-close-action');

  const YOUTUBE_API_SRC = 'https://www.youtube.com/iframe_api';
  let youtubePlayer = null;      // 公式 IFrame Player API のプレイヤーインスタンス
  let youtubeApiPromise = null;  // API ロード結果の共有 Promise
  let playerRequest = 0;

  /** YouTube 公式 IFrame Player API を一度だけ読み込む (失敗時は null で解決) */
  function loadYouTubeApi() {
    if (window.YT && window.YT.Player) return Promise.resolve(window.YT);
    if (youtubeApiPromise) return youtubeApiPromise;

    youtubeApiPromise = new Promise(resolve => {
      const finish = () => resolve(window.YT && window.YT.Player ? window.YT : null);
      const timer = setTimeout(finish, 5000);
      const previousReady = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = () => {
        if (typeof previousReady === 'function') previousReady();
        clearTimeout(timer);
        finish();
      };
      if (!document.querySelector('script[data-wawahz-youtube-api]')) {
        const script = document.createElement('script');
        script.src = YOUTUBE_API_SRC;
        script.async = true;
        script.setAttribute('data-wawahz-youtube-api', '1');
        document.head.appendChild(script);
      }
    });
    return youtubeApiPromise;
  }

  /** モーダル内の再生を停止し、DOM からプレイヤーを除去する */
  function clearPlayerMedia() {
    playerRequest++;
    if (playerError) {
      playerError.hidden = true;
      playerError.textContent = '';
    }
    if (youtubePlayer) {
      // destroy() で iframe ごと破棄して音声を確実に止める
      try {
        if (typeof youtubePlayer.destroy === 'function') youtubePlayer.destroy();
      } catch (e) {}
      youtubePlayer = null;
    }
    if (playerHost) playerHost.textContent = '';
    if (playerMediaSlot) {
      playerMediaSlot.textContent = '';
      playerMediaSlot.hidden = true;
    }
    if (playerVideoContainer) playerVideoContainer.classList.remove('has-inline-media');
  }

  /** YouTube 公式プレイヤーで再生。API が使えない環境では公式埋め込み iframe にフォールバック */
  function playYouTube(ytId) {
    clearPlayerMedia();
    if (!playerHost) return;
    const request = playerRequest;

    const params = new URLSearchParams({
      autoplay: '1',
      playsinline: '1',
      rel: '0',
      enablejsapi: '1',
    });
    const origin = window.location.origin;
    if (origin && origin !== 'null') params.set('origin', origin);
    const embedUrl = 'https://www.youtube.com/embed/' + ytId + '?' + params.toString();

    loadYouTubeApi().then(api => {
      if (request !== playerRequest) return;
      if (!api || !api.Player) {
        const iframe = document.createElement('iframe');
        iframe.src = embedUrl;
        iframe.title = 'YouTube video player';
        iframe.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        playerHost.textContent = '';
        playerHost.appendChild(iframe);
        return;
      }
      // API が置換・削除する要素は、再利用するホストの内側に作成する。
      const mount = document.createElement('div');
      playerHost.appendChild(mount);
      youtubePlayer = new api.Player(mount, {
        videoId: ytId,
        playerVars: { autoplay: 1, playsinline: 1, rel: 0, origin: origin },
        events: {
          // 公式プレイヤーの準備完了後に明示的に再生する (autoplay の取りこぼし対策)
          onReady: event => {
            if (request !== playerRequest) return;
            try { event.target.playVideo(); } catch (e) {}
          },
          onError: event => {
            if (request !== playerRequest || !playerError) return;
            const messages = {
              2: '動画URLが正しくありません。投稿のYouTube URLを確認してください。',
              100: '動画が削除されたか、非公開になっています。投稿のYouTube URLを確認してください。',
              101: 'この動画はサイト内での再生が許可されていません。',
              150: 'この動画はサイト内での再生が許可されていません。',
              153: 'YouTubeがサイトの情報を確認できず、埋め込み再生できません。',
            };
            playerError.textContent = (messages[event.data] || 'YouTubeで動画を再生できません。動画の処理中など、動画側の状態が原因の場合もあります。') + '「YouTube 公式で開く」から確認してください。';
            playerError.hidden = false;
          },
        },
      });
    });
  }

  /** 投稿本文の標準メディア (audio / video) をモーダル内で再生する */
  function playInlineMedia(template) {
    if (!playerMediaSlot || !template) return;
    playerMediaSlot.textContent = '';
    playerMediaSlot.appendChild(template.content.cloneNode(true));
    playerMediaSlot.hidden = false;
    if (playerVideoContainer) playerVideoContainer.classList.add('has-inline-media');
  }

  /** トリガー要素 (または等価なオブジェクト) から再生対象のテンプレートを解決する */
  function resolveMediaTemplate(trigger, data) {
    if (trigger) {
      const nested = trigger.querySelector('template.nowplaying-inline-media');
      if (nested) return nested;
    }
    const id = data.mediaTemplateId || '';
    return id ? document.getElementById(id) : null;
  }

  function openNowPlayingPlayer(source) {
    if (!playerModal) return;

    const trigger = source instanceof Element ? source : null;
    const data = trigger ? trigger.dataset : (source || {});
    const ytId = (data.youtubeId || '').trim();
    const template = resolveMediaTemplate(trigger, data);
    const postUrl = data.postUrl || '';

    // 再生できるメディアが無い場合はモーダルを開かない
    // (存在しない動画 ID へのフォールバックを廃止し「動画を再生できません」を防ぐ)
    if (!ytId && !template) return;

    clearPlayerMedia();
    playerTitle.textContent = data.title || 'Now Playing';
    applyMarquee(playerTitle);
    playerArtist.textContent = data.artist || '';
    if (playerYear) playerYear.textContent = data.year || '2026';
    if (playerMood) playerMood.textContent = data.mood || 'Ambient';
    if (playerCommentary) {
      playerCommentary.textContent = data.commentary || '心地よいアンビエント音響スケッチ。';
    }

    if (ytId) {
      // YouTube 公式プレイヤー (埋め込み iframe / IFrame Player API)
      if (playerBadge) playerBadge.textContent = 'YouTube Player';
      if (playerYtExternal) {
        playerYtExternal.href = `https://www.youtube.com/watch?v=${ytId}`;
        playerYtExternal.hidden = false;
      }
      if (playerExternalLabel) playerExternalLabel.textContent = 'YouTube 公式で開く';
      playYouTube(ytId);
    } else {
      // 投稿本文の標準メディア (audio / video) をモーダル内で再生
      if (playerBadge) playerBadge.textContent = 'Media Player';
      playInlineMedia(template);
      if (playerYtExternal) {
        playerYtExternal.href = postUrl || '#';
        playerYtExternal.hidden = !postUrl;
      }
      if (playerExternalLabel) playerExternalLabel.textContent = '記事を開く';
    }

    playerModal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeNowPlayingPlayer() {
    if (!playerModal) return;
    playerModal.classList.remove('active');
    // YouTube は destroy()、audio / video は DOM から除去して再生を確実に停止する
    clearPlayerMedia();
    document.body.style.overflow = '';
  }

  playerCloseBtns.forEach(btn => btn.addEventListener('click', closeNowPlayingPlayer));

  if (playerModal) {
    playerModal.addEventListener('click', e => {
      if (e.target === playerModal) closeNowPlayingPlayer();
    });
  }

  // [data-open-player] を持つ要素のクリックを監視
  // (カード内のリンク遷移は抑止し、モーダル再生を優先する)
  document.addEventListener('click', event => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;
    const trigger = target.closest('[data-open-player]');
    if (!trigger) return;
    event.preventDefault();
    event.stopPropagation();
    openNowPlayingPlayer(trigger);
  });

  // Track Station「モーダルで再生」ボタン & M3 カスタムドロップダウン
  const infoDropdownField = document.getElementById('info-nowplaying-dropdown-field');
  const infoDropdownMenu = document.getElementById('info-nowplaying-dropdown-menu');
  const infoSelectedText = document.getElementById('info-nowplaying-dropdown-selected');
  const infoOpenPlayerBtn = document.getElementById('info-open-player-btn');

  if (infoDropdownField && infoDropdownMenu) {
    infoDropdownField.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = infoDropdownMenu.classList.contains('open');
      infoDropdownMenu.classList.toggle('open', !isOpen);
      infoDropdownMenu.style.display = !isOpen ? 'block' : 'none';
      infoDropdownField.setAttribute('aria-expanded', String(!isOpen));
    });

    infoDropdownMenu.querySelectorAll('.m3-dropdown-item').forEach((item) => {
      item.addEventListener('click', (e) => {
        e.stopPropagation();
        infoDropdownMenu.querySelectorAll('.m3-dropdown-item').forEach(i => i.classList.remove('selected'));
        item.classList.add('selected');

        const d = item.dataset;
        if (infoSelectedText) {
          infoSelectedText.textContent = `${d.title} - ${d.artist}`;
        }

        if (infoOpenPlayerBtn) {
          infoOpenPlayerBtn.dataset.youtubeId = d.youtubeId || '';
          infoOpenPlayerBtn.dataset.title = d.title || '';
          infoOpenPlayerBtn.dataset.artist = d.artist || '';
          infoOpenPlayerBtn.dataset.album = d.album || '';
          infoOpenPlayerBtn.dataset.mood = d.mood || '';
          infoOpenPlayerBtn.dataset.year = d.year || '';
          infoOpenPlayerBtn.dataset.commentary = d.commentary || '';
          // YouTube 以外のメディアは <template> を参照してモーダルで再生する
          infoOpenPlayerBtn.dataset.mediaTemplateId = d.mediaTemplateId || '';
          infoOpenPlayerBtn.dataset.postUrl = d.postUrl || '';
        }

        infoDropdownMenu.classList.remove('open');
        infoDropdownMenu.style.display = 'none';
        infoDropdownField.setAttribute('aria-expanded', 'false');
      });
    });

    document.addEventListener('click', (e) => {
      if (!infoDropdownField.contains(e.target) && !infoDropdownMenu.contains(e.target)) {
        infoDropdownMenu.classList.remove('open');
        infoDropdownMenu.style.display = 'none';
        infoDropdownField.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // #info-open-player-btn は [data-open-player] の共通ハンドラで再生されるため、
  // ここでは個別のクリック処理を持たない (二重起動を防ぐ)。

  /* =====================================================================
     5. 作品詳細モーダル (Works Detail Dialog)
     ===================================================================== */
  const workModal = document.getElementById('work-detail-dialog');
  const workCoverImg = document.getElementById('work-detail-cover-img');
  const workCatTag = document.getElementById('work-detail-category-tag');
  const workYearTag = document.getElementById('work-detail-year-tag');
  const workTitle = document.getElementById('work-detail-title');
  const workDesc = document.getElementById('work-detail-description');
  const workTagsWrap = document.getElementById('work-detail-tags-container');
  const workPermalink = document.getElementById('work-detail-permalink');
  const workCloseBtn = document.getElementById('work-detail-close-btn');

  function openWorkDetail(card) {
    if (!workModal) return;
    const d = card.dataset;

    if (workCoverImg && d.cover) workCoverImg.style.backgroundImage = `url('${d.cover}')`;
    if (workCatTag) workCatTag.textContent = d.category || 'Work';
    if (workYearTag) workYearTag.textContent = d.year || '2026';
    if (workTitle) workTitle.textContent = d.title || '';
    if (workDesc) workDesc.textContent = d.description || d.summary || '';
    if (workPermalink && d.permalink) workPermalink.href = d.permalink;

    if (workTagsWrap) {
      const tags = (d.tags || '').split(',').filter(Boolean);
      workTagsWrap.innerHTML = tags.map(t => `<span class="work-tag" style="background:var(--md-sys-color-surface-container-high); padding:3px 8px; border-radius:8px; font-size:11px;">#${t.trim()}</span>`).join('');
    }

    workModal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeWorkDetail() {
    if (!workModal) return;
    workModal.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (workCloseBtn) workCloseBtn.addEventListener('click', closeWorkDetail);
  if (workModal) {
    workModal.addEventListener('click', e => {
      if (e.target === workModal) closeWorkDetail();
    });
  }

  document.addEventListener('click', e => {
    const card = e.target.closest('.work-card');
    // 「近日公開」プレースホルダーは作品ではないため詳細ダイアログを開かない
    if (card && !card.classList.contains('is-coming-soon') && !e.target.closest('a')) {
      e.preventDefault();
      openWorkDetail(card);
    }
  });

  /* =====================================================================
     6. 汎用ダイアログ開閉 (data-open-dialog / .dialog-close-btn)
     ===================================================================== */
  document.addEventListener('click', e => {
    const openBtn = e.target.closest('[data-open-dialog]');
    if (openBtn) {
      e.preventDefault();
      const dialogId = openBtn.dataset.openDialog;
      const dialogEl = document.getElementById(dialogId);
      if (dialogEl) {
        closeDrawer();
        dialogEl.classList.add('active', 'open');
        document.body.style.overflow = 'hidden';
      }
      return;
    }

    const closeBtn = e.target.closest('.dialog-close-btn');
    if (closeBtn) {
      e.preventDefault();
      const dialogBackdrop = closeBtn.closest('.m3-dialog-backdrop');
      if (dialogBackdrop) {
        dialogBackdrop.classList.remove('active', 'open');
        document.body.style.overflow = '';
      }
    }
  });

  // バックドロップ外側クリックで閉じる
  document.querySelectorAll('.m3-dialog-backdrop').forEach(bd => {
    bd.addEventListener('click', e => {
      if (e.target === bd) {
        bd.classList.remove('active', 'open');
        document.body.style.overflow = '';
      }
    });
  });

  // ESCキーでモーダル・ドロワーを閉じる
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeDrawer();
      closeNowPlayingPlayer();
      closeWorkDetail();
      document.querySelectorAll('.m3-dialog-backdrop.active, .m3-dialog-backdrop.open').forEach(bd => {
        bd.classList.remove('active', 'open');
      });
      document.body.style.overflow = '';
    }
  });

  /* =====================================================================
     7. モバイル Pickup カルーセル (スワイプ / スナップ / ドット)
     ===================================================================== */
  const pickupTrack = document.getElementById('mobile-pickup-track');
  const pickupPrevBtn = document.getElementById('mobile-pickup-prev-btn');
  const pickupNextBtn = document.getElementById('mobile-pickup-next-btn');
  const pickupDots = document.getElementById('mobile-pickup-dots');

  if (pickupTrack) {
    const pickupSection = pickupTrack.closest('.mobile-pickup-section');
    const pickupSlides = Array.from(pickupTrack.querySelectorAll('.mobile-pickup-slide'));
    const pickupLoop = pickupSlides.length > 1;
    const pickupMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    const pickupDotsList = [];
    const initialIndex = pickupSlides.length >= 3 ? 2 : Math.max(0, pickupSlides.length - 1);
    let pickupIndex = initialIndex;
    let pickupDragging = false;
    let pickupDragStartX = 0;
    let pickupDragStartLeft = 0;
    let pickupDragMoved = false;
    let pickupResizeTimer = 0;

    // スライド i を中央に表示するためのスクロール位置 (末尾は上限でクランプ)
    function pickupOffsetOf(index, limit) {
      const slide = pickupSlides[index];
      if (!slide) return 0;
      const max = typeof limit === 'number'
        ? limit
        : Math.max(0, pickupTrack.scrollWidth - pickupTrack.clientWidth);
      const centered = slide.offsetLeft - (pickupTrack.clientWidth - slide.offsetWidth) / 2;
      return Math.max(0, Math.min(centered, max));
    }

    // 現在のスクロール位置に最も近いスライド
    function pickupNearestIndex() {
      const x = pickupTrack.scrollLeft;
      const limit = Math.max(0, pickupTrack.scrollWidth - pickupTrack.clientWidth);
      let nearest = 0;
      let nearestDistance = Infinity;
      pickupSlides.forEach((slide, i) => {
        const distance = Math.abs(pickupOffsetOf(i, limit) - x);
        if (distance < nearestDistance - 0.5) {
          nearestDistance = distance;
          nearest = i;
        }
      });
      return nearest;
    }

    function setPickupActive(index) {
      pickupIndex = index;
      pickupSlides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
      pickupDotsList.forEach((dot, i) => {
        dot.setAttribute('aria-current', i === index ? 'true' : 'false');
      });
    }

    function goToPickup(index) {
      const total = pickupSlides.length;
      if (!total) return;
      const target = pickupLoop
        ? ((index % total) + total) % total
        : Math.max(0, Math.min(index, total - 1));
      setPickupActive(target);
      pickupTrack.scrollTo({ left: pickupOffsetOf(target), behavior: pickupMotion });
    }

    if (!pickupLoop) {
      // スライドが 1 枚 (記事なし含む) のときは矢印とドットを出さない
      if (pickupSection) pickupSection.classList.add('is-single');
      if (pickupPrevBtn) pickupPrevBtn.hidden = true;
      if (pickupNextBtn) pickupNextBtn.hidden = true;
    } else {
      // ドットインジケーターをスライド数に合わせて生成 (PHP 側は空コンテナのみ出力)
      if (pickupDots) {
        pickupSlides.forEach((slide, i) => {
          const dot = document.createElement('button');
          dot.type = 'button';
          dot.className = 'mobile-pickup-dot';
          const title = slide.querySelector('.mobile-pickup-title');
          dot.setAttribute('aria-label', (i + 1) + ' / ' + pickupSlides.length + (title ? ': ' + title.textContent.trim() : ''));
          dot.setAttribute('aria-current', i === initialIndex ? 'true' : 'false');
          dot.addEventListener('click', () => goToPickup(i));
          pickupDots.appendChild(dot);
          pickupDotsList.push(dot);
        });
        pickupDots.hidden = false;
      }

      if (pickupPrevBtn) pickupPrevBtn.addEventListener('click', () => goToPickup(pickupIndex - 1));
      if (pickupNextBtn) pickupNextBtn.addEventListener('click', () => goToPickup(pickupIndex + 1));

      // スワイプ (ネイティブスクロール) 後にアクティブ表示とドットを同期
      pickupTrack.addEventListener('scroll', () => {
        const nearest = pickupNearestIndex();
        if (nearest !== pickupIndex) setPickupActive(nearest);
      }, { passive: true });

      // キーボード操作 (トラックがフォーカスされているとき)
      pickupTrack.addEventListener('keydown', event => {
        if (event.key === 'ArrowRight') {
          event.preventDefault();
          goToPickup(pickupIndex + 1);
        } else if (event.key === 'ArrowLeft') {
          event.preventDefault();
          goToPickup(pickupIndex - 1);
        } else if (event.key === 'Home') {
          event.preventDefault();
          goToPickup(0);
        } else if (event.key === 'End') {
          event.preventDefault();
          goToPickup(pickupSlides.length - 1);
        }
      });

      // マウスドラッグ (画面幅プレビューの desktop 環境用。タッチはネイティブに委ねる)
      pickupTrack.addEventListener('pointerdown', event => {
        if (event.pointerType === 'touch' || event.button !== 0) return;
        pickupDragging = true;
        pickupDragMoved = false;
        pickupDragStartX = event.clientX;
        pickupDragStartLeft = pickupTrack.scrollLeft;
        pickupTrack.classList.add('is-dragging');
      });

      window.addEventListener('pointermove', event => {
        if (!pickupDragging) return;
        const delta = event.clientX - pickupDragStartX;
        if (!pickupDragMoved && Math.abs(delta) < 4) return;
        pickupDragMoved = true;
        pickupTrack.scrollLeft = pickupDragStartLeft - delta;
      });

      const endPickupDrag = () => {
        if (!pickupDragging) return;
        pickupDragging = false;
        pickupTrack.classList.remove('is-dragging');
        // ドラッグ終了時に最寄りのスライドへスナップし直す
        goToPickup(pickupNearestIndex());
      };
      window.addEventListener('pointerup', endPickupDrag);
      window.addEventListener('pointercancel', endPickupDrag);

      // ドラッグ直後のクリック (記事遷移) をキャンセル
      pickupTrack.addEventListener('click', event => {
        if (!pickupDragMoved) return;
        pickupDragMoved = false;
        event.preventDefault();
        event.stopPropagation();
      }, true);

      // 画面幅の変化 (画面幅プレビュー切り替え / 端末の回転) に合わせて再スナップ
      let pickupLastWidth = pickupTrack.clientWidth;
      window.addEventListener('resize', () => {
        if (pickupTrack.clientWidth === pickupLastWidth) return;
        pickupLastWidth = pickupTrack.clientWidth;
        clearTimeout(pickupResizeTimer);
        pickupResizeTimer = setTimeout(() => goToPickup(pickupIndex), 150);
      });
    }

    if (pickupLoop) {
      setPickupActive(initialIndex);
      const applyInitialScroll = () => {
        pickupTrack.scrollTo({ left: pickupOffsetOf(initialIndex), behavior: 'auto' });
      };
      applyInitialScroll();
      requestAnimationFrame(applyInitialScroll);
      setTimeout(applyInitialScroll, 50);
      window.addEventListener('load', applyInitialScroll, { once: true });
    } else {
      setPickupActive(0);
    }
  }

  /* =====================================================================
     8. Now Playing 楽曲追加モーダル (専用フォーム → AJAX で追加)
     ===================================================================== */
  const addTrackForm = document.getElementById('nowplaying-add-track-form');

  if (addTrackForm) {
    const addTrackConfig = window.wawahzNowPlaying || {};
    const addTrackMessages = addTrackConfig.messages || {};
    const addTrackDialog = document.getElementById('dialog-nowplaying-add-track');
    const addTrackProgress = document.getElementById('nowplaying-add-track-progress');
    const addTrackToast = document.getElementById('nowplaying-add-track-toast');
    const addTrackSubmit = document.getElementById('nowplaying-add-track-submit');
    const addTrackTitleField = document.getElementById('nowplaying-add-track-title');
    const addTrackUrlField = document.getElementById('nowplaying-add-track-youtube');
    const addTrackPreview = document.getElementById('nowplaying-add-track-preview');
    const addTrackPreviewImg = document.getElementById('nowplaying-add-track-preview-img');
    const addTrackGrid = document.getElementById('gallery-cards-container');
    let addTrackToastTimer = 0;

    /** 入力値から YouTube 動画IDを取り出す (サーバー側 wawahz_youtube_id() と同じ判定) */
    function extractAddTrackYouTubeId(value) {
      const text = String(value || '').trim();
      if (/^[A-Za-z0-9_-]{11}$/.test(text)) return text;
      let url;
      try { url = new URL(text); } catch (e) { return ''; }
      if (url.protocol !== 'http:' && url.protocol !== 'https:') return '';
      const host = url.hostname.toLowerCase().replace(/^www\./, '').replace(/^m\./, '');
      let id = '';
      if (host === 'youtu.be') {
        id = url.pathname.replace(/^\//, '').split('/')[0];
      } else if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
        const path = url.pathname.replace(/^\//, '');
        const match = path === 'watch' ? null : path.match(/^(?:embed|shorts|live)\/([^/]+)/);
        if (match) {
          id = match[1];
        } else if (path === 'watch') {
          id = url.searchParams.get('v') || '';
        }
      }
      return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : '';
    }

    function setAddTrackProgress(message, state) {
      if (!addTrackProgress) return;
      addTrackProgress.textContent = message || '';
      addTrackProgress.dataset.state = state || '';
      addTrackProgress.hidden = !message;
    }

    function showAddTrackToast(message) {
      if (!addTrackToast || !message) return;
      addTrackToast.textContent = message;
      addTrackToast.hidden = false;
      clearTimeout(addTrackToastTimer);
      addTrackToastTimer = setTimeout(() => { addTrackToast.hidden = true; }, 6000);
    }

    /** 入力中のURLからサムネイルをプレビューする */
    function updateAddTrackPreview() {
      if (!addTrackPreview || !addTrackPreviewImg) return;
      const id = extractAddTrackYouTubeId(addTrackUrlField ? addTrackUrlField.value : '');
      if (!id) {
        addTrackPreview.hidden = true;
        addTrackPreviewImg.removeAttribute('src');
        return;
      }
      addTrackPreviewImg.src = 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg';
      addTrackPreview.hidden = false;
    }

    function closeAddTrackDialog() {
      if (!addTrackDialog) return;
      addTrackDialog.classList.remove('active');
      document.body.style.overflow = '';
    }

    // モーダルを開いたら入力欄へフォーカスし、前回のエラー表示を消す
    document.querySelectorAll('[data-open-dialog="dialog-nowplaying-add-track"]').forEach(trigger => {
      trigger.addEventListener('click', () => {
        setAddTrackProgress('');
        if (addTrackTitleField) setTimeout(() => addTrackTitleField.focus(), 0);
      });
    });

    if (addTrackUrlField) addTrackUrlField.addEventListener('input', updateAddTrackPreview);
    updateAddTrackPreview();

    addTrackForm.addEventListener('submit', async event => {
      event.preventDefault();
      if (!addTrackConfig.ajaxUrl) {
        setAddTrackProgress(addTrackMessages.error, 'error');
        return;
      }

      if (addTrackSubmit) addTrackSubmit.disabled = true;
      setAddTrackProgress(addTrackMessages.sending, 'pending');

      try {
        const body = new FormData(addTrackForm);
        body.set('action', addTrackConfig.action || 'wawahz_add_track');
        body.set('nonce', addTrackConfig.nonce || '');
        const response = await fetch(addTrackConfig.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body,
        });
        const json = await response.json();
        if (!json || !json.success) {
          const data = (json && json.data) || {};
          throw new Error(data.message || addTrackMessages.error);
        }

        const data = json.data || {};
        if (data.html && addTrackGrid) {
          // AJAX 応答のカードをギャラリー先頭へ差し込む (再生はイベント委譲で動作)
          addTrackGrid.insertAdjacentHTML('afterbegin', data.html);
          const card = addTrackGrid.firstElementChild;
          if (card && card.classList) card.classList.add('is-newly-added');
          if (card) setupMarquees(card);
          const empty = document.querySelector('.nowplaying-empty');
          if (empty) empty.remove();
        } else {
          // カードを差し込めない画面では最新のギャラリーを表示する
          window.location.reload();
          return;
        }

        addTrackForm.reset();
        updateAddTrackPreview();
        closeAddTrackDialog();
        setAddTrackProgress('');
        showAddTrackToast(data.message || addTrackMessages.added);
      } catch (error) {
        setAddTrackProgress((error && error.message) || addTrackMessages.error, 'error');
      } finally {
        if (addTrackSubmit) addTrackSubmit.disabled = false;
      }
    });
  }

  /* =====================================================================
     9. 記事ページの「前の画面に戻る」ボタン (#reading-back-btn)
     同一オリジンのリファラーがある場合は履歴で戻り、直接アクセス等で
     履歴が無い場合は href (記事一覧) にフォールバックする。
     ===================================================================== */
  const readingBackBtn = document.getElementById('reading-back-btn');
  if (readingBackBtn) {
    readingBackBtn.addEventListener('click', event => {
      try {
        if (window.history.length > 1 && document.referrer) {
          const referrerOrigin = new URL(document.referrer).origin;
          if (referrerOrigin === window.location.origin) {
            event.preventDefault();
            window.history.back();
          }
        }
      } catch (e) {
        // URL 解析に失敗した場合は href のフォールバックに任せる
      }
    });
  }

  /* =====================================================================
     11. タイトルマーキーの初期セットアップ & 再計測
     ===================================================================== */
  setupMarquees(document);

  let marqueeResizeTimer = 0;
  window.addEventListener('resize', () => {
    clearTimeout(marqueeResizeTimer);
    marqueeResizeTimer = setTimeout(() => setupMarquees(document), 150);
  });

  /* =====================================================================
     12. Works ギャラリー: 画面サイズに応じたページ送り幅の調整
     - グリッドの表示枚数 (4 / 6 / 8 / 10) は css/nowplaying.css が決める。
     - ページ送りは「実際に見えている枚数」ぶんだけ進める必要があるため、
       表示枚数に合わせて前/次のリンクと 1 / N インジケータを更新する。
       こうしないと、通常デスクトップで 5枚目以降の作品が取りこぼされる。
     ===================================================================== */
  const galleryNav = document.querySelector('[data-gallery-pagination]');
  if (galleryNav) {
    const galleryTotal = parseInt(galleryNav.dataset.galleryTotal || '0', 10);
    const galleryServerPage = parseInt(galleryNav.dataset.galleryPage || '1', 10);

    // css/nowplaying.css のメディアクエリと同じ条件で「見えている枚数」を求める。
    const galleryVisibleSlots = () => {
      if (document.documentElement.classList.contains('force-mobile')) return 4;
      const w = window.innerWidth;
      const h = window.innerHeight;
      if (w >= 2560 || h >= 1600) return 10;
      if (w >= 2200 || h >= 1400) return 8;
      if (w >= 1920 || h >= 1200) return 6;
      return 4;
    };

    const syncGalleryPagination = () => {
      if (!galleryTotal) return;
      const perPage = galleryVisibleSlots();
      const maxPage = Math.max(1, Math.ceil(galleryTotal / perPage));
      const current = Math.min(galleryServerPage, maxPage);

      const indicator = galleryNav.querySelector('.page-indicator');
      if (indicator) {
        indicator.innerHTML = String(current) + '<span class="page-sep">/</span>' + String(maxPage);
      }

      galleryNav.querySelectorAll('a.m3-btn[rel]').forEach((link) => {
        const dir = link.getAttribute('rel');
        const target = dir === 'prev' ? current - 1 : dir === 'next' ? current + 1 : 0;
        if (target < 1 || target > maxPage) {
          link.classList.add('is-disabled');
          link.setAttribute('aria-disabled', 'true');
          link.setAttribute('tabindex', '-1');
          return;
        }
        link.classList.remove('is-disabled');
        link.removeAttribute('aria-disabled');
        link.removeAttribute('tabindex');
        const url = new URL(link.href, window.location.href);
        url.searchParams.set('gallery_page', String(target));
        url.searchParams.set('gallery_per_page', String(perPage));
        link.href = url.toString();
      });

      // 直接リンクなどで現在ページが表示上限を超える場合は1ページ目へ整える。
      if (galleryServerPage > maxPage) {
        const url = new URL(window.location.href);
        url.searchParams.set('gallery_page', '1');
        url.searchParams.set('gallery_per_page', String(perPage));
        window.location.replace(url.toString());
      }
    };

    syncGalleryPagination();
    let galleryResizeTimer = 0;
    window.addEventListener('resize', () => {
      clearTimeout(galleryResizeTimer);
      galleryResizeTimer = setTimeout(syncGalleryPagination, 150);
    });
  }

  // Webフォント読み込み後に文字幅が変わるため再計測する
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(() => setupMarquees(document));
  }
});
