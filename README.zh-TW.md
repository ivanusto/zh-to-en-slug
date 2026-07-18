# Chinese to English Slug Converter (中英網址轉換器)

[English Version README](README.md)

一個專門為 WordPress 開發的輕量級外掛，可在發佈文章時自動將中文標題翻譯成英文代稱 (Slug)，為您的網站內容建立乾淨且符合 SEO 的網址結構。

![Version](https://img.shields.io/badge/version-1.1.0-blue) ![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4) ![License](https://img.shields.io/badge/license-Apache--2.0-green)

## 主要功能

- **自動翻譯**：自動將中文文章標題翻譯為英文代稱 (Slug)。
- **保證唯一性**：自動在代稱結尾附加文章 ID，確保跨文章的網址絕對不重複。
- **最大長度設定**：可自由設定代稱的最大字元長度限制。
- **SEO 友善網址**：產生乾淨、不含中文字元編碼 (%xx) 的語意化網址。
- **設定簡單**：提供易於操作的後台設定介面。
- **API 連線測試**：內建 API 連線測試工具，可即時驗證金鑰是否有效。
- **語系支援**：完美支援繁體中文與簡體中文。

## 系統需求

- WordPress 6.0 或更高版本
- PHP 7.4 或更高版本
- 擁有已啟用 Cloud Translation API 的 Google Cloud Platform 帳號

## 安裝步驟

1. 將外掛檔案上傳至 `/wp-content/plugins/chinese-to-english-slug` 目錄，或直接透過 WordPress 後台的外掛安裝介面進行安裝。
2. 在 WordPress 的 **「外掛」** 頁面啟用此外掛。
3. 前往 **設定 > 中英網址轉換** 來進行外掛設定。
4. 輸入您的 Google Cloud Translation API 金鑰，並設定您偏好的代稱最大長度。

### 申請 Google Cloud Translation API 金鑰步驟

1. 前往 [Google Cloud Console](https://console.cloud.google.com/)。
2. 建立新專案或選擇現有專案。
3. 啟用 **Cloud Translation API**。
4. 建立憑證（選擇 API 金鑰）。
5. 建議設定 API 金鑰的使用限制（限制僅能在您的 WordPress 網站網域使用）。
6. 將該 API 金鑰複製並貼到您的外掛設定頁面中。

## 使用方式

1. 安裝並啟用外掛。
2. 前往 **設定 > 中英網址轉換**。
3. 輸入您的 Google Cloud Translation API 金鑰。
4. 設定最大字元長度限制（預設為 30，系統會自動預留 12 個字元給文章 ID，如 `-123`）。
5. 點擊「測試 API 連線」按鈕驗證連線是否成功。
6. 開始撰寫中文標題的文章 — 代稱 (Slug) 將會在您儲存草稿或發佈時自動完成翻譯與生成。

## 常見問題 (FAQ)

**此外掛是否需要 API 金鑰？**
是的，需要 Google Cloud Translation API 金鑰才能將中文標題翻譯成英文。

**翻譯次數是否有上限？**
翻譯次數上限取決於您的 Google Cloud Platform 帳號配額。Google Cloud 提供每個月免費的額度，對於一般網站來說非常充足。

**如果翻譯失敗會怎麼樣？**
如果因為網路或 API 限制導致翻譯失敗，外掛會自動降級回退到 WordPress 預設的代稱生成機制。

**我可以在自動生成後手動修改網址代稱嗎？**
可以的。外掛自動生成代稱後，您隨時可以像平常一樣在文章編輯器中手動編輯和修改 Slug。

**這支援自訂文章類型 (Custom Post Types) 嗎？**
支援。外掛適用於 WordPress 中所有支援並使用網址代稱 (Slug) 的文章類型。

## 隱私權政策

本外掛會將您的文章標題發送到 Google Cloud Translation API 進行翻譯。請確保這符合您的隱私權政策與資料保護要求。

傳送至 Google 伺服器的資料包含：
- 用於翻譯的文章標題
- 您的 API 金鑰憑證

此外掛本身不會儲存、追蹤或收集任何個人隱私數據。如需更多資訊，請造訪 [Google Cloud 隱私權說明](https://cloud.google.com/privacy)。

## 授權條款

本專案採用 [Apache-2.0](LICENSE) 授權條款。
