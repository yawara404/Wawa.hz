# Wawa.hz — WordPress Theme

**Wawa.hz** は、Material 3 Expressive デザインシステムで構築した WordPress ブログ / 作品アーカイブ用のオリジナルテーマです。
モバイル 412dp とデスクトップ 1280dp の 2 レイアウトを 1 つのテーマで切り替え、音楽（Now Playing）・作品ギャラリー・ミニゲームまでを一体で扱えます。

---

## サイト構成

| 画面 | 内容 |
| --- | --- |
| ホーム | Pickup カルーセル + Lately ボックス、Now Playing / Works ボックス連携 |
| Category / Archive | カテゴリートーナルボタン + 連結ページ送り |
| Works Gallery | 作品カードグリッド + 詳細モーダル（`page-gallery.php` / `?view=gallery`） |
| Now Playing | 16:9 サムネ付きの楽曲カード + YouTube 公式 IFrame 再生モーダル（`?view=nowplaying`） |
| Site Info | ブランドカード・仕様グリッド・プライバシーポリシー・お問い合わせフォーム |
| Search | リアルタイム絞り込み検索（見出しインクリメンタル抽出） |
| Mini Game | 授業で制作した 2D アクション「Falling Survivor」を Gallery 下部に同梱表示 |

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
| 画面ルーティング | `view.php` が `?view=category / post / gallery / nowplaying / info` を `template-parts/` に振り分け |
| 同梱ライブラリ | p5.js / planck.js / p5play（`games/falling-survivor/lib/`）— CDN 非依存で動作 |

---

## ライセンス

- テーマ本体: GNU General Public License v2 or later（`style.css` のテーマヘッダー参照）
- 同梱サードパーティライブラリ（p5.js / planck.js / p5play、`games/falling-survivor/lib/`）は各配布元の MIT ライセンスに従います。
- 記事内で紹介する楽曲・映像の著作権は各権利者に帰属します。
