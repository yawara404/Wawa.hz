# Wawa.hz — WordPress Theme

> **公開URL**: https://wawa-nk-44.moo.jp/blog/

**Wawa.hz** は、Material 3 Expressive デザインシステムで構築した WordPress ブログ / 作品アーカイブ用のオリジナルテーマです。
モバイル 412dp とデスクトップ 1280dp の 2 レイアウトを 1 つのテーマで切り替え、音楽（Now Playing）・作品ギャラリー・ミニゲームまでを一体で扱えます。

---

## サイト構成

| 画面 | 内容 |
| --- | --- |
| ホーム | Pickup カルーセル + Lately ボックス、Now Playing / Works ボックス連携 |
| Category / Archive | カテゴリートーナルボタン + 連結ページ送り |
| Works Gallery | 作品カードグリッド + 詳細モーダル（`page-gallery.php` / `?view=gallery`） |
| Now Playing | 16:9 サムネ付きの楽曲カード + YouTube 公式 IFrame 再生モーダル（`page-music.php` / `page-nowplaying.php` / `?view=nowplaying`。固定ページ「Music」を優先） |
| Site Info | ブランドカード・仕様グリッド・プライバシーポリシー・お問い合わせフォーム |
| Profile | プロフィール（名前・紹介文・リンク）。`page-profile.php` / `?view=profile`。トップバーの三点メニューから開く |
| Search | リアルタイム絞り込み検索（見出しインクリメンタル抽出） |
| Mini Game | 授業で制作した 2D アクション「Falling Survivor」を独立画面（`page-game.php` / `?view=game`）で同梱表示（Gallery の左右中央ボタンで移動） |

- **デザイントークン**: `--md-sys-color-*` / `--md-sys-shape-*` による Dark / Light 完全対応（`css/variables.css`）
- **インタラクション**: Glassmorphism、リップル風ホバー、View Transitions API によるページ遷移スライド
- **タイポグラフィ**: Inter / Noto Sans JP / Roboto + Material Symbols Rounded
- **外部依存なし**: ミニゲームの p5 / planck / p5play は `games/falling-survivor/lib/` に同梱（オフライン動作）

---

## 技術構成

| 項目 | 内容 |
| --- | --- |
| テーマ形式 | WordPress クラシックテーマ（PHP テンプレート + モジュール分割 CSS） |
| 想定 WordPress | 6.0 以降（`theme.json` version 2 / ブロック API version 2 を使用） |
| 実装言語 | PHP 8 系 / Vanilla JavaScript（UI フレームワーク不使用） |
| ビルド工程 | なし。`package.json` / `composer.json` を必要とせず、そのまま動作 |
| スタイル | `style.css` から用途別に分割した `css/*.css` を `@import`。`--md-sys-*` トークンで一元管理（Tailwind 等は不使用） |
| スクリプト | `js/theme.js`（画面 UI・検索・各モーダル）/ `js/block-nowplaying.js`（Gutenberg ブロック）/ `js/mini-game.js`（ゲーム起動制御） |
| カスタムブロック | `wawahz/nowplaying-gallery`（属性: `limit` / `showHeader`） |
| ショートコード | `[nowplaying_gallery limit="6" show_header="true"]` |
| テーマ設定 | `theme.json`（コンテンツ幅 760px / ワイド幅 1120px、カラーパレット、フォントサイズ） |
| 画面ルーティング | `view.php` が `?view=category / post / gallery / game / nowplaying / profile / info` を `template-parts/` に振り分け |
| 同梱ライブラリ | p5.js / planck.js / p5play（`games/falling-survivor/lib/`）— CDN 非依存で動作 |

---

## デプロイ / 移行

- **Windows 本番サーバーへ移行する手順**: [WINDOWS_DEPLOY.md](WINDOWS_DEPLOY.md)
  - IIS / Apache、PHP 8.2–8.3、MySQL 8、git clone 先、DB 移行、パーマリンク、トラブルシューティングまで記載
- **Windows でもそのまま動きます**（対策済み）
  - `css/images` のシンボリックリンクを廃止し、CSS を `url('../images/...')` 参照に変更
  - `.gitattributes` による改行コードの自動正規化
  - IIS 用 `web.config` を同梱（`.md` / `.json` / `.git` への直接アクセス拒否）
- 更新時は `wp-content/themes/Wawa.hz` で `git pull origin main`。

---

## OGP / ファビコン

- **OGP 画像**: `images/ogp.png`（1200×630・ロゴマーク）
  - SEO SIMPLE PACK の `ssp_output_og_image` フィルタで、**og:image が未設定のとき**に使用（記事はアイキャッチが優先）
  - `twitter:card` は `summary_large_image`
- **ファビコン（サイトアイコン）**: `images/icon-512.png` / `icon-192.png` / `icon-180.png`
  - WordPress の「サイトアイコン」が**未設定のときだけ**テーマが `<link rel="icon">` / `apple-touch-icon` を出力
  - 管理画面でサイトアイコンを設定すればそちらが優先されます

---

## ライセンス

- テーマ本体: GNU General Public License v2 or later（`style.css` のテーマヘッダー参照）
- 同梱サードパーティライブラリ（p5.js / planck.js / p5play、`games/falling-survivor/lib/`）は各配布元の MIT ライセンスに従います。
- 記事内で紹介する楽曲・映像の著作権は各権利者に帰属します。
