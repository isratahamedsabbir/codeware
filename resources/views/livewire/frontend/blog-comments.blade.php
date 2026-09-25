<section id="comments" class="mt-14 scroll-mt-24 border-t border-zinc-100 pt-12">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <h2 class="text-2xl font-bold text-sf-heading">{{ __('Comments') }}</h2>
        @if ($comments->isNotEmpty())
            <p class="text-sm text-zinc-500">{{ trans_choice(':count comment|:count comments', $comments->count(), ['count' => $comments->count()]) }}</p>
        @endif
    </div>

    {{-- Write a comment — any signed-in user, no purchase needed. --}}
    @guest
        <x-storefront.card>
            <div class="p-5 text-sm">
                <p class="text-zinc-600">{{ __('Join the discussion — sign in to leave a comment.') }}</p>
                <a href="{{ route('login') }}" class="mt-3 inline-flex items-center gap-1.5 font-semibold text-brand hover:underline">{{ __('Sign in') }} →</a>
            </div>
        </x-storefront.card>
    @else
        <x-storefront.card :title="__('Leave a comment')">
            <div class="p-5">
                <form wire:submit="submit" class="space-y-4">
                    <x-storefront.input name="body" :label="__('Your comment')" textarea :rows="3" maxlength="2000" :placeholder="__('What do you think?')" />
                    <x-storefront.button icon="chat" loading="submit" :loading-text="__('Sending...')">
                        {{ __('Post comment') }}
                    </x-storefront.button>
                    <p class="text-center text-xs text-zinc-400">{{ __('Comments are checked before they appear.') }}</p>
                </form>
            </div>
        </x-storefront.card>
    @endguest

    @if ($statusMessage)
        <div wire:key="status-message" class="mt-4 flex items-start gap-3 rounded-card border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            <x-storefront.icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
            <p>{{ $statusMessage }}</p>
        </div>
    @endif

    {{-- The approved comments, with their approved replies nested one level. --}}
    @if ($comments->isEmpty())
        <div class="mt-5 rounded-card border border-dashed border-zinc-300 bg-white px-6 py-12 text-center text-sm text-zinc-500">
            {{ __('No comments yet — be the first to share your thoughts.') }}
        </div>
    @else
        <ul class="mt-5 space-y-4">
            @foreach ($comments as $comment)
                <li wire:key="comment-{{ $comment->id }}" class="rounded-card border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($comment->user?->photo_url)
                                <img src="{{ $comment->user->photo_url }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                            @else
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-bold uppercase text-brand">
                                    {{ $comment->user?->initials() ?: '?' }}
                                </span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-sf-heading">{{ $comment->user?->name ?? __('Deleted user') }}</p>
                                <p class="text-xs text-zinc-400">{{ $comment->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-zinc-600">{{ $comment->body }}</p>

                    @auth
                        <button type="button" wire:click="beginReply({{ $comment->id }})"
                            @class(['mt-3 text-xs font-semibold transition hover:text-brand', $replyToId === $comment->id ? 'text-brand' : 'text-zinc-500 hover:underline'])
                            @disabled($replyToId === $comment->id)>
                            {{ $replyToId === $comment->id ? __('Replying…') : __('Reply') }}
                        </button>

                        @if ($replyToId === $comment->id)
                            <form wire:submit="submitReply({{ $comment->id }})" wire:key="reply-form-{{ $comment->id }}"
                                class="mt-3 space-y-3 rounded-card border border-zinc-200 bg-zinc-50/60 p-4">
                                <x-storefront.input name="replyBody" :label="__('Your reply')" textarea :rows="2" maxlength="2000" :placeholder="__('Write a reply…')" />
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="cancelReply"
                                        class="rounded-[5px] border border-zinc-300 bg-white px-4 py-2 text-xs font-semibold text-zinc-600 transition hover:text-zinc-900">
                                        {{ __('Cancel') }}
                                    </button>
                                    <x-storefront.button loading="submitReply" :loading-text="__('Sending...')" class="w-auto px-5 py-2 text-xs">
                                        {{ __('Post reply') }}
                                    </x-storefront.button>
                                </div>
                            </form>
                        @endif
                    @endauth

                    @if ($comment->replies->isNotEmpty())
                        <ul class="mt-4 space-y-4 border-l-2 border-zinc-100 pl-4">
                            @foreach ($comment->replies as $reply)
                                <li wire:key="reply-{{ $reply->id }}" class="min-w-0">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        @if ($reply->user?->photo_url)
                                            <img src="{{ $reply->user->photo_url }}" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover">
                                        @else
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand/10 text-[10px] font-bold uppercase text-brand">
                                                {{ $reply->user?->initials() ?: '?' }}
                                            </span>
                                        @endif
                                        <p class="min-w-0 truncate text-sm font-semibold text-sf-heading">{{ $reply->user?->name ?? __('Deleted user') }}</p>
                                        <span class="shrink-0 text-xs text-zinc-400">{{ $reply->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-zinc-600">{{ $reply->body }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>