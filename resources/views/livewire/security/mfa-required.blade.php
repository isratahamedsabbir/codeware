{{--
    The forced-enrolment screen — where App\Http\Middleware\EnsureMfaEnforced
    sends an account whose audience has MFA switched on and which has no factor
    yet.

    Two panes on a wide card: what is happening and what is left to do on the
    left, the shared App\Livewire\Security\MfaPanel on the right. Mounted once
    per portal (admin.mfa.required / vendor.mfa.required / delivery.mfa.required)
    so the panel, the passkey routes it drives and the session stay on the host
    whose policy is being enforced.

    MfaRequired::render() supplies the layout with ->layout(). That layout ships
    its own hand-written CSS rather than the Tailwind bundle, so this screen
    carries its own small stylesheet instead of utility classes.

    No reload on success: the panel dispatches mfa-updated, MfaRequired
    re-renders, and the "Continue" button appears. A reload would discard the
    recovery codes the panel has just put on screen, which are shown only once.
--}}
<div class="mfa-screen">
    <style>
        .mfa-screen {
            display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
            gap: 40px; width: 100%; text-align: left; align-items: start;
        }
        .mfa-side { display: flex; flex-direction: column; gap: 22px; }
        .mfa-main { min-width: 0; }

        .mfa-eyebrow {
            align-self: flex-start; display: inline-flex; align-items: center; gap: 7px;
            padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 600;
            letter-spacing: .03em; color: #bae6fd; background: rgba(14, 165, 233, .16);
            border: 1px solid rgba(125, 211, 252, .3);
        }
        .mfa-title { margin: 0; font-size: 28px; line-height: 1.2; font-weight: 700; color: #fff; }
        .mfa-lead { margin: 8px 0 0; font-size: 14.5px; line-height: 1.6; color: #cbd5e1; }

        .mfa-steps { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 14px; }
        .mfa-step { display: flex; gap: 12px; align-items: flex-start; }
        .mfa-step__dot {
            flex: none; width: 28px; height: 28px; border-radius: 999px; margin-top: 1px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #cbd5e1;
            background: rgba(148, 163, 184, .18); border: 1px solid rgba(148, 163, 184, .35);
        }
        .mfa-step--done .mfa-step__dot { background: #10b981; border-color: #10b981; color: #fff; }
        .mfa-step__name { margin: 0; font-size: 14px; font-weight: 600; color: #f1f5f9; }
        .mfa-step__desc { margin: 2px 0 0; font-size: 12.5px; line-height: 1.5; color: #94a3b8; }

        .mfa-done {
            display: flex; flex-direction: column; gap: 12px; padding: 16px 18px; border-radius: 14px;
            background: #ecfdf5; border: 1px solid #a7f3d0;
        }
        .mfa-done__title { margin: 0; font-size: 14.5px; font-weight: 700; color: #064e3b; }
        .mfa-done__hint { margin: 0; font-size: 12.5px; line-height: 1.5; color: #047857; }
        .mfa-done__cta {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 11px 18px; border-radius: 10px; font-size: 14px; font-weight: 600;
            background: #059669; color: #fff; text-decoration: none;
            transition: background .15s, transform .15s;
        }
        .mfa-done__cta:hover { background: #047857; transform: translateY(-1px); }

        .mfa-foot {
            display: flex; flex-direction: column; gap: 8px; padding-top: 18px;
            border-top: 1px solid rgba(148, 163, 184, .25);
        }
        .mfa-foot__out {
            align-self: flex-start; background: none; border: 0; padding: 0; cursor: pointer;
            font-size: 13px; font-weight: 500; color: #cbd5e1; text-decoration: underline;
        }
        .mfa-foot__out:hover { color: #fff; }
        .mfa-foot__help { margin: 0; font-size: 12px; line-height: 1.5; color: #94a3b8; }

        /* The steps on the left already say what is required, so the panel's own
           warning is redundant here. */
        .mfa-main .mfa-required-callout { display: none; }

        @media (max-width: 860px) {
            .mfa-screen { grid-template-columns: minmax(0, 1fr); gap: 28px; }
            .mfa-title { font-size: 24px; }
        }
    </style>

    <aside class="mfa-side">
        <span class="mfa-eyebrow">
            <flux:icon.shield-check class="size-4" /> {{ __('Security check') }}
        </span>

        <div>
            <h1 class="mfa-title">{{ __('Two-factor authentication') }}</h1>
            <p class="mfa-lead">
                {{ $hasFactor
                    ? __('Your account is protected. You can carry on to the portal.')
                    : __('This portal asks for a second sign-in step. Pick one method on the right, it takes about a minute.') }}
            </p>
        </div>

        <ol class="mfa-steps">
            <li class="mfa-step {{ $hasFactor ? 'mfa-step--done' : '' }}">
                <span class="mfa-step__dot">
                    @if ($hasFactor)
                        <flux:icon.check class="size-4" />
                    @else
                        1
                    @endif
                </span>
                <div>
                    <p class="mfa-step__name">{{ __('Choose a method') }}</p>
                    <p class="mfa-step__desc">{{ __('An authenticator app or a passkey. One is enough.') }}</p>
                </div>
            </li>
            <li class="mfa-step">
                <span class="mfa-step__dot">2</span>
                <div>
                    <p class="mfa-step__name">{{ __('Save your recovery codes') }}</p>
                    <p class="mfa-step__desc">{{ __('Shown once after you turn on an authenticator app. Keep them somewhere safe.') }}</p>
                </div>
            </li>
            <li class="mfa-step">
                <span class="mfa-step__dot">3</span>
                <div>
                    <p class="mfa-step__name">{{ __('Continue to the portal') }}</p>
                    <p class="mfa-step__desc">{{ __('You will be asked for this step each time you sign in.') }}</p>
                </div>
            </li>
        </ol>

        @if ($hasFactor)
            <div class="mfa-done" role="status">
                <p class="mfa-done__title">{{ __('Two-factor authentication is on') }}</p>
                <p class="mfa-done__hint">{{ __('If you just turned on an authenticator app, save the recovery codes first. They are shown only once.') }}</p>
                <a href="{{ $continueUrl }}" class="mfa-done__cta">
                    {{ __('Continue to dashboard') }}
                    <flux:icon.arrow-right class="size-4" />
                </a>
            </div>
        @endif

        <div class="mfa-foot">
            <form method="POST" action="{{ $logoutRoute }}">
                @csrf
                <button type="submit" class="mfa-foot__out">{{ __('Sign out') }}</button>
            </form>
            <p class="mfa-foot__help">
                @if ($requiredForAdmin)
                    {{ __('Need help? Another administrator can turn this requirement off for your role in Roles.') }}
                @else
                    {{ __('Need help? Your administrator can turn this requirement off for your role in Roles.') }}
                @endif
            </p>
        </div>
    </aside>

    <main class="mfa-main">
        <livewire:security.mfa-panel :required="true" />
    </main>
</div>
