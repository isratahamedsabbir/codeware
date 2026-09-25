<div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 border-t border-zinc-100 pt-6">
    @auth
        <button type="button" wire:click="toggleLike"
            class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition
                {{ $likedByMe ? 'border-red-200 bg-red-50 text-red-600' : 'border-zinc-200 text-zinc-600 hover:border-red-200 hover:bg-red-50 hover:text-red-600' }}">
            <x-storefront.icon name="heart" class="h-4 w-4" />
            {{ trans_choice(':count like|:count likes', $likesCount, ['count' => $likesCount]) }}
        </button>
    @else
        <a href="{{ route('login') }}"
            class="inline-flex items-center gap-2 rounded-full border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
            title="{{ __('Sign in to like this post') }}">
            <x-storefront.icon name="heart" class="h-4 w-4" />
            {{ trans_choice(':count like|:count likes', $likesCount, ['count' => $likesCount]) }}
        </a>
    @endauth

    <span class="inline-flex items-center gap-2 text-sm text-zinc-500">
        <x-storefront.icon name="eye" class="h-4 w-4" />
        {{ trans_choice(':count view|:count views', $post->views, ['count' => $post->views]) }}
    </span>
</div>