API thì tôi có viết hướng dẫn trong Document_Api rồi nhes
-   Đối với các bạn clone về cần làm các bước sau để chạy được nhé

1. Chạy Composer install (để cài các thư viện)
2. Tạo 1 file mới .env và copy file .env.example vào .env
3. Chạy php artisan key:generate (để tạo key cho project)
4. Ở phần cuối của .env tìm đến trường JWT_SECRET và thay bằng 1 chuỗi ngẫu nhiên
5. Chạy php artisan migrate (để tạo database)
6. Chạy php artisan db:seed (để tạo dữ liệu mẫu nó sẽ ra 1 tài khoan admin để dễ test (username : admin , pass : 123456))
7. Chạy php artisan serve (để chạy project)

-   Đối với các bạn muốn chạy thử thêm chức năng Notification thì cần chạy server redis trên docker hoặc 1 môi trường khác tùy và kết nối vào đây
-   Rồi chạy php artisan queue:work --queue=emails (tôi mới tạo Job cho email thôi)(Đây là hàng đợi Queue để gửi mail)
