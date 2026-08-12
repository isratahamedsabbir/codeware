<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'user_welcome',
                'name' => 'User Welcome',
                'description' => 'Sent to users after successful registration.',
                'subject_template' => 'Welcome to {{site_name}}, {{name}}!',
                'body_template' => '<p>Dear {{name}},</p><p>Welcome to {{site_name}}!</p><p>Your account has been created successfully. You can now sign in and start exploring all the products and services we offer.</p><h3>Account Access:</h3><p>🔗 Login: {{login_url}}<br>📧 Email: {{email}}</p><p>If you need any assistance, our support team is always here to help.</p><p>Best regards,<br>{{site_name}} Team</p>',
                'variables' => ['name', 'email', 'login_url', 'site_name'],
                'active' => true,
            ],
            [
                'key' => 'user_password_reset',
                'name' => 'Password Reset',
                'description' => 'Sent to users when they request a password reset.',
                'subject_template' => 'Reset Your Password – {{site_name}}',
                'body_template' => '<p>Dear {{name}},</p><p>We received a request to reset the password for your {{site_name}} account.</p><p>To reset your password, please click the button below:</p><p style="text-align:center;margin:28px 0;">
                <a href="{{reset_link}}" style="display:inline-block;background:#7cc242;color:#ffffff;font-family:Poppins,Arial,sans-serif;font-size:14px;font-weight:600;padding:12px 32px;border-radius:50px;letter-spacing:0.4px;text-decoration:none;">Reset Password</a></p><p>⏳ This link will expire in {{expiry_minutes}} minutes.</p><p>If you did not request this, you can safely ignore this email.</p><p>Best regards,<br>{{site_name}} Team</p>',
                'variables' => ['name', 'reset_link', 'expiry_minutes', 'site_name'],
                'active' => true,
            ],
            [
                'key' => 'order_confirmation',
                'name' => 'Order Confirmation (Customer)',
                'description' => 'Order confirmation sent to the customer.',
                'subject_template' => 'Order Confirmation – #{{order_id}}',
                'body_template' => '<p>Dear {{customer_name}},</p><p>Thank you for your order with {{site_name}}! Your order has been confirmed.</p><p>🧾 Order Number: <strong>#{{order_id}}</strong><br>📅 Order Date: {{order_date}}<br>💳 Payment Method: {{payment_method}}<br>💰 Total Amount: {{order_total}}</p><p>📦 Estimated Delivery: {{estimated_delivery}}</p><p>You can track your order anytime by signing in to your account:</p><p>👉 {{account_url}}</p><p>If you have any questions, feel free to contact our support team.</p><p>Thank you for choosing us!</p><p>Best regards,<br>{{site_name}} Team</p>',
                'variables' => ['customer_name', 'order_id', 'order_date', 'payment_method', 'order_total', 'estimated_delivery', 'account_url', 'site_name'],
                'active' => true,
            ],
            [
                'key' => 'order_admin_notification',
                'name' => 'Order Notification (Admin)',
                'description' => 'Sent to admin when a new order is placed.',
                'subject_template' => 'New Order – #{{order_id}} | {{customer_name}}',
                'body_template' => '<p>Dear Admin,</p><p>A new order has been placed on your website. Please find the details below:</p><p>🧾 Order Number: <strong>#{{order_id}}</strong><br>🔹 Customer Name: {{customer_name}}<br>🔹 Phone: {{customer_phone}}<br>🔹 Email: {{customer_email}}<br>💳 Payment Method: {{payment_method}}<br>💰 Total Amount: {{order_total}}</p><p>📅 Order Date: {{order_date}}</p><p>Please review the order and ensure it is processed on time.</p><p>Best regards,<br>Website Order System</p>',
                'variables' => ['order_id', 'customer_name', 'customer_phone', 'customer_email', 'payment_method', 'order_total', 'order_date'],
                'active' => true,
            ],
            [
                'key' => 'contact_message_for_admin',
                'name' => 'Contact Message (Admin)',
                'description' => 'Sent to admin when a new contact message is submitted.',
                'subject_template' => 'New Contact Message Received',
                'body_template' => '<p>You have received a new contact message.</p><p>Name: {{name}}<br>Email: {{email}}<br>Phone: {{phone}}<br>Message: {{message}}</p>',
                'variables' => ['name', 'email', 'phone', 'message'],
                'active' => true,
            ],
            [
                'key' => 'contact_message_for_customer',
                'name' => 'Contact Message (Customer)',
                'description' => 'Sent to the customer after submitting a contact form message.',
                'subject_template' => 'Thank you for contacting {{site_name}}, {{name}}!',
                'body_template' => '<h1>Dear {{name}},</h1><p>Thank you for reaching out to {{site_name}}. We truly appreciate your interest.</p><p>We have received your message and our team will get back to you as soon as possible.</p><p>If you need any assistance, feel free to contact us anytime.</p><p>Best regards,<br>{{site_name}} Team</p>',
                'variables' => ['name', 'email', 'phone', 'message', 'site_name'],
                'active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                $template,
            );
        }
    }
}
