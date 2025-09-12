# Claude.md - Performance Recorder Setup Guide

## Overview
This document provides instructions for the automated performance recording system for the "Buy Now" button on variable products on the homepage.

## Quick Start

### 1. Install Dependencies
```bash
npm install
npx playwright install
```

### 2. Run Performance Recording
After pushing changes and waiting for deployment:
```bash
npm run record:buy-now-variable
```

This will:
- Check deployment status via `wp-content/deploy.txt`
- Run automated browser tests
- Save artifacts to `perf/artifacts/<timestamp>/`

## Architecture

### Components
1. **Recorder Script** (`tools/recorder/record-buy-now-variable.js`)
   - Uses Playwright to automate browser interactions
   - Records HAR files, console logs, performance traces
   - Captures event listeners using CDP

2. **Runner Script** (`tools/recorder/runner.js`)
   - Verifies deployment by checking commit SHA
   - Polls `https://vidieu.vn/wp-content/deploy.txt`
   - Executes recorder after deployment confirmation

3. **CI/CD Integration**
   - GitHub Actions uploads `deploy.txt` after FTP deployment
   - Contains HEAD commit SHA for verification

### Artifacts Generated
- `home-buy-now-variable.har` - Network activity
- `home-buy-now-variable.console.txt` - Console logs
- `home-buy-now-variable.trace.zip` - Performance trace
- `home.event_listeners.*.json` - Event listener dumps

## Workflow

1. **Development**
   - Make changes to code
   - Commit to feature branch
   - Push to GitHub

2. **Deployment**
   - CI/CD deploys via FTP
   - Creates `wp-content/deploy.txt` with commit SHA

3. **Testing**
   - Run `npm run record:buy-now-variable`
   - Script waits for deployment confirmation
   - Executes automated tests
   - Saves artifacts locally

## Test Scenario
1. Open https://vidieu.vn/ (desktop viewport)
2. Find variable product card
3. Click eye icon (`.pe-7s-look`)
4. Select attributes in inline panel
5. Wait for "Buy Now" button to activate
6. Capture event listeners
7. Click "Buy Now" and wait for checkout redirect

## Troubleshooting

### Deploy marker not found
If `deploy.txt` is not available, the script will wait 30 seconds before proceeding.

### Test failures
Check console output and artifacts in `perf/artifacts/` for debugging information.

## Kill Switch
To disable the recorder without removing code, set environment variable:
```bash
DISABLE_PERF_RECORDER=true
4) Runner – logic chờ deploy

Đọc HEAD SHA (từ git rev-parse HEAD).

Poll https://vidieu.vn/wp-content/deploy.txt mỗi 10s, tối đa 3 phút. Khi deploy.txt == HEAD → coi như deploy thành công.

Nếu không có marker, sleep 30s rồi chạy recorder (fallback).

5) Recorder – kịch bản thao tác

Mở https://vidieu.vn/ (viewport desktop).

Tìm thẻ sản phẩm có biến thể; click icon quickview .pe-7s-look trong card đó.

Trong .nasa-product-content-select-wrap:

Lặp mỗi .nasa-product-content-child → click option đầu tiên .nasa-attr-ux-item.enabled.

Chờ nút .vd-buy-now-button.vd-buy-now-variable[data-variation-selected="true"].

Trước khi click:

Dùng CDP DOMDebugger.getEventListeners dump listeners của:

document

document.body

chính nút "Mua Ngay"

Bật ghi HAR/trace; lắng nghe console.

Click "Mua Ngay"; chờ điều hướng tới /checkout/ (timeout 10s).

Xuất artefact vào perf/artifacts/<timestamp>/:

home-buy-now-variable.har

home-buy-now-variable.console.txt

home-buy-now-variable.trace.zip (hoặc .webm)

home.event_listeners.document.json

home.event_listeners.body.json

home.event_listeners.buy_now_btn.json

6) Cách chạy
# Cài deps (lần đầu)
npm i
npx playwright install

# Sau khi push/merge và CI deploy xong
npm run record:buy-now-variable

7) Đầu ra & Nơi xem

Artefact: perf/artifacts/<YYYYMMDD-HHMMSS>/...

Console sẽ in:

Tổng số request trong HAR

URL cuối (kỳ vọng: /checkout/)

Thời điểm click

8) An toàn & không ảnh hưởng hệ thống

Không chỉnh sửa logic frontend/ admin.

Không public secret; marker deploy.txt không nhạy cảm.

Nếu cần thêm asset frontend, phải có cờ tắt (VIDIEU_PERF_RECORDER=false) và chỉ load ở môi trường staging.

9) Tiêu chí hoàn thành Pha A

Thu thập đủ artefact; có thể tái hiện hiện tượng “khựng + auto-scroll + redirect”.

Không phát sinh lỗi JS mới do recorder.

Ready cho Phase B–E (phân tích event timeline & RCA).

10) Rollback

Xoá tools/recorder/ và scripts liên quan trong package.json.

Bỏ bước upload deploy.txt trong CI nếu không dùng nữa.

Không động chạm code core/theme.


---

## Gợi ý nhỏ
- **Chờ 30 giây** là tạm được, nhưng **đối sánh SHA qua `deploy.txt` chuẩn hơn** (CI chậm/nhanh biến động). Trong prompt mình đã yêu cầu cả 2: ưu tiên SHA, fallback 30s.
- Nếu bạn muốn mình viết **prompt FIX** (sau khi có artefact) hoặc bổ sung **template báo cáo RCA.md