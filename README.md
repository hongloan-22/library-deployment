
# Library Deployment with Docker Compose

## 1. Overview

Ứng dụng quản lý thư viện đơn giản, sử dụng PHP, Apache và MySQL.
Toàn bộ hệ thống được triển khai bằng Docker Compose trên Windows.

## 2. Technologies

- PHP 8.3 và Apache
- MySQL 8.4
- phpMyAdmin
- Docker Desktop và Docker Compose
- Nginx reverse proxy (triển khai ở bước tiếp theo)

## 3. Requirements

- Windows 10
- Docker Desktop đang chạy
- Docker Compose

## 4. Start the application

Mở PowerShell tại thư mục dự án:

```powershell
docker compose up -d --build
docker compose ps
```

## 5. Current access URLs

Trước khi cấu hình Nginx:
- Website: http://localhost:8080
- phpMyAdmin: http://localhost:8081

Sau khi bổ sung Nginx:
- Website HTTPS: https://localhost:8443
- phpMyAdmin: http://localhost:8081

Chứng chỉ HTTPS được tạo tự ký để thực hành.
Trình duyệt có thể hiển thị cảnh báo chứng chỉ chưa được tin cậy.

## 6. Stop the application

```powershell
docker compose stop
```

Để dừng và xóa các container, nhưng giữ volume database:

```powershell
docker compose down
```

Không sử dụng `docker compose down -v` nếu muốn giữ dữ liệu MySQL.
