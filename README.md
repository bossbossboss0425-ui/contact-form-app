# COACHTECH お問い合わせフォーム

## 概要

- 一般ユーザーが利用する公開のお問い合わせフォームです。
- 誰でもお問い合わせを送信でき、管理者はログイン後にその内容を確認・管理します。

## ER図

```mermaid
erDiagram
    users{
        id bigint pk
        name varchar
        email varchar
        email_verified_at timestamp
        password varchar
        remember_token varchar
        created_at timestamp
        updated_at timestamp
    }
    categories{
        id bigint pk
        content varchar
        created_at timestamp
        updated_at timestamp
    }
    contacts{
        id bigint pk
        category_id bigint fk
        first_name varchar
        last_name varchar
        gender tinyint
        email varchar
        tel varchar
        address varchar
        building varchar
        detail varchar
        created_at timestamp
        updated_at timestamp
    }
    tags{
        id bigint pk
        name varcher
        created_at timestamp
        updated_at timestamp
    }
    contact_tags{
        id bigint pk
        contact_id bigint fk
        tag_id bigint fk
        created_at timestamp
        updated_at timestamp
    }

    categories ||--o{contacts:has
    contacts ||--o{contact_tags:has
    tags ||--o{contact_tags:has

```

## 開発環境URL

- Webアプリケーション（フロントエンド/管理者ページ）: `http://localhost`
- phpMyAdmin（データベース管理Tool）: `http://localhost:8080`

## 環境構築手順

### 1. リポジトリのクローンと移動

```bash
git clone <本リポジトリのURL>
cd <プロジェクトフォルダ名>
```

### 2. Composer依存パッケージのインストール

Dockerコンテナを利用して `vendor` ディレクトリを生成します。

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer install
```

### 3. 環境変数の設定

`.env.example` をコピーして `.env` ファイルを作成します。

```bash
cp .env.example .env
```

`.env` 内のデータベース接続設定が以下になっているか確認・編集します。

```ini
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### 4. Sailコンテナの起動

```bash
./vendor/bin/sail up -d
```

※必要に応じて `sail` エイリアスを設定しておくと便利です。

```bash
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.zshrc
exec $SHELL
```

### 5. アプリケーションキーの生成

```bash
sail artisan key:generate
```

### 6. マイグレーションとシーディング

データベースのテーブル作成および初期データの投入を行います。

```bash
sail artisan migrate:fresh --seed
```

### 7. フロントエンドのセットアップとビルド

```bash
sail npm install
sail npm run dev
```

※ `sail npm run dev` を実行したターミナルは起動したままにしてください。

## 使用技術

- OS:windows11
- PHP:8.2
- Laravel:10.x
- DB:MySQL 8.0
- Webサーバー:Nginx
- フロントエンド:Vite, Tailwind CSS ^3.4.0
- 開発ツール:Docker, Laravel Sail, phpMyAdmin

## API エンドポイント一覧 (V1)

ベースURL: `/api/v1`

| メソッド | パス             | 概要                                               | 認証 |
| :------- | :--------------- | :------------------------------------------------- | :--- |
| `GET`    | `/contacts`      | お問い合わせ一覧取得（検索・ページネーション対応） | 不要 |
| `POST`   | `/contacts`      | お問い合わせ新規登録                               | 不要 |
| `GET`    | `/contacts/{id}` | お問い合わせ詳細取得                               | 不要 |
| `PUT`    | `/contacts/{id}` | お問い合わせ更新                                   | 不要 |
| `DELETE` | `/contacts/{id}` | お問い合わせ削除                                   | 不要 |

## 作成者

太田優子
