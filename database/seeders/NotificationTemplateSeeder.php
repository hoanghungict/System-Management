<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'id' => 1,
                'name' => 'student_account_created',
                'title' => 'Tài khoản sinh viên đã được tạo',
                'subject' => 'Thông tin đăng nhập sinh viên',
                'email_template' => '<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8" />
        <title>{{subject}}</title>
    </head>
    <body
        style="
            margin: 0;
            background: #ffffff;
            font-family: Arial, sans-serif;
            color: #111827;
        "
    >
        <table
            role="presentation"
            width="100%"
            cellspacing="0"
            cellpadding="0"
            style="padding: 40px 0; background: #f9fafb"
        >
            <tr>
                <td align="center">
                    <table
                        role="presentation"
                        width="100%"
                        cellspacing="0"
                        cellpadding="0"
                        style="
                            max-width: 560px;
                            background: #ffffff;
                            border: 1px solid #e5e7eb;
                            border-radius: 12px;
                        "
                    >
                        <!-- Logo -->
                        <tr>
                            <td
                                style="
                                    text-align: center;
                                    padding: 28px 24px 12px 24px;
                                "
                            >
                                <img
                                    src="{{ asset(\'assets/img/logo-email-template.png\') }}"
                                    alt="Logo"
                                    width="120"
                                    style="display: inline-block"
                                />
                            </td>
                        </tr>

                        <!-- Title -->
                        <tr>
                            <td
                                style="
                                    text-align: center;
                                    padding: 0 24px 24px 24px;
                                "
                            >
                                <h1
                                    style="
                                        margin: 0;
                                        font-size: 22px;
                                        font-weight: 700;
                                        color: #1d4ed8;
                                    "
                                >
                                    🎉 Chào mừng {{name}} đến với {{app_name}}!
                                </h1>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding: 0 32px 24px 32px">
                                <p
                                    style="
                                        margin: 0 0 18px 0;
                                        font-size: 15px;
                                        line-height: 1.6;
                                        color: #374151;
                                        text-align: center;
                                    "
                                >
                                    Tài khoản sinh viên của bạn đã được tạo
                                    thành công.<br />
                                    Hãy đăng nhập để bắt đầu trải nghiệm hệ
                                    thống học tập hiện đại.
                                </p>
                            </td>
                        </tr>

                        <!-- Info card -->
                        <tr>
                            <td style="padding: 0 32px">
                                <table
                                    role="presentation"
                                    width="100%"
                                    cellpadding="0"
                                    cellspacing="0"
                                    style="
                                        background: #f3f4f6;
                                        border-radius: 10px;
                                        padding: 18px;
                                    "
                                >
                                    <tr>
                                        <td
                                            style="
                                                font-size: 14px;
                                                color: #111827;
                                                line-height: 1.6;
                                            "
                                        >
                                            <b>Thông tin đăng nhập:</b><br />
                                            📧 Tài khoản: <b>{{user_name}}</b><br />
                                            🔑 Mật khẩu: <b>{{password}}</b>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- CTA -->
                        <tr>
                            <td style="text-align: center; padding: 28px 32px">
                                <a
                                    href="{{login_url}}"
                                    style="
                                        background: #1d4ed8;
                                        color: #ffffff;
                                        text-decoration: none;
                                        font-weight: 600;
                                        font-size: 15px;
                                        padding: 14px 28px;
                                        border-radius: 8px;
                                        display: inline-block;
                                    "
                                >
                                    🚀 Đăng nhập ngay
                                </a>
                            </td>
                        </tr>

                        <!-- Features -->
                        <tr>
                            <td style="padding: 0 32px 32px 32px">
                                <ul
                                    style="
                                        margin: 0;
                                        padding: 0 0 0 20px;
                                        font-size: 14px;
                                        color: #374151;
                                        line-height: 1.6;
                                    "
                                >
                                    <li>Cập nhật thông tin cá nhân</li>
                                    <li>Xem thời khóa biểu & học liệu</li>
                                    <li>
                                        Theo dõi điểm số theo thời gian thực
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <!-- Banner (optional) -->
                        <tr>
                            <td>
                                <img
                                    src="{{banner_url}}"
                                    alt="Banner"
                                    style="
                                        width: 100%;
                                        height: auto;
                                        display: block;
                                        border-top: 1px solid #e5e7eb;
                                    "
                                />
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td
                                style="
                                    padding: 20px 24px;
                                    background: #f9fafb;
                                    text-align: center;
                                    border-top: 1px solid #e5e7eb;
                                "
                            >
                                <p
                                    style="
                                        margin: 0;
                                        font-size: 12px;
                                        color: #6b7280;
                                        line-height: 1.6;
                                    "
                                >
                                    © {{year}} {{app_name}}. Mọi quyền được bảo
                                    lưu.<br />
                                    Đây là email tự động, vui lòng không trả
                                    lời.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>',
                'sms_template' => 'TK Sinh viên: {{user_name}}, MK: {{password}}',
                'push_template' => 'Tài khoản sinh viên {{user_name}} đã được tạo',
                'in_app_template' => 'Tài khoản sinh viên {{user_name}} đã được tạo',
                'channels' => '["email", "sms", "push", "in_app"]',
                'priority' => 'high',
                'category' => 'user',
                'description' => 'Email tạo tài khoản sinh viên',
                'is_active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'lecturer_account_created',
                'title' => 'Tài khoản giảng viên đã được tạo',
                'subject' => 'Thông tin đăng nhập giảng viên',
                'email_template' => '<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8" />
        <title>{{subject}}</title>
    </head>
    <body
        style="
            margin: 0;
            background: #ffffff;
            font-family: Arial, sans-serif;
            color: #111827;
        "
    >
        <table
            role="presentation"
            width="100%"
            cellspacing="0"
            cellpadding="0"
            style="padding: 40px 0; background: #f9fafb"
        >
            <tr>
                <td align="center">
                    <table
                        role="presentation"
                        width="100%"
                        cellspacing="0"
                        cellpadding="0"
                        style="
                            max-width: 560px;
                            background: #ffffff;
                            border: 1px solid #e5e7eb;
                            border-radius: 12px;
                        "
                    >
                        <!-- Logo -->
                        <tr>
                            <td
                                style="
                                    text-align: center;
                                    padding: 28px 24px 12px 24px;
                                "
                            >
                                <img
                                    src="{{ asset(\'assets/img/logo-email-template.png\') }}"
                                    alt="Logo"
                                    width="120"
                                    style="display: inline-block"
                                />
                            </td>
                        </tr>

                        <!-- Title -->
                        <tr>
                            <td
                                style="
                                    text-align: center;
                                    padding: 0 24px 24px 24px;
                                "
                            >
                                <h1
                                    style="
                                        margin: 0;
                                        font-size: 22px;
                                        font-weight: 700;
                                        color: #059669;
                                    "
                                >
                                    👋 Xin chào {{name}} – Chào mừng đến với
                                    {{app_name}}!
                                </h1>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding: 0 32px 24px 32px">
                                <p
                                    style="
                                        margin: 0 0 18px 0;
                                        font-size: 15px;
                                        line-height: 1.6;
                                        color: #374151;
                                        text-align: center;
                                    "
                                >
                                    Tài khoản <b>giảng viên</b> của bạn đã được
                                    tạo thành công.<br />
                                    Hãy đăng nhập để bắt đầu quản lý lớp học và
                                    hỗ trợ sinh viên.
                                </p>
                            </td>
                        </tr>

                        <!-- Info card -->
                        <tr>
                            <td style="padding: 0 32px">
                                <table
                                    role="presentation"
                                    width="100%"
                                    cellpadding="0"
                                    cellspacing="0"
                                    style="
                                        background: #f3f4f6;
                                        border-radius: 10px;
                                        padding: 18px;
                                    "
                                >
                                    <tr>
                                        <td
                                            style="
                                                font-size: 14px;
                                                color: #111827;
                                                line-height: 1.6;
                                            "
                                        >
                                            <b>Thông tin đăng nhập:</b><br />
                                            📧 Tài khoản: <b>{{username}}</b><br />
                                            🔑 Mật khẩu: <b>{{password}}</b>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- CTA -->
                        <tr>
                            <td style="text-align: center; padding: 28px 32px">
                                <a
                                    href="{{login_url}}"
                                    style="
                                        background: #059669;
                                        color: #ffffff;
                                        text-decoration: none;
                                        font-weight: 600;
                                        font-size: 15px;
                                        padding: 14px 28px;
                                        border-radius: 8px;
                                        display: inline-block;
                                    "
                                >
                                    🚀 Đăng nhập ngay
                                </a>
                            </td>
                        </tr>

                        <!-- Features -->
                        <tr>
                            <td style="padding: 0 32px 32px 32px">
                                <ul
                                    style="
                                        margin: 0;
                                        padding: 0 0 0 20px;
                                        font-size: 14px;
                                        color: #374151;
                                        line-height: 1.6;
                                    "
                                >
                                    <li>Quản lý lớp học và sinh viên</li>
                                    <li>Cập nhật thông tin cá nhân</li>
                                    <li>
                                        Theo dõi tiến độ và điểm số của sinh
                                        viên
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <!-- Banner (optional) -->
                        <tr>
                            <td>
                                <img
                                    src="{{ banner_url }}"
                                    alt="Banner"
                                    style="
                                        width: 100%;
                                        height: auto;
                                        display: block;
                                        border-top: 1px solid #e5e7eb;
                                    "
                                />
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td
                                style="
                                    padding: 20px 24px;
                                    background: #f9fafb;
                                    text-align: center;
                                    border-top: 1px solid #e5e7eb;
                                "
                            >
                                <p
                                    style="
                                        margin: 0;
                                        font-size: 12px;
                                        color: #6b7280;
                                        line-height: 1.6;
                                    "
                                >
                                    © {{year}} {{app_name}}. Mọi quyền được bảo
                                    lưu.<br />
                                    Đây là email tự động, vui lòng không trả
                                    lời.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>',
                'sms_template' => 'TK GV: {{username}}, MK: {{password}}',
                'push_template' => 'Tài khoản giảng viên {{username}} đã được tạo',
                'in_app_template' => 'Tài khoản giảng viên {{username}} đã được tạo',
                'channels' => '["email", "sms", "push"]',
                'priority' => 'high',
                'category' => 'user',
                'description' => 'Email tạo tài khoản giảng viên',
                'is_active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 3,
                'name' => 'system_maintenance_html',
                'title' => 'Thông báo bảo trì hệ thống',
                'subject' => 'Thông báo bảo trì hệ thống',
                'email_template' => '<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>{{subject}}</title>
    </head>
    <body
        style="
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: Arial, sans-serif;
        "
    >
        <table
            role="presentation"
            width="100%"
            cellspacing="0"
            cellpadding="0"
            style="padding: 20px 0"
        >
            <tr>
                <td align="center">
                    <table
                        role="presentation"
                        width="100%"
                        cellspacing="0"
                        cellpadding="0"
                        style="
                            max-width: 600px;
                            border: 1px solid #e5e7eb;
                            border-radius: 12px;
                            overflow: hidden;
                        "
                    >
                        <!-- Header -->
                        <tr>
                            <td
                                style="
                                    padding: 24px;
                                    text-align: center;
                                    background: #f9fafb;
                                "
                            >
                                <img
                                    src="https://dummyimage.com/120x40/1e3a8a/ffffff.png&text=HPC"
                                    alt="{{app_name}}"
                                    style="
                                        max-height: 40px;
                                        margin-bottom: 16px;
                                    "
                                />
                                <h2
                                    style="
                                        margin: 0;
                                        font-size: 22px;
                                        color: #1f2937;
                                        font-weight: 700;
                                    "
                                >
                                    Thông báo bảo trì
                                </h2>
                            </td>
                        </tr>

                        <!-- Nội dung -->
                        <tr>
                            <td style="padding: 32px; background: #ffffff">
                                <p
                                    style="
                                        margin: 0 0 16px 0;
                                        font-size: 15px;
                                        line-height: 1.6;
                                        color: #111827;
                                    "
                                >
                                    Xin chào <b>{{user_name}}</b>,
                                </p>
                                <p
                                    style="
                                        margin: 0 0 16px 0;
                                        font-size: 14px;
                                        line-height: 1.7;
                                        color: #374151;
                                    "
                                >
                                    Hệ thống của chúng tôi sẽ được tiến hành bảo
                                    trì theo lịch trình sau:
                                </p>
                                <table
                                    role="presentation"
                                    width="100%"
                                    cellspacing="0"
                                    cellpadding="0"
                                    style="
                                        margin: 0 0 20px 0;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                    "
                                >
                                    <tr>
                                        <td
                                            style="
                                                padding: 12px 16px;
                                                font-size: 14px;
                                                color: #1e3a8a;
                                                font-weight: bold;
                                            "
                                        >
                                            🕒 Thời gian:
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="
                                                padding: 12px 16px;
                                                font-size: 14px;
                                                color: #374151;
                                            "
                                        >
                                            Từ <b>{{start_time}}</b> đến
                                            <b>{{end_time}}</b>
                                        </td>
                                    </tr>
                                </table>
                                <p
                                    style="
                                        margin: 0 0 20px 0;
                                        font-size: 14px;
                                        line-height: 1.7;
                                        color: #374151;
                                    "
                                >
                                    Trong thời gian này, có thể bạn sẽ không
                                    truy cập được hệ thống. Vui lòng sắp xếp
                                    công việc để tránh gián đoạn.
                                </p>
                                <p
                                    style="
                                        margin: 0;
                                        font-size: 13px;
                                        color: #6b7280;
                                    "
                                >
                                    Trân trọng,<br />
                                    — Đội ngũ <b>{{app_name}}</b>
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td
                                style="
                                    background: #f9fafb;
                                    padding: 16px;
                                    text-align: center;
                                "
                            >
                                <p
                                    style="
                                        margin: 0;
                                        font-size: 12px;
                                        color: #6b7280;
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
</html>',
                'sms_template' => 'Bảo trì hệ thống: {{start_time}} - {{end_time}}',
                'push_template' => 'Hệ thống sẽ bảo trì từ {{start_time}}',
                'in_app_template' => 'Hệ thống sẽ bảo trì từ {{start_time}} đến {{end_time}}',
                'channels' => '["email", "push", "in_app"]',
                'priority' => 'critical',
                'category' => 'system',
                'description' => 'Email thông báo bảo trì có HTML đẹp',
                'is_active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 4,
                'name' => 'task_assigned',
                'title' => 'Công việc mới được giao',
                'subject' => 'Công việc mới: {{task_name}}',
                'email_template' => '<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>{{subject}}</title>
  </head>
  <body style="margin:0;background:#f8fafc;font-family:Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;">
      <tr>
        <td align="center" style="padding:24px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" 
            style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.06);">
            
            <!-- Header -->
            <tr>
              <td style="padding:24px;background:linear-gradient(135deg,#2563eb,#1e3a8a);color:#ffffff;">
                <h2 style="margin:0;font-size:22px;font-weight:700;">📋 Công việc mới</h2>
              </td>
            </tr>

            <!-- Content -->
            <tr>
              <td style="padding:32px;">
                <p style="margin:0 0 16px 0;font-size:16px;color:#111827;">
                  Xin chào <b>{{user_name}}</b>,
                </p>
                <p style="margin:0 0 20px 0;font-size:15px;color:#374151;">
                  Bạn vừa được giao một công việc mới:
                </p>

                <!-- Task Box -->
                <div style="background:#f1f5f9;padding:20px;border-radius:12px;border-left:4px solid #2563eb;margin:20px 0;">
                  <h3 style="margin:0 0 10px;font-size:18px;font-weight:600;color:#1e293b;">{{task_name}}</h3>
                  <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">{{task_description}}</p>
                </div>

                <!-- Task Details -->
                <div style="margin:20px 0;">
                  <p style="margin:8px 0;font-size:14px;color:#374151;">
                    <strong>👤 Người giao:</strong> {{assigner_name}}
                  </p>
                  <p style="margin:8px 0;font-size:14px;color:#374151;">
                    <strong>⏰ Hạn hoàn thành:</strong> <span style="color:#dc2626;font-weight:600;">{{deadline}}</span>
                  </p>
                </div>

                <!-- Button -->
                <div style="text-align:center;margin:28px 0;">
                  <a href="{{task_url}}" 
                    style="display:inline-block;padding:14px 28px;background:#2563eb;color:#ffffff;border-radius:8px;text-decoration:none;font-size:15px;font-weight:600;">
                    🔎 Xem chi tiết công việc
                  </a>
                </div>

                <p style="font-size:13px;line-height:1.6;color:#6b7280;margin:20px 0 0;">
                  Vui lòng đăng nhập vào hệ thống để xem chi tiết và thực hiện công việc.
                </p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="padding:20px;background:#f9fafb;text-align:center;border-top:1px solid #e5e7eb;">
                <p style="margin:0;font-size:12px;color:#6b7280;">
                  © {{year}} {{app_name}} · Hệ thống quản lý giáo dục
                </p>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </body>
