<?php

namespace Modules\Notifications\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ (cẩn thận với foreign key)
        DB::table('notification_templates')->delete();

        // Tạo notification templates
        $templates = [
            // Task notifications
            [
                'name' => 'task_assigned',
                'title' => 'Bạn có công việc mới',
                'subject' => 'Công việc mới được giao',
                'email_template' => 'Xin chào {{user_name}}, bạn có công việc mới: {{task_title}}. Hạn hoàn thành: {{deadline}}',
                'sms_template' => 'CV mới: {{task_title}}. Hạn: {{deadline}}',
                'push_template' => 'Công việc mới: {{task_title}}',
                'in_app_template' => 'Bạn có công việc mới: {{task_title}}',
                'channels' => json_encode(['email', 'push', 'sms', 'in_app']),
                'priority' => 'medium',
                'category' => 'task',
                'description' => 'Thông báo khi có công việc mới được giao',
                'is_active' => true
            ],
            [
                'name' => 'task_reminder',
                'title' => 'Nhắc nhở công việc',
                'subject' => 'Nhắc nhở: Công việc sắp đến hạn',
                'email_template' => 'Xin chào {{user_name}}, công việc "{{task_title}}" sẽ đến hạn vào {{deadline}}. Vui lòng hoàn thành sớm.',
                'sms_template' => 'Nhắc nhở: {{task_title}} - hạn {{deadline}}',
                'push_template' => 'Nhắc nhở: {{task_title}}',
                'in_app_template' => 'Công việc "{{task_title}}" sẽ đến hạn vào {{deadline}}',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'high',
                'category' => 'task',
                'description' => 'Nhắc nhở công việc sắp đến hạn',
                'is_active' => true
            ],
            [
                'name' => 'task_completed',
                'title' => 'Công việc hoàn thành',
                'subject' => 'Công việc đã hoàn thành',
                'email_template' => 'Xin chào {{user_name}}, công việc "{{task_title}}" đã được hoàn thành bởi {{completed_by}}.',
                'sms_template' => 'CV hoàn thành: {{task_title}}',
                'push_template' => 'Công việc hoàn thành: {{task_title}}',
                'in_app_template' => 'Công việc "{{task_title}}" đã hoàn thành',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'low',
                'category' => 'task',
                'description' => 'Thông báo khi công việc hoàn thành',
                'is_active' => true
            ],

            // Library notifications
            [
                'name' => 'book_borrowed',
                'title' => 'Sách đã được mượn',
                'subject' => 'Xác nhận mượn sách',
                'email_template' => 'Xin chào {{user_name}}, bạn đã mượn sách "{{book_title}}" từ thư viện. Hạn trả: {{return_date}}',
                'sms_template' => 'Đã mượn: {{book_title}}. Hạn trả: {{return_date}}',
                'push_template' => 'Sách đã mượn: {{book_title}}',
                'in_app_template' => 'Bạn đã mượn sách "{{book_title}}" từ thư viện',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'medium',
                'category' => 'library',
                'description' => 'Thông báo khi mượn sách thành công',
                'is_active' => true
            ],
            [
                'name' => 'book_return_reminder',
                'title' => 'Nhắc nhở trả sách',
                'subject' => 'Nhắc nhở: Sách sắp đến hạn trả',
                'email_template' => 'Xin chào {{user_name}}, sách "{{book_title}}" sẽ đến hạn trả vào {{return_date}}. Vui lòng trả sách đúng hạn.',
                'sms_template' => 'Nhắc trả sách: {{book_title}} - hạn {{return_date}}',
                'push_template' => 'Nhắc nhở trả sách: {{book_title}}',
                'in_app_template' => 'Sách "{{book_title}}" sẽ đến hạn trả vào {{return_date}}',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'high',
                'category' => 'library',
                'description' => 'Nhắc nhở sách sắp đến hạn trả',
                'is_active' => true
            ],

            // System notifications
            [
                'name' => 'system_maintenance',
                'title' => 'Bảo trì hệ thống',
                'subject' => 'Thông báo bảo trì hệ thống',
                'email_template' => 'Xin chào {{user_name}}, hệ thống sẽ bảo trì từ {{start_time}} đến {{end_time}}. Vui lòng lưu công việc trước khi bảo trì.',
                'sms_template' => 'Bảo trì hệ thống: {{start_time}} - {{end_time}}',
                'push_template' => 'Bảo trì hệ thống từ {{start_time}}',
                'in_app_template' => 'Hệ thống sẽ bảo trì từ {{start_time}} đến {{end_time}}',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'critical',
                'category' => 'system',
                'description' => 'Thông báo bảo trì hệ thống',
                'is_active' => true
            ],
            [
                'name' => 'system_update',
                'title' => 'Cập nhật hệ thống',
                'subject' => 'Thông báo cập nhật hệ thống',
                'email_template' => 'Xin chào {{user_name}}, hệ thống đã được cập nhật lên phiên bản {{version}}. Các tính năng mới: {{new_features}}',
                'sms_template' => 'Hệ thống cập nhật v{{version}}',
                'push_template' => 'Hệ thống đã cập nhật v{{version}}',
                'in_app_template' => 'Hệ thống đã được cập nhật lên phiên bản {{version}}',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'medium',
                'category' => 'system',
                'description' => 'Thông báo cập nhật hệ thống',
                'is_active' => true
            ],

            // User notifications
            [
                'name' => 'user_registered',
                'title' => 'Tài khoản mới đã được tạo thành công',
                'subject' => 'Thông tin đăng nhập hệ thống',
                'email_template' => 'Xin chào {{user_name}},

Chào mừng bạn đến với hệ thống quản lý giáo dục!

Thông tin đăng nhập của bạn:
- Tài khoản: {{username}}
- Mật khẩu: {{password}}
- Email: {{user_email}}

Vui lòng đăng nhập và đổi mật khẩu để bảo mật tài khoản.

Trân trọng,
Đội ngũ hỗ trợ hệ thống',
                'sms_template' => 'TK: {{username}} - MK: {{password}}. Chào mừng {{user_name}} đến với hệ thống!',
                'push_template' => 'Tài khoản mới: {{username}} - Mật khẩu: {{password}}',
                'in_app_template' => 'Tài khoản mới đã được tạo:

Tài khoản: {{username}}
Mật khẩu: {{password}}
Email: {{user_email}}

Vui lòng đăng nhập và đổi mật khẩu!',
                'channels' => json_encode(['email', 'push', 'sms', 'in_app']),
                'priority' => 'high',
                'category' => 'user',
                'description' => 'Thông báo thông tin đăng nhập khi user đăng ký',
                'is_active' => true
            ],
            [
                'name' => 'password_reset',
                'title' => 'Đặt lại mật khẩu',
                'subject' => 'Yêu cầu đặt lại mật khẩu',
                'email_template' => 'Xin chào {{user_name}}, bạn đã yêu cầu đặt lại mật khẩu. Mã xác nhận: {{reset_code}}. Mã có hiệu lực trong 10 phút.',
                'sms_template' => 'Mã đặt lại MK: {{reset_code}}',
                'push_template' => 'Yêu cầu đặt lại mật khẩu',
                'in_app_template' => 'Bạn đã yêu cầu đặt lại mật khẩu. Mã xác nhận: {{reset_code}}',
                'channels' => json_encode(['email', 'sms', 'push', 'in_app']),
                'priority' => 'high',
                'category' => 'user',
                'description' => 'Thông báo khi user yêu cầu đặt lại mật khẩu',
                'is_active' => true
            ],
            [
                'name' => 'password_changed',
                'title' => 'Mật khẩu đã được thay đổi',
                'subject' => 'Xác nhận thay đổi mật khẩu',
                'email_template' => 'Xin chào {{user_name}},

Mật khẩu tài khoản {{username}} đã được thay đổi thành công.

Nếu bạn không thực hiện thay đổi này, vui lòng liên hệ ngay với đội ngũ hỗ trợ.

Trân trọng,
Đội ngũ bảo mật hệ thống',
                'sms_template' => 'MK {{username}} đã thay đổi. Liên hệ hỗ trợ nếu không phải bạn.',
                'push_template' => 'Mật khẩu {{username}} đã thay đổi',
                'in_app_template' => 'Mật khẩu tài khoản {{username}} đã được thay đổi thành công.

Nếu bạn không thực hiện thay đổi này, vui lòng liên hệ ngay với đội ngũ hỗ trợ.',
                'channels' => json_encode(['email', 'push', 'sms', 'in_app']),
                'priority' => 'high',
                'category' => 'user',
                'description' => 'Thông báo khi user thay đổi mật khẩu',
                'is_active' => true
            ]
        ];

        foreach ($templates as $template) {
            DB::table('notification_templates')->insert($template);
        }

        $this->command->info('Notification templates seeded successfully!');
    }
}
