{{-- 401 Unauthorized — usually only reached via abort(401); the framework redirects to login for AuthenticationException. --}}
<x-errors.page
    :code="401"
    :title="__('Unauthorized')"
    :message="__('You need to sign in before you can view this page.')"
    :back="false"
    :links="[
        ['label' => __('Sign in'), 'href' => route('login')],
        ['label' => __('Create account'), 'href' => route('register')],
    ]"
/>
