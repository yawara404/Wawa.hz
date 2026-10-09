<?php
/**
 * Wawa.hz テーマ: 外部サイトの埋め込み (インクルード用)
 *
 * Wikipedia 系のサマリーカード / ニコニコ動画のプレイヤー / その他 oEmbed 提供元を
 * 登録する。プラグイン (wawahz-embeds) として試作したものをテーマに組み込んだもので、
 * テーマを同期するだけでサイト全体に反映される。
 *
 * 実装は以下の2ファイルに分かれている。
 * - inc/embeds/wiki.php     : Wikimedia API から要約・サムネイルを取得しカードを生成
 * - inc/embeds/handlers.php : ハンドラ登録・本文/ブロックへの適用・エディタ用スタイル
 */

require_once get_template_directory() . '/inc/embeds/wiki.php';
require_once get_template_directory() . '/inc/embeds/handlers.php';
