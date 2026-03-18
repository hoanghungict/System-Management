---
description: Đẩy code FE (fe-portal) và BE (System-Management) lên git và Trigger Deploy Production (hpc_root)
---
Theo chuẩn **[Cách 2: Không cần cài lại]** của tài liệu hệ thống điện tử HPC, để cập nhật thay đổi của FE và BE lên Production, quy trình gồm 3 giai đoạn:
1. Push code ở các repo service (frontend, backend) riêng lẻ.
2. Cập nhật code mới ở thư mục submodule tương ứng bên trong `hpc_root`.
3. Commit và Push thay đổi của từ `hpc_root` để kích hoạt quá trình tự động Deploy trên server.

> **Lưu ý:** Bạn có thể thay đổi lại nội dung của `git commit -m "..."` trong Bước 1 và 2 ở dưới cho phù hợp với nội dung làm việc thực tế của bạn trước khi duyệt chạy lệnh nhé.

### 1. Push code Frontend (fe-portal)
Chuyển đến repo `fe-portal` hiện tại, commit và đẩy những thay đổi.

```powershell
Set-Location -Path "e:\fe-portal"
git add .
git commit -m "Cập nhật frontend"
git push origin main
```

### 2. Push code Backend (System-Management)
Chuyển đến repo `System-Management` hiện tại, commit và đẩy những thay đổi.

```powershell
Set-Location -Path "\\wsl.localhost\Ubuntu\home\anhduong\projects\System-Management\System-Management"
git add .
git commit -m "Cập nhật logic backend"
git push origin main
```

### 3. Cập nhật các service bên trong hpc_root
Di chuyển vào các thư mục service tương ứng bên trong `hpc_root` để lấy source code mới nhất đã được push từ Bước 1 và 2.

```powershell
# Cập nhật fe-portal
Set-Location -Path "\\wsl.localhost\Ubuntu\home\anhduong\projects\hpc_root\fe-portal"
git switch main -q 2>$null; if (-not $?) { git switch master -q }
git pull origin $(git branch --show-current)

# Nếu xảy ra conflict trong fe-portal, lệnh merge dưới đây sẽ abort để bạn giải quyết bằng tay.
if ($LASTEXITCODE -ne 0) {
    Write-Host "Phát hiện conflict hoặc lỗi pull trong fe-portal. Aborting merge." -ForegroundColor Red
    git merge --abort
    exit 1
}

# Cập nhật System-Management
Set-Location -Path "\\wsl.localhost\Ubuntu\home\anhduong\projects\hpc_root\System-Management"
git switch main -q 2>$null; if (-not $?) { git switch master -q }
git pull origin $(git branch --show-current)

# Nếu xảy ra conflict trong System-Management, lệnh merge dưới đây sẽ abort để bạn giải quyết bằng tay.
if ($LASTEXITCODE -ne 0) {
    Write-Host "Phát hiện conflict hoặc lỗi pull trong System-Management. Aborting merge." -ForegroundColor Red
    git merge --abort
    exit 1
}
```

### 4. Đẩy hpc_root lên server Production
Trở lại thư mục gốc `hpc_root`, cập nhật `fe-portal` và `System-Management` để báo cho Server biết có code mới cần tự động deploy.

// turbo
```powershell
Set-Location -Path "\\wsl.localhost\Ubuntu\home\anhduong\projects\hpc_root"
git add fe-portal System-Management
git commit -m "feat: release update for fe-portal and System-Management"
git push origin main
```
