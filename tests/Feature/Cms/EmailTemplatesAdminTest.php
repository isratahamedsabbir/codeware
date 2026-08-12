<?php

use App\Livewire\Admin\EmailTemplates\Index;
use App\Mail\TemplateDrivenMail;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use App\Services\EmailTemplateService;
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
