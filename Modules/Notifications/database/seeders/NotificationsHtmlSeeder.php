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
        <meta charset="utf-8" />
        <title>{{subject}}</title>
    </head>
    <body style="margin: 0; background: #f5f7fb">
        <table
            role="presentation"
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="background: #f5f7fb"
        >
            <tr>
                <td align="center" style="padding: 24px">
                    <table
                        role="presentation"
                        width="100%"
                        cellpadding="0"
                        cellspacing="0"
                        style="
                            max-width: 600px;
                            background: #ffffff;
                            border-radius: 16px;
                            overflow: hidden;
                            box-shadow: 0 8px 24px rgba(20, 20, 43, 0.06);
                        "
                    >
                        <!-- Header -->
                        <tr>
                            <td style="padding: 16px 20px; background: #111827">
                                <img
                                    src="{{logo_url}}"
                                    alt="Logo"
                                    width="120"
                                    style="display: block"
                                />
                            </td>
                        </tr>

                        <!-- Banner -->
                        <tr>
                            <td>
                                <img
                                    src="{{banner_url}}"
                                    alt="Welcome Banner"
                                    width="600"
                                    style="
                                        width: 100%;
                                        height: auto;
                                        display: block;
                                    "
                                />
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding: 32px 24px">
                                <h1
                                    style="
                                        margin: 0 0 12px 0;
                                        font: 700 24px/1.3 Arial, sans-serif;
                                        color: #111827;
                                    "
                                >
                                    🎉 Chào mừng {{name}} đến với {{app_name}}!
                                </h1>
                                <p
                                    style="
                                        font: 400 15px/1.7 Arial, sans-serif;
                                        color: #374151;
                                        margin: 0 0 16px 0;
                                    "
                                >
                                    Tài khoản sinh viên của bạn đã được tạo thành công. 
                                    Từ bây giờ, bạn có thể truy cập hệ thống để bắt đầu trải nghiệm
                                    các tính năng học tập trực tuyến hiện đại.
                                </p>

                                <p
                                    style="
                                        font: 400 14px/1.7 Arial, sans-serif;
                                        color: #374151;
                                        background: #f9fafb;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                        padding: 12px 16px;
                                        margin-bottom: 20px;
                                    "
                                >
                                    <b>Thông tin đăng nhập của bạn:</b><br />
                                    📧 Tài khoản: <b>{{username}}</b><br />
                                    🔑 Mật khẩu: <b>{{password}}</b>
                                </p>

                                <a
                                    href="{{login_url}}"
                                    style="
                                        display: inline-block;
                                        padding: 14px 22px;
                                        background: #2563eb;
                                        color: #fff;
                                        font: 600 15px Arial, sans-serif;
                                        border-radius: 10px;
                                        text-decoration: none;
                                        margin-bottom: 24px;
                                    "
                                    >🚀 Đăng nhập ngay</a
                                >

                                <p
                                    style="
                                        font: 400 13px/1.7 Arial, sans-serif;
                                        color: #6b7280;
                                        margin-top: 16px;
                                    "
                                >
                                    👉 Sau khi đăng nhập, bạn có thể:
                                    <br />• Cập nhật thông tin cá nhân.
                                    <br />• Truy cập thời khóa biểu và học liệu.
                                    <br />• Theo dõi kết quả học tập theo thời gian thực.
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td
                                style="
                                    padding: 18px 24px;
                                    background: #fafafa;
                                    text-align: center;
                                "
                            >
                                <p
                                    style="
                                        font: 400 12px/1.6 Arial, sans-serif;
                                        color: #6b7280;
                                    "
                                >
                                    © {{year}} {{app_name}}. Mọi quyền được bảo lưu.<br />
                                    Đây là email tự động, vui lòng không trả lời.
                                </p>
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
  <head>
    <meta charset="utf-8" />
    <title>{{subject}}</title>
  </head>
  <body style="margin: 0; background: #f5f7fb">
    <table
      role="presentation"
      width="100%"
      cellpadding="0"
      cellspacing="0"
      style="background: #f5f7fb"
    >
      <tr>
        <td align="center" style="padding: 24px">
          <table
            role="presentation"
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="
              max-width: 600px;
              background: #ffffff;
              border-radius: 16px;
              overflow: hidden;
              box-shadow: 0 8px 24px rgba(20, 20, 43, 0.06);
            "
          >
            <!-- Header -->
            <tr>
              <td style="padding: 16px 20px; background: #0b3b2e">
                <img
                  src="{{logo_url}}"
                  alt="Logo"
                  width="120"
                  style="display: block"
                />
              </td>
            </tr>
            <!-- Banner -->
            <tr>
              <td>
                <img
                  src="{{banner_url}}"
                  alt="Lecturer Banner"
                  width="600"
                  style="
                    width: 100%;
                    height: auto;
                    display: block;
                  "
                />
              </td>
            </tr>
            <!-- Nội dung -->
            <tr>
              <td style="padding: 24px">
                <h1
                  style="
                    margin: 0;
                    font: 700 22px/1.3 Arial;
                    color: #0b3b2e;
                  "
                >
                  Xin chào {{name}} 👋
                </h1>
                <p
                  style="
                    font: 400 14px/1.7 Arial;
                    color: #374151;
                    margin-top: 12px;
                  "
                >
                  Tài khoản <b>giảng viên</b> của bạn đã được khởi tạo thành công.
                  Vui lòng sử dụng thông tin bên dưới để đăng nhập vào hệ thống:
                </p>
                <p
                  style="
                    font: 400 14px/1.7 Arial;
                    color: #374151;
                    margin: 16px 0;
                  "
                >
                  Tài khoản: <b>{{username}}</b><br />
                  Mật khẩu: <b>{{password}}</b>
                </p>
                <a
                  href="{{login_url}}"
                  style="
                    display: inline-block;
                    padding: 12px 18px;
                    background: #059669;
                    color: #fff;
                    border-radius: 8px;
                    text-decoration: none;
                    font: 600 14px Arial;
                  "
                  >Đăng nhập ngay</a
                >
              </td>
            </tr>
            <!-- Footer -->
            <tr>
              <td
                style="
                  padding: 18px 24px;
                  background: #fafafa;
                  text-align: center;
                "
              >
                <p
                  style="
                    font: 400 12px/1.6 Arial;
                    color: #6b7280;
                    margin: 0;
                  "
                >
                  © {{year}} {{app_name}} · Hệ thống quản lý giảng viên
                </p>
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
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>{{subject}}</title>
    </head>
    <body style="margin: 0; background: #f5f7fb">
        <table
            role="presentation"
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="background: #f5f7fb"
        >
            <tr>
                <td align="center" style="padding: 24px">
                    <table
                        role="presentation"
                        width="100%"
                        cellpadding="0"
                        cellspacing="0"
                        style="
                            max-width: 600px;
                            background: #ffffff;
                            border-radius: 16px;
                            overflow: hidden;
                            box-shadow: 0 8px 24px rgba(20, 20, 43, 0.06);
                        "
                    >
                        <!-- Header -->
                        <tr>
                            <td
                                style="
                                    padding: 20px;
                                    background: #1e3a8a;
                                    color: #ffffff;
                                "
                            >
                                <h2 style="margin: 0; font: 700 20px Arial">
                                    🔧 Thông báo bảo trì hệ thống
                                </h2>
                            </td>
                        </tr>

                        <!-- Nội dung -->
                        <tr>
                            <td style="padding: 24px">
                                <p
                                    style="
                                        font: 400 14px/1.6 Arial;
                                        color: #111827;
                                        margin: 0 0 12px 0;
                                    "
                                >
                                    Xin chào <b>{{user_name}}</b>,
                                </p>
                                <p
                                    style="
                                        font: 400 14px/1.7 Arial;
                                        color: #374151;
                                        margin: 0 0 12px 0;
                                    "
                                >
                                    Hệ thống sẽ được tiến hành bảo trì từ
                                    <b>{{start_time}}</b> đến
                                    <b>{{end_time}}</b>.
                                </p>
                                <p
                                    style="
                                        font: 400 14px/1.7 Arial;
                                        color: #374151;
                                        margin: 0 0 16px 0;
                                    "
                                >
                                    Trong khoảng thời gian này, bạn có thể không
                                    truy cập được hệ thống. Vui lòng sắp xếp
                                    công việc phù hợp để tránh gián đoạn.
                                </p>
                                <p
                                    style="
                                        font: 400 13px/1.6 Arial;
                                        color: #6b7280;
                                        margin: 0;
                                    "
                                >
                                    Cảm ơn bạn đã thông cảm và hợp tác.<br />
                                    — Đội ngũ {{app_name}}
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td
                                style="
                                    padding: 18px;
                                    background: #fafafa;
                                    text-align: center;
                                "
                            >
                                <p
                                    style="
                                        font: 400 12px Arial;
                                        color: #6b7280;
                                        margin: 0;
                                    "
                                >
                                    © {{year}} {{app_name}} · Thông báo hệ thống
                                </p>
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
            ],

            [
                'name' => 'task_assigned',
                'title' => 'Công việc mới được giao',
                'subject' => 'Công việc mới: {{task_name}}',
                'email_template' => <<<HTML
<!doctype html>
<html>
  <head><meta charset="utf-8"><title>{{subject}}</title></head>
  <body style="margin:0;background:#f8fafc;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;">
      <tr>
        <td align="center" style="padding:24px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,.08);">
            <tr>
              <td style="padding:20px;background:linear-gradient(135deg, #3b82f6, #1d4ed8);color:#fff;">
                <h2 style="margin:0;font:600 22px Arial;">📋 Công việc mới</h2>
              </td>
            </tr>
            <tr>
              <td style="padding:28px;">
                <p style="font:400 16px Arial;color:#111827;margin:0 0 16px;">Xin chào {{user_name}},</p>
                <p style="font:400 15px Arial;color:#374151;margin:0 0 20px;">
                  Bạn vừa được giao một công việc mới:
                </p>
                <div style="background:#f1f5f9;padding:20px;border-radius:12px;border-left:4px solid #3b82f6;margin:20px 0;">
                  <h3 style="margin:0 0 12px;font:600 18px Arial;color:#1e293b;">{{task_name}}</h3>
                  <p style="margin:0;font:400 14px Arial;color:#64748b;">{{task_description}}</p>
                </div>
                <div style="margin:20px 0;">
                  <p style="margin:8px 0;font:400 14px Arial;color:#374151;">
                    <strong>Người giao:</strong> {{assigner_name}}
                  </p>
                  <p style="margin:8px 0;font:400 14px Arial;color:#374151;">
                    <strong>Hạn hoàn thành:</strong> <span style="color:#dc2626;">{{deadline}}</span>
                  </p>
                </div>
                <div style="text-align:center;margin:28px 0;">
                  <a href="{{task_url}}" style="display:inline-block;padding:14px 24px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font:500 15px Arial;">Xem chi tiết công việc</a>
                </div>
                <p style="font:400 13px Arial;color:#6b7280;margin:20px 0 0;">
                  Vui lòng đăng nhập vào hệ thống để xem chi tiết và thực hiện công việc.
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:20px;background:#f8fafc;text-align:center;border-top:1px solid #e2e8f0;">
                <p style="font:400 12px Arial;color:#64748b;margin:0;">© {{year}} {{app_name}} - Hệ thống quản lý giáo dục</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'sms_template' => 'Công việc mới: {{task_name}} - Hạn: {{deadline}}',
                'push_template' => 'Bạn vừa được giao công việc: {{task_name}}',
                'in_app_template' => 'Bạn vừa được giao công việc: {{task_name}} bởi {{assigner_name}} (Hạn: {{deadline}})',
                'channels' => json_encode(['email', 'push', 'in_app']),
                'priority' => 'medium',
                'category' => 'task',
                'description' => 'Email thông báo công việc mới được giao',
                'is_active' => true,
            ]
        ];

        foreach ($templates as $tpl) {
            DB::table('notification_templates')->insert($tpl);
        }

        $this->command->info('Notification HTML templates seeded!');
    }
}
