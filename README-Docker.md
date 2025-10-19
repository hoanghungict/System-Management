# Khởi động tất cả services
sudo docker-compose up --build -d

# Xem logs
docker-compose logs -f

# Dừng tất cả services
docker-compose down

# Dừng và xóa volumes
docker-compose down -v
```

# Clear cache
docker-compose exec backend php artisan cache:clear

# Generate key
docker-compose exec backend php artisan key:generate

# View queue jobs
docker-compose exec backend php artisan queue:monitor

# Clear failed jobs
docker-compose exec backend php artisan queue:flush
```

### Queue Management
```bash
vào sql
 sudo mysql -u root
   use system_service
   
bật dockerd demon
sudo dockerd &
sudo dockerd --host=unix:///var/run/docker.sock --host=tcp://0.0.0.0:2376
dừng dockerd demon
sudo pkill dockerd
# Xem queue logs
docker-compose logs -f queue-worker

# Restart queue worker
docker-compose restart queue-worker

# Check queue status
docker-compose exec backend php artisan queue:work --once
```

### Redis Management
```bash
# Truy cập Redis CLI
docker-compose exec redis redis-cli

# Monitor Redis
docker-compose exec redis redis-cli monitor

### Port Conflicts
Nếu có lỗi port đã được sử dụng, thay đổi ports trong `docker-compose.yml`:

```yaml
ports:
  - "8080:80"  # Backend
  - "6380:6379"  # Redis
```

### Database Connection Issues
```bash
# Kiểm tra kết nối database
docker-compose exec backend php artisan tinker --execute="echo 'DB: ' . (DB::connection()->getPdo() ? 'Connected' : 'Failed');"

# Kiểm tra Laragon MySQL đang chạy
netstat -an | findstr :3306
```

### Permission Issues
```bash
# Fix Laravel storage permissions
docker-compose exec backend chmod -R 775 storage bootstrap/cache
```

### Queue Issues
```bash
# Check queue status
docker-compose exec backend php artisan queue:work --once

# Clear failed jobs
docker-compose exec backend php artisan queue:flush

# Restart queue worker
docker-compose restart queue-worker
```

## 🛠️ Production Deployment

Để deploy production, cần:

1. Thay đổi `APP_ENV=production` trong environment
2. Disable debug mode: `APP_DEBUG=false`
3. Sử dụng production database credentials
4. Configure SSL certificates
5. Set up proper logging
6. Optimize Redis configuration
7. Set up monitoring for queues

## 📊 Monitoring

```bash
# Xem resource usage
docker stats

# Xem logs của specific service
docker-compose logs -f backend
docker-compose logs -f queue-worker
docker-compose logs -f redis

# Health check
curl tihttp://localhost/health
