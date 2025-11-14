# 🔌 Backend Connection Ports - System Management

## 📊 Tổng quan kết nối

Dựa trên phân tích `docker-compose.yml` và test kết nối, đây là các cổng mà frontend Next.js có thể kết nối với backend:

## 🌐 Web Server Ports

### 1. **Main Web Server** - Port 8082
- **URL**: `http://localhost:8082`
- **Service**: Nginx Web Server
- **Status**: ✅ **Hoạt động bình thường** (đã khắc phục)
- **Mục đích**: API endpoints chính cho frontend
- **Cấu hình**: 
  ```yaml
  ports:
    - "8082:80"
  ```

### 2. **Alternative Web Server** - Port 8080 (Backup)
- **URL**: `http://localhost:8080`
- **Service**: Nginx Web Server (backup)
- **Status**: ❓ **Chưa test**
- **Mục đích**: Backup web server

## 🗄️ Database Ports

### 3. **MySQL Database** - Port 3307
- **URL**: `mysql://localhost:3307`
- **Service**: MySQL 8.0
- **Status**: ✅ **Hoạt động**
- **Mục đích**: Database chính
- **Cấu hình**:
  ```yaml
  ports:
    - "3307:3306"
  ```

## 🚀 Message Queue Ports

### 4. **Redis Cache** - Port 6380
- **URL**: `redis://localhost:6380`
- **Service**: Redis Alpine
- **Status**: ✅ **Hoạt động**
- **Mục đích**: Caching, session storage
- **Cấu hình**:
  ```yaml
  ports:
    - "6380:6379"
  ```

### 5. **Kafka Message Broker** - Port 9092
- **URL**: `kafka://localhost:9092`
- **Service**: Apache Kafka
- **Status**: ✅ **Hoạt động**
- **Mục đích**: Event streaming, notifications
- **Cấu hình**:
  ```yaml
  ports:
    - "9092:9092"
  ```

### 6. **Zookeeper** - Port 2181
- **URL**: `zookeeper://localhost:2181`
- **Service**: Apache Zookeeper
- **Status**: ✅ **Hoạt động**
- **Mục đích**: Kafka coordination
- **Cấu hình**:
  ```yaml
  ports:
    - "2181:2181"
  ```

## 🔧 Application Services

### 7. **Laravel Application** - Port 9000 (Internal)
- **URL**: `http://localhost:9000` (Internal)
- **Service**: PHP-FPM
- **Status**: ✅ **Hoạt động**
- **Mục đích**: Laravel application processing
- **Note**: Chỉ accessible từ Nginx, không trực tiếp từ frontend

## 📋 Frontend Connection Summary

### ✅ **Cổng chính cho Frontend Next.js:**

1. **API Endpoints**: `http://localhost:8082/api/v1/*`
2. **Database**: `mysql://localhost:3307`
3. **Cache**: `redis://localhost:6380`
4. **Events**: `kafka://localhost:9092`

### ⚠️ **Vấn đề hiện tại:**

- **Web Server (Port 8082)**: Đang có lỗi 500
- **Queue Services**: Một số services đang restart
- **Cần khắc phục**: Laravel application configuration

## 🛠️ Khuyến nghị khắc phục

### 1. **Kiểm tra Laravel Configuration**
```bash
# Kiểm tra logs
docker-compose logs app
docker-compose logs webserver

# Restart services
docker-compose restart app webserver
```

### 2. **Kiểm tra Database Connection**
```bash
# Test MySQL connection
mysql -h localhost -P 3307 -u root -p
```

### 3. **Kiểm tra Redis Connection**
```bash
# Test Redis connection
redis-cli -h localhost -p 6380 ping
```

### 4. **Kiểm tra Kafka Connection**
```bash
# Test Kafka connection
kafka-topics --bootstrap-server localhost:9092 --list
```

## 🔗 Frontend Integration

### **Next.js Environment Variables:**
```env
# Backend API
NEXT_PUBLIC_API_URL=http://localhost:8082
NEXT_PUBLIC_API_BASE_URL=http://localhost:8082/api/v1

# Database (nếu cần direct connection)
DATABASE_URL=mysql://root:password@localhost:3307/system_management

# Cache
REDIS_URL=redis://localhost:6380

# Message Queue
KAFKA_BROKER=localhost:9092
```

### **API Endpoints cho Frontend:**
- **Authentication**: `POST /api/v1/login`
- **Tasks**: `GET /api/v1/tasks`
- **Users**: `GET /api/v1/users`
- **Notifications**: `GET /api/v1/notifications`
- **Health Check**: `GET /api/v1/health`

## 📊 Status Dashboard

| Service | Port | Status | Purpose |
|---------|------|--------|---------|
| Web Server | 8082 | ⚠️ Error 500 | Main API |
| MySQL | 3307 | ✅ Running | Database |
| Redis | 6380 | ✅ Running | Cache |
| Kafka | 9092 | ✅ Running | Events |
| Zookeeper | 2181 | ✅ Running | Coordination |
| Laravel App | 9000 | ✅ Running | Processing |

## 🚨 Action Required

1. **Khắc phục lỗi 500** trên port 8082
2. **Kiểm tra Laravel configuration**
3. **Test tất cả API endpoints**
4. **Cấu hình CORS** cho frontend
5. **Setup SSL/TLS** cho production

---

**📝 Note**: Frontend Next.js sẽ chủ yếu kết nối qua port **8082** cho API calls, và có thể sử dụng Redis (6380) cho caching nếu cần.