</html>',
                'sms_template' => 'Công việc mới: {{task_name}} - Hạn: {{deadline}}',
                'push_template' => 'Bạn vừa được giao công việc: {{task_name}}',
                'in_app_template' => 'Bạn vừa được giao công việc: {{task_name}} bởi {{assigner_name}} (Hạn: {{deadline}})',
                'channels' => '["email", "push", "in_app"]',
                'priority' => 'medium',
                'category' => 'task',
                'description' => 'Email thông báo công việc mới được giao',
                'is_active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 6,
                'name' => 'official_dispatch',
                'title' => 'Công văn mới cần xử lý',
                'subject' => 'Bạn có công văn mới cần xử lý: {{documentTitle}}',
                'email_template' => '<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>Bạn có công văn mới cần xử lý</title>
        <style>
            /* General reset */
            body,
            table,
            td,
            a {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table,
            td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                display: block;
                border: 0;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            body {
                margin: 0;
                padding: 0;
                width: 100% !important;
                background-color: #f4f6f8;
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial,
                    sans-serif;
                color: #1f2937;
            }

            /* Container */
            .email-wrap {
                width: 100%;
                background-color: #f4f6f8;
                padding: 28px 16px;
            }
            .email-main {
                max-width: 680px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 6px 30px rgba(15, 23, 42, 0.08);
            }

            /* Header */
            .header {
                padding: 22px 28px;
                display: flex;
                align-items: center;
                gap: 16px;
                background: linear-gradient(90deg, #0c60b9 0%, #063970 100%);
                color: #fff;
            }
            .logo {
                width: 56px;
                height: 56px;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.15);
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 18px;
            }
            .header-title {
                font-size: 18px;
                line-height: 1;
                font-weight: 600;
            }

            /* Body */
            .body {
                padding: 24px 28px;
            }
            h1 {
                font-size: 20px;
                margin: 0 0 10px 0;
                color: #0f172a;
            }
            p.lead {
                margin: 0 0 18px 0;
                color: #374151;
                font-size: 15px;
            }
            .card {
                background: #f8fafc;
                border: 1px solid #e6eef6;
                padding: 16px;
                border-radius: 10px;
                margin: 14px 0;
            }
            .meta {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
                margin-top: 8px;
            }
            .meta-item {
                background: #fff;
                border: 1px solid #e6eef6;
                padding: 10px 12px;
                border-radius: 8px;
                min-width: 150px;
                box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);
            }
            .meta-key {
                display: block;
                font-size: 12px;
                color: #6b7280;
                margin-bottom: 6px;
            }
            .meta-value {
                font-weight: 600;
                font-size: 14px;
                color: #0f172a;
            }

            /* Action box */
            .action {
                text-align: center;
                padding: 18px 0;
            }
            .btn {
                display: inline-block;
                padding: 12px 20px;
                border-radius: 10px;
                background: #063970;
                color: while;
                text-decoration: none;
                font-weight: 700;
                box-shadow: 0 6px 18px rgba(14, 165, 163, 0.14);
            }
            .secondary {
                display: inline-block;
                margin-top: 10px;
                color: #6b7280;
                font-size: 13px;
                text-decoration: none;
            }

            /* Footer */
            .footer {
                padding: 18px 28px;
                font-size: 13px;
                color: #6b7280;
                border-top: 1px solid #eef2f7;
                background: #fff;
                display: flex;
                justify-content: space-between;
                gap: 12px;
                align-items: center;
            }
            .brand {
                font-weight: 700;
                color: #0f172a;
            }
            .legal {
                font-size: 12px;
                color: #9ca3af;
            }

            /* Important note */
            .note {
                background: #fff7ed;
                border: 1px solid #ffedd5;
                color: #92400e;
                padding: 12px;
                border-radius: 8px;
                margin-top: 12px;
                font-size: 13px;
            }

            /* Responsive */
            @media screen and (max-width: 480px) {
                .header {
                    padding: 16px;
                    gap: 10px;
                }
                .body {
                    padding: 18px;
                }
                .footer {
                    padding: 16px;
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }
                .meta-item {
                    min-width: 120px;
                }
            }
        </style>
    </head>
    <body>
        <table
            role="presentation"
            class="email-wrap"
            width="100%"
            cellpadding="0"
            cellspacing="0"
        >
            <tr>
                <td align="center">
                    <table
                        role="presentation"
                        class="email-main"
                        width="100%"
                        cellpadding="0"
                        cellspacing="0"
                    >
                        <!-- Header -->
                        <tr>
                            <td>
                                <div class="header">
                                    <div class="logo" aria-hidden="true">
                                        HPC
                                    </div>
                                    <div>
                                        <div class="header-title">
                                            Bạn có công văn mới cần xử lý
                                        </div>
                                        <div
                                            style="
                                                font-size: 13px;
                                                opacity: 0.95;
                                            "
                                        >
                                            Giao việc —
                                            <strong>{{assignerName}}</strong> đã
                                            phân công cho bạn
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td class="body">
                                <h1>Xin chào, {{assigneeName}} 👋</h1>
                                <p class="lead">
                                    Bạn vừa được phân công xử lý một công văn
                                    mới. Vui lòng xem chi tiết bên dưới và thực
                                    hiện hành động cần thiết.
                                </p>

                                <!-- Document card -->
                                <div class="card">
                                    <div
                                        style="
                                            display: flex;
                                            justify-content: space-between;
                                            align-items: center;
                                            gap: 12px;
                                            flex-wrap: wrap;
                                        "
                                    >
                                        <div style="flex: 1; min-width: 220px">
                                            <div
                                                style="
                                                    font-size: 14px;
                                                    color: #374151;
                                                    margin-bottom: 6px;
                                                "
                                            >
                                                Tiêu đề
                                            </div>
                                            <div
                                                style="
                                                    font-weight: 700;
                                                    font-size: 15px;
                                                    color: #0f172a;
                                                "
                                            >
                                                {{documentTitle}}
                                            </div>
                                            <div
                                                style="
                                                    margin-top: 10px;
                                                    font-size: 13px;
                                                    color: #6b7280;
                                                "
                                            >
                                                Số hiệu:
                                                <strong
                                                    >{{documentSerialNumber}}</strong
                                                >
                                            </div>
                                        </div>
                                        <div
                                            style="
                                                text-align: right;
                                                min-width: 160px;
                                            "
                                        >
                                            <div
                                                style="
                                                    font-size: 12px;
                                                    color: #6b7280;
                                                    margin-bottom: 6px;
                                                "
                                            >
                                                Người giao
                                            </div>
                                            <div
                                                style="
                                                    font-weight: 700;
                                                    color: #0f172a;
                                                "
                                            >
                                                {{assignerName}}
                                            </div>
                                            <div
                                                style="
                                                    margin-top: 10px;
                                                    font-size: 12px;
                                                    color: #6b7280;
                                                "
                                            >
                                                Ngày nhận
                                            </div>
                                            <div
                                                style="
                                                    font-weight: 600;
                                                    color: #0f172a;
                                                "
                                            >
                                                {{assignedDate}}
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="meta"
                                        role="list"
                                        aria-label="document meta"
                                    >
                                        <div class="meta-item" role="listitem">
                                            <span class="meta-key"
                                                >Trạng thái</span
                                            >
                                            <span class="meta-value"
                                                >Đã phân công</span
                                            >
                                        </div>
                                        <div class="meta-item" role="listitem">
                                            <span class="meta-key"
                                                >Yêu cầu</span
                                            >
                                            <span
                                                class="meta-value"
                                                style="font-weight: 600"
                                                >{{actionRequired}}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Action -->
                                <div class="action">
                                    <a
                                        class="btn"
                                        href="{{documentUrl}}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        >Xem công văn & xử lý</a
                                    >
                                    <div>
                                        <a
                                            class="secondary"
                                            href="{{documentUrl}}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            >Mở trong ứng dụng</a
                                        >
                                    </div>
                                </div>

                                <!-- Note / Warning -->
                                <div class="note" role="note">
                                    <strong>Ghi chú:</strong> Nếu bạn không phải
                                    là người nhận hoặc có vấn đề truy cập, vui
                                    lòng liên hệ người giao —
                                    <strong>{{assignerName}}</strong>.
                                </div>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td class="footer">
                                <div>
                                    <div class="brand">HPC System</div>
                                    <div class="legal">
                                        © {{year}} HPC. Tất
                                        cả quyền được bảo lưu.
                                    </div>
                                </div>
                                <div style="text-align: right">
                                    <div
                                        style="font-size: 13px; color: #6b7280"
                                    >
                                        Bạn cần trợ giúp?
                                    </div>
                                    <div style="font-size: 13px">
                                        <a
                                            href="mailto:support@hpc-app.com"
                                            style="
                                                color: #063970;
                                                text-decoration: none;
                                            "
                                            >support@hpc-app.com</a
                                        >
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <script>
            // Chỉ để hiển thị năm trong email client hỗ trợ script (không quan trọng)
            try {
                document.getElementById("year").textContent =
                    new Date().getFullYear();
            } catch (e) {}
        </script>
    </body>
