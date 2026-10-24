
# Hệ thống quản lý thư viện triển khai bằng Docker Compose

## 1. Tổng quan

Dự án triển khai ứng dụng quản lý thư viện sử dụng PHP, Apache và MySQL trên Windows thông qua Docker Desktop và Docker Compose.

Hệ thống bao gồm ứng dụng web, cơ sở dữ liệu, reverse proxy HTTPS, công cụ quản trị database, giám sát metrics và thu thập log tập trung.

## 2. Kiến trúc và công nghệ

- **Ứng dụng:** PHP 8.3, Apache.
- **Cơ sở dữ liệu:** MySQL 8.4.
- **Quản trị database:** phpMyAdmin.
- **Reverse proxy:** Nginx.
- **Bảo mật kết nối:** HTTPS với chứng chỉ tự ký phục vụ thực hành.
- **Giám sát metrics:** Prometheus, cAdvisor, Nginx Prometheus Exporter và MySQL Exporter.
- **Dashboard:** Grafana.
- **Giám sát log:** Loki và Promtail.
- **Môi trường triển khai:** Windows 10, Docker Desktop, Docker Compose.
- **Quản lý mã nguồn:** Git và GitHub.

Các dịch vụ giao tiếp thông qua các Docker network được phân tách theo chức năng. Dữ liệu MySQL và dữ liệu giám sát được lưu trong các Docker volume.

## 3. Yêu cầu môi trường

- Windows 10.
- Docker Desktop đã cài đặt và đang chạy.
- Git.
- Kết nối Internet để tải các Docker image khi triển khai lần đầu.

## 4. Tải mã nguồn

Mở PowerShell và chạy:

```powershell
git clone https://github.com/hongloan-22/library-deployment.git
cd library-deployment
```

## 5. Cấu hình môi trường

Tạo tệp `.env` từ tệp mẫu:

```powershell
Copy-Item .env.example .env
notepad .env
```

Thay các giá trị mẫu bằng mật khẩu mạnh của riêng bạn:

```dotenv
MYSQL_ROOT_PASSWORD=your_strong_root_password
MYSQL_DATABASE=library_db
MYSQL_USER=library_user
MYSQL_PASSWORD=your_strong_app_password
GRAFANA_ADMIN_PASSWORD=your_strong_grafana_password
```

Không sử dụng nguyên các giá trị mẫu trong môi trường thực tế. Không commit hoặc push tệp `.env` lên GitHub.

### Cấu hình MySQL Exporter

Tạo tệp cấu hình cục bộ:

```powershell
notepad .\monitoring\mysql-exporter.my.cnf
```

Nội dung mẫu:

```ini
[client]
user=YOUR_MONITORING_USER
password=YOUR_MONITORING_PASSWORD
```

Thay các giá trị mẫu bằng thông tin của tài khoản monitoring MySQL đã được cấu hình và cấp đủ quyền cần thiết cho MySQL Exporter. Không đưa mật khẩu thực tế vào GitHub.

### Chuẩn bị chứng chỉ HTTPS

Nginx yêu cầu hai tệp cục bộ:

- `nginx/certs/server.crt`
- `nginx/certs/server.key`

Nếu bạn đã có hai tệp này, có thể bỏ qua bước tạo chứng chỉ.

Nếu triển khai trên máy mới, tạo thư mục và chứng chỉ tự ký bằng Docker:

```powershell
New-Item -ItemType Directory -Force .\nginx\certs | Out-Null

docker run --rm -v "$((Get-Location).Path -replace '\\','/')/nginx/certs:/certs" alpine:3.22 sh -c "apk add --no-cache openssl >/dev/null && openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /certs/server.key -out /certs/server.crt -subj '/CN=localhost'"
```

Chứng chỉ tự ký chỉ dùng cho thực hành. Trình duyệt có thể cảnh báo rằng chứng chỉ chưa được tin cậy.

## 6. Khởi chạy hệ thống

Tại thư mục gốc của dự án, chạy:

```powershell
docker compose config --quiet
docker compose up -d --build
docker compose ps
```

Kiểm tra trạng thái container. Các dịch vụ cần thiết phải ở trạng thái `Up`; MySQL cần đạt trạng thái `healthy`.

