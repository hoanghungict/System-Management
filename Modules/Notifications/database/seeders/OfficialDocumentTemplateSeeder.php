<?php

namespace Modules\Notifications\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\app\Models\NotificationTemplate;
use Illuminate\Support\Facades\DB;

class OfficialDocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa template cũ nếu có
        NotificationTemplate::where('name', 'official_document_assigned')->delete();

        $emailTemplate = <<<HTML
<!DOCTYPE html>
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
                color: #fff;
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
</html>
HTML;

        // Tạo template mới
        NotificationTemplate::create([
            'name' => 'official_dispatch',
            'title' => 'Công văn mới cần xử lý',
            'subject' => 'Bạn có công văn mới cần xử lý: {{documentTitle}}',
            'email_template' => $emailTemplate,
            'sms_template' => 'Công văn mới: {{documentTitle}} từ {{assignerName}}. Vui lòng xem chi tiết.',
            'push_template' => 'Bạn có công văn mới từ {{assignerName}}: {{documentTitle}}',
            'in_app_template' => 'Bạn được phân công xử lý công văn "{{documentTitle}}" bởi {{assignerName}}',
            'channels' => ['email', 'push', 'in_app', 'sms'],
            'priority' => 'high',
            'category' => 'official_dispatch',
            'description' => 'Template thông báo công văn chính thức được phân công - Thiết kế chuyên nghiệp với brand HPC',
            'is_active' => true,
        ]);

        $this->command->info('✅ Official Document Template đã được tạo thành công!');
        $this->command->info('📧 Template name: official_document_assigned');
        $this->command->info('🎨 Thiết kế: Professional HPC Brand với color scheme xanh navy');
        $this->command->info('📱 Channels: Email, Push, In-app, SMS');
        $this->command->info('🔧 Variables hỗ trợ:');
        $this->command->info('   - {{assignerName}} - Tên người giao');
        $this->command->info('   - {{assigneeName}} - Tên người nhận');
        $this->command->info('   - {{documentTitle}} - Tiêu đề công văn');
        $this->command->info('   - {{documentSerialNumber}} - Số hiệu');
        $this->command->info('   - {{assignedDate}} - Ngày nhận');
        $this->command->info('   - {{actionRequired}} - Yêu cầu hành động');
        $this->command->info('   - {{documentUrl}} - Link xem công văn');
        $this->command->info('   - {{year}} - Năm hiện tại');
    }
}
