<?php

namespace Modules\Notifications\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationsHtmlSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('notification_templates')->delete();

        $templates = [
            [
                'name' => 'student_account_created',
                'title' => 'Tài khoản sinh viên đã được tạo',
                'subject' => 'Thông tin đăng nhập sinh viên',
                'email_template' => <<<HTML
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>{{subject}}</title>
  </head>
  <body style="margin:0;background:#f5f7fb;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;">
      <tr>
        <td align="center" style="padding:24px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(20,20,43,.06);">
            <tr>
              <td style="padding:16px 20px;background:#111827;">
                <img src="{{logo_url}}" alt="Logo" width="120" style="display:block;">
              </td>
            </tr>
            <tr>
              <td><img src="{{banner_url}}" alt="Welcome Banner" width="600" style="width:100%;height:auto;display:block;"></td>
            </tr>
            <tr>
              <td style="padding:24px;">
                <h1 style="margin:0;font:700 22px/1.3 Arial;color:#111827;">Chào mừng {{name}} 🎉</h1>
                <p style="font:400 14px/1.7 Arial;color:#374151;">Tài khoản sinh viên của bạn đã được tạo thành công.</p>
                <p style="font:400 14px/1.7 Arial;color:#374151;">Tài khoản: <b>{{username}}</b><br>Mật khẩu: <b>{{password}}</b></p>
                <a href="{{login_url}}" style="display:inline-block;padding:12px 18px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;">Đăng nhập ngay</a>
              </td>
            </tr>
            <tr>
              <td style="padding:18px 24px;background:#fafafa;text-align:center;">
                <p style="font:400 12px/1.6 Arial;color:#6b7280;">© {{year}} {{app_name}}</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'sms_template' => 'TK Sinh viên: {{username}}, MK: {{password}}',
                'push_template' => 'Tài khoản sinh viên {{username}} đã được tạo',
                'in_app_template' => 'Tài khoản sinh viên {{username}} đã được tạo',
                'channels' => json_encode(['email', 'sms', 'push', 'in_app']),
                'priority' => 'high',
                'category' => 'user',
                'description' => 'Email tạo tài khoản sinh viên',
                'is_active' => true,
            ],

            [
                'name' => 'lecturer_account_created',
                'title' => 'Tài khoản giảng viên đã được tạo',
                'subject' => 'Thông tin đăng nhập giảng viên',
                'email_template' => <<<HTML
<!doctype html>
<html>
  <head><meta charset="utf-8"><title>{{subject}}</title></head>
  <body style="margin:0;background:#f9fafb;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;">
      <tr>
        <td align="center" style="padding:24px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 20px rgba(0,0,0,.05);">
            <tr>
              <td style="padding:16px;background:#0b3b2e;color:#fff;">
                <img src="{{logo_url}}" alt="Logo" width="120" style="display:block;">
              </td>
            </tr>
            <tr>
              <td><img src="{{banner_url}}" alt="Lecturer Banner" width="600" style="width:100%;display:block;"></td>
            </tr>
            <tr>
              <td style="padding:24px;">
                <h2 style="margin:0;font:600 20px Arial;color:#0b3b2e;">Xin chào {{name}}</h2>
                <p style="font:400 14px/1.7 Arial;color:#374151;">Tài khoản giảng viên của bạn đã được tạo.</p>
                <p style="font:400 14px/1.7 Arial;color:#374151;">Tài khoản: <b>{{username}}</b><br>Mật khẩu: <b>{{password}}</b></p>
                <a href="{{login_url}}" style="display:inline-block;padding:10px 16px;background:#059669;color:#fff;border-radius:8px;text-decoration:none;">Đăng nhập</a>
              </td>
            </tr>
            <tr>
              <td style="padding:16px;background:#f3f4f6;text-align:center;">
                <p style="font:400 12px Arial;color:#6b7280;">© {{year}} {{app_name}}</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'sms_template' => 'TK GV: {{username}}, MK: {{password}}',
                'push_template' => 'Tài khoản giảng viên {{username}} đã được tạo',
                'in_app_template' => 'Tài khoản giảng viên {{username}} đã được tạo',
                'channels' => json_encode(['email', 'sms', 'push']),
                'priority' => 'high',
                'category' => 'user',
                'description' => 'Email tạo tài khoản giảng viên',
                'is_active' => true,
            ],

            [
                'name' => 'system_maintenance_html',
                'title' => 'Thông báo bảo trì hệ thống',
                'subject' => 'Bảo trì hệ thống',
                'email_template' => <<<HTML
<!doctype html>
<html>
  <head><meta charset="utf-8"><title>{{subject}}</title></head>
  <body style="margin:0;background:#f3f4f6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
      <tr>
        <td align="center" style="padding:20px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.05);">
            <tr>
              <td style="padding:20px;background:#1e3a8a;color:#fff;">
                <h2 style="margin:0;font:600 20px Arial;">Thông báo bảo trì</h2>
              </td>
            </tr>
            <tr>
              <td style="padding:24px;">
                <p style="font:400 14px Arial;color:#111827;">Xin chào {{user_name}},</p>
                <p style="font:400 14px Arial;color:#374151;">
                  Hệ thống sẽ được bảo trì từ <b>{{start_time}}</b> đến <b>{{end_time}}</b>.
                </p>
                <p style="font:400 14px Arial;color:#374151;">
                  Trong thời gian này, bạn có thể không truy cập được hệ thống. Vui lòng sắp xếp công việc phù hợp.
                </p>
                <p style="font:400 12px Arial;color:#6b7280;">Cảm ơn bạn đã thông cảm,<br>Đội ngũ {{app_name}}</p>
              </td>
            </tr>
            <tr>
              <td style="padding:16px;background:#f9fafb;text-align:center;">
                <p style="font:400 12px Arial;color:#6b7280;">© {{year}} {{app_name}}</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'sms_template' => 'Bảo trì hệ thống: {{start_time}} - {{end_time}}',
                'push_template' => 'Hệ thống sẽ bảo trì từ {{start_time}}',
                'in_app_template' => 'Hệ thống sẽ bảo trì từ {{start_time}} đến {{end_time}}',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'critical',
                'category' => 'system',
                'description' => 'Email thông báo bảo trì có HTML đẹp',
                'is_active' => true,
            ]
        ];

        foreach ($templates as $tpl) {
            DB::table('notification_templates')->insert($tpl);
        }

        $this->command->info('Notification HTML templates seeded!');
    }
}