Xem log khi cần kiểm tra lỗi:

```powershell
docker compose logs --tail=80
```

## 7. Các địa chỉ truy cập

| Dịch vụ | Địa chỉ |
|---|---|
| Website HTTP, tự chuyển hướng HTTPS | http://localhost:8082 |
| Website HTTPS | https://localhost:8443 |
| phpMyAdmin | http://localhost:8081 |
| Prometheus | http://localhost:9090 |
| Grafana | http://localhost:3000 |
| Loki readiness | http://localhost:3100/ready |

Grafana sử dụng tài khoản quản trị `admin`; mật khẩu được đặt bằng biến `GRAFANA_ADMIN_PASSWORD` trong `.env`.

MySQL không được ánh xạ cổng trực tiếp ra Windows. Các cổng web, quản trị và monitoring được cấu hình chỉ lắng nghe trên `127.0.0.1`.

## 8. Cấu hình Grafana

Sau khi mở Grafana, cấu hình các nguồn dữ liệu nếu chưa có:

### Prometheus

- Data source: Prometheus
- URL: `http://prometheus:9090`

### Loki

- Data source: Loki
- URL: `http://loki:3100`

Lưu và kiểm tra kết nối của từng nguồn dữ liệu.

### Dashboard monitoring

Tệp dashboard metrics:

`monitoring/library-system-dashboard.json`

Dashboard theo dõi các thông số như CPU, RAM, kết nối Nginx, trạng thái MySQL và Prometheus targets.

### Dashboard log

Tệp dashboard log:

`monitoring/library-system-logs.json`

Dashboard phục vụ tra cứu log của các Docker container, lọc lỗi HTTP 5xx của Nginx và tìm thông điệp lỗi của ứng dụng.

Để nhập dashboard trên máy mới:

1. Mở Grafana.
2. Chọn **Dashboards → New → Import**.
3. Chọn tệp JSON tương ứng trong thư mục `monitoring`.
4. Chọn data source phù hợp nếu Grafana yêu cầu.
5. Hoàn tất nhập và kiểm tra dashboard.

## 9. Truy vấn log mẫu

Trong Grafana, mở **Explore**, chọn data source Loki và nhập các truy vấn LogQL sau.

### Log Nginx

```logql
{container="library-nginx"}
```

### Log ứng dụng PHP

```logql
{container="library-web"}
```

### Lọc log HTTP 4xx và 5xx của ứng dụng

```logql
{container="library-web"} |~ " [45][0-9]{2} "
```

### Lọc lỗi HTTP 5xx của Nginx

```logql
{container="library-nginx"} |~ " 5[0-9]{2} "
```

### Tìm thông điệp lỗi trong log ứng dụng

```logql
{container="library-web"} |~ "(?i)(error|exception|failed)"
```

Nếu không có kết quả, có thể chưa phát sinh log khớp với điều kiện truy vấn trong khoảng thời gian đã chọn.

## 10. Dừng hệ thống

Dừng các container nhưng giữ nguyên container:

```powershell
docker compose stop
```

Dừng và xóa các container, nhưng giữ lại dữ liệu trong Docker volume:

```powershell
docker compose down
```

Không sử dụng `docker compose down -v` nếu cần giữ dữ liệu MySQL, Prometheus, Grafana và Loki.

Để khởi động lại:

```powershell
docker compose up -d
```

## 11. Lưu ý bảo mật

- Không commit `.env`, mật khẩu MySQL Exporter, chứng chỉ riêng hoặc các tệp bí mật.
- Dùng `.env.example` để cung cấp danh sách biến môi trường cần thiết.
- Chỉ mở các cổng dịch vụ cần thiết; MySQL không được công khai cổng trực tiếp ra Windows.
- Giữ nguyên các Docker volume nếu muốn bảo toàn dữ liệu.
- Chứng chỉ tự ký chỉ thích hợp cho môi trường thực hành, không thay thế chứng chỉ tin cậy khi triển khai công khai.
- Cần tiếp tục rà soát quyền người dùng của container, Docker network, Security Headers và các biện pháp hardening trước khi triển khai thực tế.