</html>',
                'sms_template' => 'Công văn mới: {{documentTitle}} từ {{assignerName}}. Vui lòng xem chi tiết.',
                'push_template' => 'Bạn có công văn mới từ {{assignerName}}: {{documentTitle}}',
                'in_app_template' => 'Bạn được phân công xử lý công văn "{{documentTitle}}" bởi {{assignerName}}',
                'channels' => '["email", "push", "in_app", "sms"]',
                'priority' => 'high',
                'category' => 'official_dispatch',
                'description' => 'Template thông báo công văn chính thức được phân công - Thiết kế chuyên nghiệp với brand HPC',
                'is_active' => 1,
                'created_at' => '2025-09-23 03:44:12',
                'updated_at' => '2025-09-23 03:44:12',
            ],
            [
                'id' => 7,
                'name' => 'official_dispatch_status',
                'title' => 'Công văn của bạn đã được xử lý',
                'subject' => 'Công văn {{documentTitle}} đã được xử lý',
                'email_template' => '<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Cập nhật trạng thái công văn</title>
    </head>
    <body
        style="
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: Arial, sans-serif;
        "
    >
        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="background-color: #f4f6f8; padding: 30px 0"
        >
            <tr>
                <td align="center">
                    <table
                        width="600"
                        cellpadding="0"
                        cellspacing="0"
                        style="
                            background: #ffffff;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                        "
                    >
                        <!-- Header -->
                        <tr>
                            <td
                                style="
                                    background: #063970;
                                    padding: 20px;
                                    text-align: center;
                                "
                            >
                                <h1
                                    style="
                                        margin: 0;
                                        font-size: 22px;
                                        color: #ffffff;
                                    "
                                >
                                    Công văn của bạn đã được xử lý
                                </h1>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td
                                style="
                                    padding: 30px 40px;
                                    color: #333;
                                    font-size: 15px;
                                    line-height: 1.6;
                                "
                            >
                                <p>Xin chào <strong>{{authorName}}</strong>,</p>
                                <p>
                                    Công văn
                                    <strong>{{documentSerialNumber}}</strong> –
                                    "<em>{{documentTitle}}</em>" do bạn tạo đã
                                    được <strong>{{reviewerName}}</strong> xử lý
                                    với trạng thái:
                                    <span
                                        style="color:{{status == \'Đã phê duyệt\' ? \'#16a34a\' : \'#dc2626\'}};font-weight:bold;"
                                    >
                                        {{status}} </span
                                    >.
                                </p>

                                <!-- Ghi chú -->
                                <table
                                    cellpadding="0"
                                    cellspacing="0"
                                    width="100%"
                                    style="
                                        margin: 20px 0;
                                        background: #f9fafb;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                    "
                                >
                                    <tr>
                                        <td
                                            style="
                                                padding: 15px;
                                                color: #555;
                                                font-size: 14px;
                                            "
                                        >
                                            <strong
                                                >Ghi chú từ người xử lý:</strong
                                            ><br />
                                            "{{reviewComment}}"
                                        </td>
                                    </tr>
                                </table>

                                <p>
                                    Bạn có thể xem chi tiết công văn và toàn bộ
                                    quá trình xử lý tại liên kết dưới đây:
                                </p>

                                <!-- CTA Button -->
                                <p style="text-align: center; margin: 30px 0">
                                    <a
                                        href="{{documentUrl}}"
                                        style="
                                            background: #063970;
                                            color: #ffffff;
                                            text-decoration: none;
                                            padding: 12px 24px;
                                            border-radius: 6px;
                                            font-weight: bold;
                                            display: inline-block;
                                        "
                                    >
                                        Xem chi tiết công văn
                                    </a>
                                </p>

                                <p style="margin-top: 30px">
                                    Trân trọng,<br /><strong
                                        >Hệ thống Quản lý Công văn HPC</strong
                                    >
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td
                                style="
                                    background: #f9fafb;
                                    padding: 15px;
                                    text-align: center;
                                    font-size: 12px;
                                    color: #888;
                                "
                            >
                                Đây là email tự động, vui lòng không trả lời
                                trực tiếp.<br />
                                © 2025 HPC Corp. All rights reserved.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>',
                'sms_template' => 'Công văn {{documentTitle}} đã được xử lý',
                'push_template' => 'Công văn {{documentSerialNumber}} đã được xử lý',
                'in_app_template' => 'Công văn {{documentSerialNumber}} đã được xử lý',
                'channels' => '["email", "push", "in_app"]',
                'priority' => 'medium',
                'category' => 'official_dispatch',
                'description' => 'Template thông báo công băn',
                'is_active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 8,
                'name' => 'quiz_result',
                'title' => 'Kết quả kiểm tra',
                'subject' => 'Kết quả kiểm tra',
                'email_template' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kết Quả Bài Quiz</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f8; padding: 30px 0">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: #063970; padding: 20px; text-align: center;">
                            <h1 style="margin: 0; font-size: 22px; color: #ffffff;">
                                🎉 Kết quả bài Quiz mới!
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px 40px; color: #333; font-size: 15px; line-height: 1.6;">
                            <p>Xin chào <strong>{{student_name}}</strong>,</p>
                            <p>
                                Bạn vừa nhận được kết quả cho bài quiz:
                                <strong>"{{title_quiz}}"</strong>.
                            </p>

                            <!-- Box điểm số -->
                            <table cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 15px; color: #111; font-size: 16px; text-align: center;">
                                        <strong>Điểm số của bạn:</strong><br>
                                        <span style="font-size: 26px; font-weight: bold; color: #16a34a;">
                                            {{score}}
                                        </span> / 10
                                    </td>
                                </tr>
                            </table>

                            <p>
                                Bạn có thể xem chi tiết kết quả và phân tích bài làm tại liên kết bên dưới:
                            </p>

                            <!-- CTA Button -->
                            <p style="text-align: center; margin: 30px 0;">
                                <a href="{{quiz_url}}" style="background: #063970; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; display: inline-block;">
                                    Xem chi tiết kết quả
                                </a>
                            </p>

                            <p style="margin-top: 30px">
                                Thời gian làm bài: <strong>{{date}}</strong><br>
                                Trân trọng,<br>
                                <strong>Hệ thống Quiz HPC</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f9fafb; padding: 15px; text-align: center; font-size: 12px; color: #888;">
                            Đây là email tự động, vui lòng không trả lời trực tiếp.<br>
                            © {{year}} HPC Corp. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>',
                'sms_template' => null,
                'push_template' => 'Điểm kiểm tra bài : {{title_quiz}} đã có',
                'in_app_template' => 'Điểm kiểm tra bài : {{title_quiz}} đã có',
                'channels' => '["email", "sms", "push", "in_app"]',
                'priority' => 'medium',
                'category' => 'quiz',
                'description' => 'Trả kết quả kiểm tra',
                'is_active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 9,
                'name' => 'course_create',
                'title' => 'Có khóa học mới cần phê duyệt',
                'subject' => 'Có khóa học mới được tạo cần phê duyệt',
                'email_template' => '<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Yêu Cầu Phê Duyệt Khóa Học</title>
    </head>
    <body
        style="
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: Arial, sans-serif;
        "
    >
        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="background-color: #f4f6f8; padding: 30px 0"
        >
            <tr>
                <td align="center">
                    <table
                        width="600"
                        cellpadding="0"
                        cellspacing="0"
                        style="
                            background: #ffffff;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                        "
                    >
                        <!-- Header -->
                        <tr>
                            <td
                                style="
                                    background: #063970;
                                    padding: 20px;
                                    text-align: center;
                                "
                            >
                                <h1
                                    style="
                                        margin: 0;
                                        font-size: 22px;
                                        color: #ffffff;
                                    "
                                >
                                    📚 Yêu cầu phê duyệt khóa học mới
                                </h1>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td
                                style="
                                    padding: 30px 40px;
                                    color: #333;
                                    font-size: 15px;
                                    line-height: 1.6;
                                "
                            >
                                <p>Kính gửi <strong>Quản trị viên</strong>,</p>
                                <p>
                                    Giảng viên
                                    <strong>{{lecturer_name}}</strong> vừa tạo
                                    một khóa học mới:
                                    <strong>"{{title}}"</strong>.
                                </p>

                                <!-- Box thông tin -->
                                <table
                                    cellpadding="0"
                                    cellspacing="0"
                                    width="100%"
                                    style="
                                        margin: 20px 0;
                                        background: #f9fafb;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                    "
                                >
                                    <tr>
                                        <td
                                            style="
                                                padding: 15px;
                                                color: #111;
                                                font-size: 14px;
                                            "
                                        >
                                            <strong>Người tạo:</strong>
                                            {{lecturer_name}} <br />
                                            <strong>Tên khóa học:</strong>
                                            {{title}}
                                        </td>
                                    </tr>
                                </table>

                                <p>
                                    Vui lòng xem xét và phê duyệt khóa học này
                                    để nó có thể được mở cho sinh viên đăng ký.
                                </p>

                                <!-- CTA Button -->
                                <p style="text-align: center; margin: 30px 0">
                                    <a
                                        href="{{course_review_url}}"
                                        style="
                                            background: #063970;
                                            color: #ffffff;
                                            text-decoration: none;
                                            padding: 12px 24px;
                                            border-radius: 6px;
                                            font-weight: bold;
                                            display: inline-block;
                                        "
                                    >
                                        Xem chi tiết & Phê duyệt
                                    </a>
                                </p>

                                <p style="margin-top: 30px">
                                    Thời gian tạo: <strong>{{date}}</strong><br />
                                    Trân trọng,<br />
                                    <strong
                                        >Hệ thống Quản lý Khóa học HPC</strong
                                    >
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td
                                style="
                                    background: #f9fafb;
                                    padding: 15px;
                                    text-align: center;
                                    font-size: 12px;
                                    color: #888;
                                "
                            >
                                Đây là email tự động, vui lòng không trả lời
                                trực tiếp.<br />
                                © {{year}} HPC Corp. All rights reserved.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>',
                'sms_template' => null,
                'push_template' => 'Có khóa học mới được tạo cần phê duyệt',
                'in_app_template' => 'Có khóa học mới được tạo cần phê duyệt',
                'channels' => '["email", "sms", "push", "in_app"]',
                'priority' => 'medium',
                'category' => 'course',
                'description' => 'Phê duyệt khóa học',
                'is_active' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('notification_templates')->updateOrInsert(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
