<?php

use App\Livewire\Admin\EmailTemplates\Index;
use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use App\Services\EmailTemplateService;
use App\Support\EmailThemes;
use App\Support\EnvFile;
use Database\Seeders\EmailTemplatesSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects guests away from email templates admin page', function () {
    $this->get(config('app.admin_url').'/email-templates')->assertRedirect('/login');
});

it('forbids non-admin users from email templates admin page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(config('app.admin_url').'/email-templates')->assertForbidden();
});

it('allows admins to access email templates admin page', function () {
    EmailTemplate::factory()->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(config('app.admin_url').'/email-templates')
        ->assertSuccessful()
        ->assertSee('Email Templates');
});

it('renders email templates index and auto-selects first template', function () {
    $user = User::factory()->admin()->create();
    $template = EmailTemplate::factory()->create(['name' => 'Welcome Email']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Welcome Email')
        ->assertSet('selectedTemplateId', $template->id);
});

it('loads template details when selecting a template', function () {
    $user = User::factory()->admin()->create();
    $template = EmailTemplate::factory()->create([
        'name' => 'Order Confirmation',
        'subject_template' => 'Order #{{order_id}} confirmed',
        'body_template' => '<p>Hello {{customer_name}}</p>',
        'variables' => ['customer_name', 'order_id'],
        'theme' => 'ocean',
        'active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('selectTemplate', $template->id)
        ->assertSet('templateKey', $template->key)
        ->assertSet('subjectTemplate', 'Order #{{order_id}} confirmed')
        ->assertSet('theme', 'ocean')
        ->assertSet('variablesList', 'customer_name, order_id')
        ->assertSet('active', true);
});

it('updates email template content through livewire form', function () {
    $user = User::factory()->admin()->create();
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
        ->set('theme', 'royal')
        ->set('variablesList', 'customer_name, order_id')
        ->set('active', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('email_templates', [
        'id' => $template->id,
        'subject_template' => 'Receipt #{{order_id}}',
        'theme' => 'royal',
        'active' => false,
    ]);

    expect(EmailTemplate::query()->findOrFail($template->id)->variables)->toBe(['customer_name', 'order_id']);
});

it('rejects an unknown theme when saving an email template', function () {
    $user = User::factory()->admin()->create();
    $template = EmailTemplate::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('selectTemplate', $template->id)
        ->set('theme', 'not-a-theme')
        ->call('save')
        ->assertHasErrors(['theme']);
});

it('generates a preview from template and variables', function () {
    $user = User::factory()->admin()->create();
    EmailTemplate::factory()->create([
        'key' => 'test_preview',
        'subject_template' => 'Hi {{name}}',
        'body_template' => 'Welcome {{name}}',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('subjectTemplate', 'Hi {{name}}')
        ->set('bodyTemplate', 'Welcome {{name}}')
        ->set('theme', 'ocean')
        ->set('previewVariablesJson', '{"name": "Rahim"}')
        ->call('generatePreview')
        ->assertSet('previewSubject', 'Hi Rahim')
        ->assertSet('previewBody', 'Welcome Rahim')
        ->assertSet('previewHtml', fn (string $html) => str_contains($html, 'Welcome Rahim')
            && str_contains($html, 'email-wrapper')
            && str_contains($html, 'Get in touch')
            && str_contains($html, 'background:#1e7bc4')
            && str_contains($html, 'background:#eef5fb'));
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
        'theme' => 'slate',
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
            && $mail->emailTheme === 'slate'
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

        $this->admin = User::factory()->admin()->create();
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
        $this->admin = User::factory()->admin()->create();
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

describe('send custom email', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
    });

    it('requires an email, subject, and description', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('customEmailTo', 'not-an-email')
            ->set('customEmailSubject', '')
            ->set('customEmailDescription', '')
            ->call('sendCustomEmail')
            ->assertHasErrors(['customEmailTo', 'customEmailSubject', 'customEmailDescription']);

        Mail::assertNothingSent();
    });

    it('sends a freeform email with the given subject and description', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('customEmailTo', 'destination@example.test')
            ->set('customEmailSubject', 'Hello there')
            ->set('customEmailDescription', "Line one\nLine two")
            ->call('sendCustomEmail')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Email sent to destination@example.test.')
            ->assertSet('customEmailTo', '')
            ->assertSet('customEmailSubject', '')
            ->assertSet('customEmailDescription', '');

        Mail::assertSent(TemplateDrivenMail::class, function (TemplateDrivenMail $mail) {
            return $mail->hasTo('destination@example.test')
                && $mail->subjectLine === 'Hello there'
                && str_contains($mail->bodyHtml, 'Line one<br />')
                && str_contains($mail->bodyHtml, 'Line two');
        });
    });

    it('escapes html in the description before sending', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('customEmailTo', 'destination@example.test')
            ->set('customEmailSubject', 'Hello')
            ->set('customEmailDescription', '<script>alert(1)</script>')
            ->call('sendCustomEmail')
            ->assertHasNoErrors();

        Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => str_contains($mail->bodyHtml, '&lt;script&gt;'));
    });

    it('pre-fills the variables editor when a template is selected', function () {
        EmailTemplate::factory()->create([
            'key' => 'custom_welcome',
            'variables' => ['customer_name', 'order_id'],
            'active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('customEmailTemplateKey', 'custom_welcome')
            ->assertSet('customEmailVariables', fn (string $json) => json_decode($json, true) === [
                'customer_name' => 'customer_name',
                'order_id' => 'order_id',
            ]);
    });

    it('sends an email using the selected template with variables', function () {
        Mail::fake();

        EmailTemplate::factory()->create([
            'key' => 'custom_welcome',
            'subject_template' => 'Hi {{ customer_name }}, welcome!',
            'body_template' => 'Thanks for joining {{ customer_name }}.',
            'variables' => ['customer_name'],
            'theme' => 'sunset',
            'active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('customEmailTo', 'destination@example.test')
            ->set('customEmailTemplateKey', 'custom_welcome')
            ->set('customEmailVariables', '{"customer_name": "Rahim"}')
            ->call('sendCustomEmail')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Email sent to destination@example.test.')
            ->assertSet('customEmailTemplateKey', '')
            ->assertSet('customEmailVariables', '')
            ->assertSet('customEmailSubject', '')
            ->assertSet('customEmailDescription', '');

        Mail::assertSent(TemplateDrivenMail::class, function (TemplateDrivenMail $mail) {
            return $mail->hasTo('destination@example.test')
                && $mail->subjectLine === 'Hi Rahim, welcome!'
                && $mail->emailTheme === 'sunset'
                && str_contains($mail->bodyHtml, 'Thanks for joining Rahim.');
        });
    });

    it('requires valid json for variables when a template is selected', function () {
        Mail::fake();

        $template = EmailTemplate::factory()->create(['active' => true]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('customEmailTo', 'destination@example.test')
            ->set('customEmailTemplateKey', $template->key)
            ->set('customEmailVariables', 'not-json')
            ->call('sendCustomEmail')
            ->assertHasErrors(['customEmailVariables' => 'json']);

        Mail::assertNothingSent();
    });

    it('warns when the selected template is missing or inactive', function () {
        Mail::fake();

        EmailTemplate::factory()->create(['key' => 'hidden_welcome', 'active' => false]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('customEmailTo', 'destination@example.test')
            ->set('customEmailTemplateKey', 'hidden_welcome')
            ->set('customEmailVariables', '{}')
            ->call('sendCustomEmail')
            ->assertDispatched('notify', message: 'Could not send — the selected template is missing or inactive.');

        Mail::assertNothingSent();
    });
});

it('renders the default email theme view', function () {
    Setting::set('contact_email', 'support@example.com');

    $html = view(EmailThemes::view('default'), [
        'subjectLine' => 'Order #10 confirmed',
        'bodyHtml' => '<p>Dear Rahim,</p><p>Thank you for your order.</p>',
    ])->render();

    expect($html)
        ->toContain('Order #10 confirmed')
        ->toContain('Dear Rahim')
        ->toContain('support@example.com');
});
