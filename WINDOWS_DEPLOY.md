# Windows 本番サーバーへの移行手順（git clone）

このテーマ（`Wawa.hz`）を **Windows 本番サーバー** に `git clone` で配置し、サイトを移行する手順です。
テーマは Windows / IIS でもそのまま動作するように調整済みです（シンボリックリンク不使用・改行自動正規化）。

> このリポジトリには **テーマのみ** が含まれます。WordPress 本体・DB・アップロード画像は含まれません（別途移行してください）。

---

## 0. 前提ソフトウェア

| 項目 | 推奨 |
| :--- | :--- |
| OS | Windows Server 2019 / 2022 |
| Web サーバー | IIS 10（推奨）または Apache 2.4 |
| PHP | 8.2 / 8.3（8.1 以上） |
| PHP 拡張 | `mysqli`, `mbstring`, `curl`, `openssl`, `gd`(または `imagick`), `intl`, `zip`, `fileinfo` |
| データベース | MySQL 8.0 / MariaDB 10.4 以上 |
| Git | Git for Windows |

IIS の場合は **URL Rewrite モジュール** と **PHP Manager for IIS**（または FastCGI の手動設定）を入れておくと簡単です。

---

## 1. WordPress 本体を配置

例: `C:\inetpub\wwwroot\blog`（IIS）または `D:\sites\blog`（Apache）。

WordPress 日本語版をダウンロードして展開します。

---

## 2. テーマを git clone

```powershell
cd C:\inetpub\wwwroot\blog\wp-content\themes
git clone https://github.com/yawara404/Wawa.hz.git Wawa.hz
```

- **フォルダ名は `Wawa.hz` のままで OK**（テーマ内は `get_template_directory()` / `get_template_directory_uri()` で解決しているため、ディレクトリ名に依存しません）。
- 改行コードは同梱の `.gitattributes` で自動正規化されます。Windows で作業する場合は次を推奨:
  ```powershell
  git config --global core.autocrlf true
  ```
- **シンボリックリンクは使っていません**（`css/images` の symlink は廃止し、CSS 側を `../images/` 参照に変更済み）。Developer Mode 未設定の Windows でもそのまま動きます。

---

## 3. `wp-config.php` を作成

`wp-config-sample.php` をコピーして `wp-config.php` を作成し、以下を設定します。

- DB 名 / ユーザー / パスワード / ホスト（`localhost` など）
- `$table_prefix` … **移行元と同じ接頭辞**（例 `wp_`）
- 認証用ソルト（[WordPress のソルト生成](https://api.wordpress.org/secret-key/1.1/salt/)で再生成）
- 本番では `define('WP_DEBUG', false);`

> `wp-config.php` は `.gitignore` 済み（リポジトリには含まれません）。

---

## 4. データベースを移行

**旧サーバー**
```bash
mysqldump -u USER -p DBNAME > backup.sql
```

**新サーバー（Windows）**
```powershell
mysql -u USER -p DBNAME < backup.sql
```

**ドメイン/URL が変わる場合**は置換します（`search-replace` 推奨）:
```powershell
wp search-replace "https://old.example.com" "https://new.example.com" --all-tables --precise
```
`wp-cli` が無い場合は phpMyAdmin の置換、または `wp_options` の `siteurl` / `home` を更新してください。

---

## 5. `wp-content` を移行（git 管理外）

- `wp-content/uploads` … **丸ごとコピー**（画像アップロード。git には含まれません）
- `wp-content/plugins` … 使用プラグインをコピー、または管理画面から再インストール
- `wp-content/upgrade`, `wp-content/cache` など … コピー不要

---

## 6. IIS の設定

1. **FastCGI で PHP を関連付け**（PHP Manager for IIS が便利）。
2. **URL Rewrite モジュール**を入れ、サイト ルートに WordPress 用 `web.config` を配置（パーマリンク用）:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
        <rule name="WordPress" patternSyntax="Wildcard">
          <match url="*" />
          <conditions>
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="index.php" />
        </rule>
      </rules>
    </rewrite>
  </system.webServer>
</configuration>
```

3. **書き込み権限**: `wp-content/uploads` に IIS_IUSRS（またはアプリ プール ID）の書き込み権限を付与。
4. テーマ同梱の `web.config` が `.md` / `.json` / `.git` 等への直接アクセスを拒否します（Apache では `.htaccess` が同等の役割）。

---

## 7. Apache（Windows）の場合

- `httpd.conf`（またはバーチャルホスト）で対象ディレクトリの `AllowOverride All` を有効化（同梱の `.htaccess` を反映）。
- `mod_rewrite` を有効化。

---

## 8. 仕上げ

1. 管理画面 → **外観 → テーマ** で「**Wawa.hz**」を有効化。
2. **設定 → パーマリンク** を「投稿名」などに設定して保存（`web.config` / `.htaccess` を再生成）。
3. WP-Optimize などのキャッシュ系プラグインを使っている場合は**キャッシュをクリア**。
4. HTTPS 化する場合は SSL 証明書を設定（必要なら `wp-config.php` に `define('FORCE_SSL_ADMIN', true);`）。
5. `git pull` で更新する場合は `wp-content/themes/Wawa.hz` で実行:
   ```powershell
   cd C:\inetpub\wwwroot\blog\wp-content\themes\Wawa.hz
   git pull origin main
   ```

---

## トラブルシューティング

| 症状 | 確認ポイント |
| :--- | :--- |
| 画像（背景パターン等）が出ない | `wp-content/themes/Wawa.hz/images/` が存在するか。CSS は `../images/` 参照に変更済み（symlink 不要） |
| 500 Internal Server Error | PHP バージョン / `mysqli` 拡張 / `web.config` の記述ミス |
| トップ以外が 404 | URL Rewrite（IIS）または `mod_rewrite` + `.htaccess`（Apache）を確認 |
| 管理バーや「曲を追加」ボタンが出ない | WordPress に**ログインしているか**（管理バー・一部ボタンはログイン状態で表示が変わります） |
| 保存・アップロードに失敗 | `wp-content/uploads` の書き込み権限 |

---

## テーマが Windows で動くための対策（このリポジトリで対応済み）

- `css/images` の**シンボリックリンクを廃止**し、CSS の参照を `url('../images/...')` に変更
- `.gitattributes` による**改行コードの自動正規化**
- IIS 用 `web.config` を同梱（`.md` / `.json` / `.git` への直接アクセス拒否）
- パスはすべて WordPress の `get_template_directory()` / `get_template_directory_uri()` 経由（Windows のパス区切りでも動作）
