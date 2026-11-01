# Q4I Lotto Platform: WhatsApp Conversational Lottery & RMG SaaS

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Twilio](https://img.shields.io/badge/Twilio-WhatsApp_API-F22F46?style=for-the-badge&logo=twilio&logoColor=white)](https://twilio.com)
[![Meta Cloud API](https://img.shields.io/badge/Meta-Cloud_API_Ready-0668E1?style=for-the-badge&logo=meta&logoColor=white)](https://developers.facebook.com/docs/whatsapp/cloud-api)
[![Tests](https://img.shields.io/badge/Tests-Passing_(46_assertions)-10B981?style=for-the-badge&logo=githubactions&logoColor=white)](tests)

A high-concurrency, multi-tenant **B2B SaaS Middleware** that transforms traditional lottery and numbers games into an interactive, high-converting **WhatsApp conversational gaming experience**. 

Engineered for mobile-first African betting markets (Nigeria, Kenya, Ghana), the platform connects licensed lottery operators to WhatsApp via a driver-agnostic gateway, features an independent **cryptographic double-entry revenue ledger (35% Q4I / 65% Operator Split)**, and includes a live **Financial Reconciliation & Audit Dashboard**.

---

## 🏛️ System Architecture

```
                                  [ Player WhatsApp ]
                                          │
                        ┌─────────────────┴─────────────────┐
                        ▼                                   ▼
             [ Twilio WhatsApp API ]             [ Meta WhatsApp Cloud API ]
                        │                                   │
                        └─────────────────┬─────────────────┘
                                          │
                                          ▼
                            [ Webhook Signature Ingress ]
                                (X-Twilio / X-Hub HMAC)
                                          │
                                          ▼
                             [ Tenant Resolver Engine ]
                               (By Destination Phone)
                                          │
                                          ▼
                           [ Dynamic Menu Tree Machine ]
                              (Redis Cached Sessions)
                                    │         │
                 ┌──────────────────┘         └──────────────────┐
                 ▼                                               ▼
     [ Step-by-Step Flow ]                            [ Fast-Path Shortcuts ]
  • Keno 20/80 (Balls & Side Bets)                  • "KENO QP 300 1234"
  • Modern Billionaire (12 Games)                   • "590 QP 200 1234"
  • Ghana 5/90 (NLA Weekly)                         • "BAL" (Wallet Check)
  • Permutations (PERM1 - PERM5)                    • "RES" (Draw Results)
  • 1Banker & 1st Drawn                             • Chained digits ("1.1.1", "5.1")
                 │                                               │
                 └──────────────────┬────────────────────────────┘
                                    │
                                    ▼
                     [ Pluggable Operator Adapter ]
                    (LottoOperatorContract Interface)
                                    │
                         ┌──────────┴──────────┐
                         ▼                     ▼
               [ Modern Lottery API ]   [ Kenya / Ghana API ]
                         │
                         ▼
        [ Cryptographic Double-Entry Ledger ]
             • 35% Q4I Platform Commission
             • 65% Operator Game Pool
             • Immutable SHA-256 Chained Hash Blocks
                         │
                         ▼
           [ Executive Financial Portal ]
            (Live at /dashboard in NGN/KES)
```

---

## ✨ Key Features

### 1. Driver-Agnostic WhatsApp Gateway
* **Twilio WhatsApp Driver:** Ready for immediate deployment via Twilio Sandbox or approved WhatsApp Senders.
* **Direct Meta Cloud API Driver:** Pre-built and configured. Flip from Twilio to Meta Cloud API via a single environment variable (`WHATSAPP_DEFAULT_DRIVER=meta`) once Meta RMG licensing is whitelisted—**without modifying any bot logic**.

### 2. Dynamic Database-Driven Menu Tree
* Fully digitized from Modern Lottery's core menu specifications into relational tables (`ussd_menus` and `ussd_menu_items`).
* **Game Lineup Supported:**
  * **Keno 20/80 (Awoof):** Ball Selection (Pick 10 / Quick Pick) + 9 Side Games (`SUM BIG`, `SUM SMALL`, `SUM ODD`, `SUM EVEN`, and Elements: `GOLD (9.2x)`, `WOOD (4.6x)`, `WATER (2.4x)`, `FIRE (4.6x)`, `EARTH (9.2x)`).
  * **Modern Billionaire (12 Sub-pools):** *Powerball, Awoof, Biggest Bet, Gold Rush, Lucky Dollar, Blessing, Owo Time, Modern Bingo, Bonus cash, Hero, Golden Night, Queen*—each offering `PERM1`, `PERM2`, `PERM3`, `PERM4`, `PERM5`, `1BANKER`, `1ST DRAWN`.
  * **Modern Jackpot:** Full 7 Permutation/Banker bet options.
  * **Ghana 5/90 (NLA):** Monday Special, Lucky Tuesday, Mid-Week, Fortune Thursday, Friday Bonanza, National Weekly, Sunday Aseda.
  * **VAG Lotto & Noon Rush:** Full Permutation and Banker options.
* **Wallet & Non-Gaming Actions:**
  * **Check Balance:** Instant lookup of *Credit Balance*, *Masters Balance*, and *Bonus Balance*.
  * **Recharge:** Online checkout links + automated Bank Transfer accounts (Zenith Bank).
  * **Withdraw:** Managed bank card payout workflows.
  * **Contact Us:** Support website, direct WhatsApp, and helpline numbers.

### 3. Power-User Single-Line Bets & Chaining
* **Single-Message Bets:** Seasoned players can place bets in a single message without navigating menus:
  * `KENO QP 500 1234` $\rightarrow$ Places a ₦500 Keno Quick-Pick ticket with PIN 1234.
  * `KENO 7 14 21 28 35 42 49 56 63 70 200 1234` $\rightarrow$ Places specific numbers.
  * `590 QP 200 1234` $\rightarrow$ Places a ₦200 5/90 ticket.
* **Chained Quick Codes:** Entering `1.1.1` or `5.1` jumps directly to nested options.
* **Keyword Shortcuts:** `BAL` (balance inquiry) and `RES` (latest draw results).

### 4. Independent 35/65 Cryptographic Revenue Ledger
* Every bet calculates the agreed commercial split:
  $$\text{Stake} = \text{Q4I (35\%)} + \text{Modern Lottery (65\%)}$$
* Entries are written to an immutable double-entry ledger (`ledger_entries`) chained using SHA-256 hashes:
  $$\text{Hash}_n = \text{SHA256}(\text{TenantID} + \text{TicketID} + \text{Amount} + \text{Hash}_{n-1} + \text{Timestamp})$$
* Guarantees tamper-proof accounting so neither party can dispute turnover or commission figures.

### 5. Executive Financial Reconciliation Dashboard (`/dashboard`)
* Visualized metrics for:
  * **Total Turnover (Gross Stakes)**
  * **Total Processed Tickets**
  * **Q4I Accrued Commission (35%)**
  * **Operator Game Pool (65%)**
  * **Net Available to Spend / Settle**
  * **Meta Messaging Deductions Tracking**
  * **Cryptographic Hash Chain Seal Verification**
  * **Live WhatsApp Bet Stream Table**
* Multi-tenant dropdown to monitor multiple betting companies across countries from a single interface.

---

## 🚀 Quick Start Guide

### 1. Requirements
* PHP 8.2 or higher
* Composer 2.x
* SQLite (default) or MySQL 8.x
* Twilio Account or Meta Cloud API credentials

### 2. Local Setup
```bash
# Clone the repository
git clone https://github.com/alagaddonjuan/lotto.git
cd lotto

# Install PHP dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run database migrations and seed Modern Lottery
php artisan migrate
php artisan db:seed --class=ModernLotterySeeder
php artisan db:seed --class=ModernLotteryUssdMenuSeeder

# Run test suite
php artisan test
```

### 3. Launch Development Server
```bash
php artisan serve
```
Open **`http://localhost:8000/dashboard`** in your browser to view the live financial dashboard.

---

## ⚙️ Configuration (`.env`)

```env
APP_NAME="Q4I Lotto Platform"
APP_ENV=production
APP_URL=https://lotto.q4iltd.com

# Active WhatsApp Gateway Driver ("twilio" or "meta")
WHATSAPP_DEFAULT_DRIVER=twilio

# Twilio Ingress Configuration
TWILIO_AUTH_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
TWILIO_BYPASS_SIGNATURE=false

# Meta WhatsApp Cloud API (When RMG approved)
META_PHONE_NUMBER_ID=your_meta_phone_number_id
META_ACCESS_TOKEN=your_meta_system_user_token
META_APP_SECRET=your_meta_app_secret
META_VERIFY_TOKEN=q4i_lotto_meta_secret
META_BYPASS_SIGNATURE=false

# Modern Lottery Backend API
MODERN_LOTTERY_API_URL=https://api.modernlottery.ng/v1
MODERN_LOTTERY_API_KEY=mock_key
```

---

## 🌐 Webhook Ingress Endpoints

| Gateway | Method | Webhook URL | Description |
| :--- | :--- | :--- | :--- |
| **Twilio** | `POST` | `/api/webhook/twilio` | Receives incoming WhatsApp messages via Twilio |
| **Meta Cloud API** | `GET` | `/api/webhook/meta` | Handles Meta verification challenge (`hub.challenge`) |
| **Meta Cloud API** | `POST` | `/api/webhook/meta` | Receives WhatsApp payloads via Graph API |
| **Health Check** | `GET` | `/api/ping` | Uptime monitoring and server health |

---

## 🧪 Test Suite

The platform includes comprehensive automated tests covering:
* Root menu loading and nested tree traversal
* Quick Pick (QP) random generator
* Stake validation and 4-digit PIN authentication
* Bet ticket placement with UUIDv4 idempotency
* Double-entry ledger calculation and SHA-256 hash chaining
* Power-user single-line bet execution (`KENO QP 300 1234`)
* Chained digit navigation (`5*1`, `6`)
* Dashboard rendering and JSON statistics APIs

Run the tests at any time:
```bash
php artisan test
```

```text
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\DashboardTest
  ✓ dashboard renders successfully
  ✓ dashboard stats api returns accurate splits

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

   PASS  Tests\Feature\LottoBotEngineTest
  ✓ ussd menu tree navigation and bet placement
  ✓ deep ussd dial strings
  ✓ fast line bet shortcut

  Tests:    7 passed (46 assertions)
  Duration: 3.97s
```

---

## 🛡️ Security & Integrity Matrix

* **Webhook Tampering:** Validates `X-Twilio-Signature` and Meta's `X-Hub-Signature-256` HMAC signatures on every incoming request.
* **Double-Spend Prevention:** Bets require a unique `UUIDv4` idempotency key. Concurrent clicks within 10 seconds are rejected via distributed Redis locks.
* **Audit Assurance:** Every financial ledger entry is mathematically linked to the preceding block. Any tampering with historical rows breaks the hash chain instantly.
* **Zero Plaintext Credentials:** API keys and sensitive tokens are encrypted at rest using Laravel's AES-256-GCM encryption.

---

## 📄 Commercial Terms & Partnership

* **Platform Provider:** Q4I Limited
* **Licensed Operator:** Modern Lottery Limited (NLRC Licensed)
* **Revenue Share:** **35% Q4I Limited / 65% Modern Lottery** on all net turnover generated via the WhatsApp channel.
* **Meta Messaging Costs:** Borne directly by the Operator's Meta Business Account.

---

## 📜 License

Proprietary Software. Developed by **Michael Alaga (Q4I Limited)**. All Rights Reserved.
