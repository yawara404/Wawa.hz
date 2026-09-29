<?php
/**
 * template-parts/privacy-policy.php
 *
 * プライバシーポリシー本文。
 * - footer.php の #policy-dialog（プライバシーポリシー モーダル）から読み込みます。
 * - WordPress の「設定 » プライバシー」でプライバシーポリシーページを指定した場合は、
 *   フッターのリンクがその固定ページへ自動で切り替わります（footer.php 参照）。
 * - サイト名・URL は WordPress の設定から動的に取得します。
 * - 運営者名と制定日は固定値です。運用に合わせて書き換えてください。
 *
 * @package Wawa.hz
 */

$wawahz_site_name        = get_bloginfo('name');
$wawahz_site_url         = home_url('/');
$wawahz_x_url            = 'https://x.com/ya_ya_moderate';
$wawahz_gravatar_policy  = 'https://automattic.com/privacy/';
$wawahz_google_policy    = 'https://policies.google.com/privacy';
$wawahz_established_date = '2026年9月28日';
?>
<div class="policy-content">
  <h3 class="title-medium policy-heading">基本方針</h3>
  <p>当サイト「<?php echo esc_html($wawahz_site_name); ?>」（以下、「当サイト」とします）は、ユーザーの個人情報の保護を重要視し、以下の通りプライバシーポリシーを定めます。</p>

  <h3 class="title-medium policy-heading">1. 個人情報の取得と利用目的</h3>
  <p>当サイトでは、お問い合わせやコメントの際に、お名前やメールアドレス等の個人情報をご入力いただく場合がございます。取得した個人情報は、お問い合わせに対する回答や必要な連絡のみに利用し、これらの目的以外では利用いたしません。</p>

  <h3 class="title-medium policy-heading">2. コメントについて</h3>
  <p>当サイトにコメントを残す際、スパムや荒らしへの対応としてIPアドレスを収集しています。これはWordPressの標準機能としてサポートされている機能であり、スパムや荒らしへの対応以外にこのIPアドレスを使用することはありません。</p>
  <p>また、メールアドレスから作成される匿名化されたハッシュ文字列が、Gravatar（プロフィール画像サービス）を使用中かどうか確認するため、同サービスに提供されることがあります。同サービスのプライバシーポリシーは <a href="<?php echo esc_url($wawahz_gravatar_policy); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($wawahz_gravatar_policy); ?></a> にあります。</p>

  <h3 class="title-medium policy-heading">3. アクセス解析ツールについて</h3>
  <p>当サイトでは、Googleによるアクセス解析ツール「Googleアナリティクス」を利用しています。このGoogleアナリティクスはトラフィックデータの収集のためにクッキー（Cookie）を使用しております。トラフィックデータは匿名で収集されており、個人を特定するものではありません。</p>
  <p>また、当サイトではテーマの表示設定（ライト／ダークテーマの選択など）をブラウザ内のローカルストレージに保存しています。これは表示を快適に保つためのもので、サーバーへ送信されることはありません。Cookie およびローカルストレージの利用はブラウザの設定で無効にできますが、その場合は当サイトの一部機能が正しく動作しないことがあります。</p>

  <h3 class="title-medium policy-heading">4. 免責事項</h3>
  <p>当サイトからのリンクやバナーなどで移動したサイトで提供される情報、サービス等について一切の責任を負いません。</p>
  <p>また、当サイトのコンテンツ・情報について、できる限り正確な情報を提供するように努めておりますが、正確性や安全性を保証するものではありません。</p>
  <p>当サイトに掲載された内容によって生じた損害等の一切の責任を負いかねますのでご了承ください。</p>

  <h3 class="title-medium policy-heading">5. 著作権について</h3>
  <p>当サイトで掲載している文章や画像などにつきましては、無断転載することを禁止します。</p>
  <p>記事内で紹介している楽曲・映像・リンク先コンテンツ等の著作権は、それぞれの権利者に帰属します。Now Playing ギャラリーおよび作品記事の再生は、YouTube 公式の埋め込みプレイヤーによる表示です。</p>

  <h3 class="title-medium policy-heading">6. 外部サービス・埋め込みコンテンツについて</h3>
  <p>当サイトでは、表示・再生のために以下の外部サービスを利用しています。各サービスが取得する情報の取り扱いは、それぞれの事業者が定めるプライバシーポリシーに準じます。</p>
  <ul class="policy-list">
    <li><strong>YouTube（Google LLC）</strong>：Now Playing ギャラリーや作品記事では、YouTube 公式の埋め込みプレイヤーを通じて動画・楽曲を再生します。再生時には YouTube 側で Cookie 等が付与される場合があります（<a href="<?php echo esc_url($wawahz_google_policy); ?>" target="_blank" rel="noopener noreferrer">Google プライバシー ポリシー</a>）。</li>
    <li><strong>Google Fonts（Google LLC）</strong>：フォント表示のために Google Fonts を読み込んでおり、閲覧時に接続先のサーバーへ IP アドレス等が送信される場合があります。</li>
    <li><strong>Gravatar（Automattic Inc.）</strong>：コメント欄のプロフィール画像表示に利用しています。</li>
  </ul>

  <h3 class="title-medium policy-heading">運営者情報</h3>
  <ul class="policy-list policy-operator-list">
    <li><strong>運営者</strong>：<?php echo esc_html($wawahz_site_name); ?></li>
    <li><strong>サイトURL</strong>：<a href="<?php echo esc_url($wawahz_site_url); ?>"><?php echo esc_html($wawahz_site_url); ?></a></li>
    <li><strong>お問い合わせ</strong>：フッターの「お問い合わせ」フォーム、または <a href="<?php echo esc_url($wawahz_x_url); ?>" target="_blank" rel="noopener noreferrer">𝕏（Twitter） @ya_ya_moderate</a> までご連絡ください。</li>
    <li><strong>制定日</strong>：<?php echo esc_html($wawahz_established_date); ?></li>
  </ul>

  <p class="policy-note">本ポリシーの内容は、法令の改正やサービス内容の変更に応じて予告なく改定することがあります。改定後の内容は、当ページに掲載した時点から適用されます。</p>
</div>
