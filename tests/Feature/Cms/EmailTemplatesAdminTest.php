<?php

use App\Livewire\Admin\EmailTemplates\Index;
use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use App\Services\EmailTemplateService;
use App\Support\EnvFile;
use Database\Seeders\EmailTemplatesSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('redirects guests away from email templates admin page', function () {
    $this->get('/admin/email-templates')->assertRedirect('/login');
});

it('forbids non-admin users from email templates admin page', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get('/admin/email-templates')->assertForbidden();
});

it('allows admins to access email templates admin page', function () {
    EmailTemplate::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/admin/email-templates')
        ->assertSuccessful()
        ->assertSee('Email Templates');
});

it('renders email templates index and auto-selects first template', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $template = EmailTemplate::factory()->create(['name' => 'Welcome Email']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Welcome Email')
        ->assertSet('selectedTemplateId', $template->id);
});

it('loads template details when selecting a template', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $template = EmailTemplate::factory()->create([
        'name' => 'Order Confirmation',
        'subject_template' => 'Order #{{order_id}} confirmed',
        'body_template' => '<p>Hello {{customer_name}}</p>',
        'variables' => ['customer_name', 'order_id'],
        'active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('selectTemplate', $template->id)
        ->assertSet('templateKey', $template->key)
        ->assertSet('subjectTemplate', 'Order #{{order_id}} confirmed')
        ->assertSet('variablesList', 'customer_name, order_id')
        ->assertSet('active', true);
});

it('updates email template content through livewire form', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $template = EmailTemplate::factory()->create([
        'key' => 'order_customer_confirmation',
        'name' => 'Order Confirmation',
        'subject_template' => 'Old Subject',
        'body_template' => '<p>Hello {{customer_name}}</p>',
        'variables' => ['customer_name'],
        'active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('selectTemplate', $template->id)
        ->set('subjectTemplate', 'Receipt #{{order_id}}')
        ->set('bodyTemplate', '<p>Hi {{customer_name}}</p>')
        ->set('variablesList', 'customer_name, order_id')
        ->set('active', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('email_templates', [
        'id' => $template->id,
        'subject_template' => 'Receipt #{{order_id}}',
        'active' => false,
    ]);

    expect(EmailTemplate::query()->findOrFail($template->id)->variables)->toBe(['customer_name', 'order_id']);
});

it('generates a preview from template and variables', function () {
    $user = User::factory()->create(['is_admin' => true]);
    EmailTemplate::factory()->create([
        'key' => 'test_preview',
        'subject_template' => 'Hi {{name}}',
        'body_template' => 'Welcome {{name}}',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('subjectTemplate', 'Hi {{name}}')
        ->set('bodyTemplate', 'Welcome {{name}}')
        ->set('previewVariablesJson', '{"name": "Rahim"}')
        ->call('generatePreview')
        ->assertSet('previewSubject', 'Hi Rahim')
        ->assertSet('previewBody', 'Welcome Rahim');
});

it('renders subject and body with variables', function () {
    $renderer = new EmailTemplateRenderer;

    expect($renderer->renderSubject('Order #{{order_id}} for {{customer_name}}', [
        'order_id' => 42,
        'customer_name' => 'Rahim',
    ]))->toBe('Order #42 for Rahim');

    expect($renderer->renderBody('<p>Hello {{customer_name}}</p>', [
        'customer_name' => '<script>Rahim</script>',
    ]))->toBe('<p>Hello &lt;script&gt;Rahim&lt;/script&gt;</p>');
});

it('sends email through the template service', function () {
    Mail::fake();

    EmailTemplate::factory()->create([
        'key' => 'order_confirmation',
        'subject_template' => 'Order #{{order_id}} confirmed',
        'body_template' => '<p>Dear {{customer_name}}</p>',
        'active' => true,
    ]);

    $service = app(EmailTemplateService::class);
    $sent = $service->send('order_confirmation', 'rahim@example.com', [
        'order_id' => 10,
        'customer_name' => 'Rahim',
    ]);

    expect($sent)->toBeTrue();

    Mail::assertSent(TemplateDrivenMail::class, function (TemplateDrivenMail $mail) {
        return $mail->subjectLine === 'Order #10 confirmed'
            && str_contains($mail->bodyHtml, 'Dear Rahim')
            && $mail->hasTo('rahim@example.com');
    });
});

it('does not send when template is inactive or missing', function () {
    Mail::fake();

    EmailTemplate::factory()->create([
        'key' => 'inactive_key',
        'active' => false,
    ]);

    $service = app(EmailTemplateService::class);

    expect($service->send('inactive_key', 'rahim@example.com'))->toBeFalse();
    expect($service->send('missing_key', 'rahim@example.com'))->toBeFalse();

    Mail::assertNothingSent();
});

it('previews a template through the service', function () {
    EmailTemplate::factory()->create([
        'key' => 'welcome',
        'subject_template' => 'Hi {{name}}',
        'body_template' => 'Welcome {{name}}',
    ]);

    $preview = app(EmailTemplateService::class)->preview('welcome', ['name' => 'Rahim']);

    expect($preview)->toBe(['subject' => 'Hi Rahim', 'body' => 'Welcome Rahim']);
    expect(app(EmailTemplateService::class)->preview('nope'))->toBeNull();
});

it('seeds default email templates', function () {
    $this->seed(EmailTemplatesSeeder::class);

    expect(EmailTemplate::query()->where('key', 'user_welcome')->exists())->toBeTrue();
    expect(EmailTemplate::query()->where('key', 'order_confirmation')->exists())->toBeTrue();
    expect(EmailTemplate::query()->where('key', 'contact_message_for_admin')->exists())->toBeTrue();
});

describe('mail settings', function () {
    beforeEach(function () {
        // EnvFile must never touch the real project .env during tests — point it at a
        // throwaway file instead, and always restore the override afterwards.
        $this->envPath = sys_get_temp_dir().'/email-templates-mail-test-'.uniqid().'.env';

        file_put_contents($this->envPath, <<<'ENV'
            MAIL_MAILER=smtp
            MAIL_HOST=smtp.example.test
            MAIL_PORT=587
            MAIL_USERNAME=original@example.test
            MAIL_PASSWORD=secret
            MAIL_SCHEME=tls
            MAIL_FROM_ADDRESS=hello@example.test
            MAIL_FROM_NAME="Example App"
            ENV);

        EnvFile::$pathOverride = $this->envPath;

        $this->admin = User::factory()->create(['is_admin' => true]);
    });

    afterEach(function () {
        EnvFile::$pathOverride = null;
        @unlink($this->envPath);
    });

    it('loads current mail settings from .env on mount, not the generic env form', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->assertSet('mailSettings.MAIL_HOST', 'smtp.example.test')
            ->assertSet('mailSettings.MAIL_FROM_NAME', 'Example App');
    });

    it('shows the mail settings action on the page and no longer on the settings env tab', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.email-templates'))
            ->assertOk()
            ->assertSee('Mail Settings');

        $this->actingAs($this->admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertDontSee('SMTP Host');
    });

    it('validates before opening the save confirmation', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('mailSettings.MAIL_FROM_ADDRESS', 'not-an-email')
            ->call('confirmSaveMailSettings')
            ->assertHasErrors(['mailSettings.MAIL_FROM_ADDRESS']);

        expect(EnvFile::get('MAIL_FROM_ADDRESS'))->toBe('hello@example.test');
    });

    it('saves mail settings to .env and clears the config cache', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('mailSettings.MAIL_HOST', 'smtp.new-provider.test')
            ->set('mailSettings.MAIL_USERNAME', 'new@example.test')
            ->call('confirmSaveMailSettings')
            ->call('saveMailSettings');

        expect(EnvFile::get('MAIL_HOST'))->toBe('smtp.new-provider.test')
            ->and(EnvFile::get('MAIL_USERNAME'))->toBe('new@example.test');
    });

    it('surfaces a clear error instead of a false success when the write fails', function () {
        $component = Livewire::actingAs($this->admin)->test(Index::class);

        EnvFile::$pathOverride = sys_get_temp_dir().'/nonexistent-dir-'.uniqid().'/.env';

        $component->call('confirmSaveMailSettings')->call('saveMailSettings')
            ->assertDispatched('notify', message: 'Could not save mail settings: Could not read '.EnvFile::path().'.');
    });
});

describe('send test email', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        EmailTemplate::factory()->create([
            'key' => Index::TEST_EMAIL_TEMPLATE_KEY,
            'subject_template' => 'Test Email from {{site_name}}',
            'body_template' => '<p>Sent at {{sent_at}}</p>',
            'active' => true,
        ]);
    });

    it('rejects an invalid test email address', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('testEmailAddress', 'not-an-email')
            ->call('sendTestEmail')
            ->assertHasErrors(['testEmailAddress']);

        Mail::assertNothingSent();
    });

    it('sends the test email template to the given address', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('testEmailAddress', 'destination@example.test')
            ->call('sendTestEmail')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Test email sent to destination@example.test.');

        Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('destination@example.test'));
    });

    it('reports failure instead of a false success when the test template is missing or inactive', function () {
        Mail::fake();
        EmailTemplate::query()->where('key', Index::TEST_EMAIL_TEMPLATE_KEY)->update(['active' => false]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('testEmailAddress', 'destination@example.test')
            ->call('sendTestEmail')
            ->assertDispatched('notify', message: 'Could not send — the "'.Index::TEST_EMAIL_TEMPLATE_KEY.'" template is missing or inactive.');

        Mail::assertNothingSent();
    });
});

it('renders the template-driven email view', function () {
    \App\Models\Setting::set('contact_email', 'support@example.com');

    $html = view('emails.template-driven', [
        'subjectLine' => 'Order #10 confirmed',
        'bodyHtml' => '<p>Dear Rahim,</p><p>Thank you for your order.</p>',
    ])->render();

    expect($html)
        ->toContain('Order #10 confirmed')
        ->toContain('Dear Rahim')
        ->toContain('support@example.com');
});
