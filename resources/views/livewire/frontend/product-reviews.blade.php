@php
    $star = 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z';
    $stars = fn (float $value, string $size = 'h-4 w-4') => collect(range(1, 5))->map(fn ($i) =>
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="'.$size.' '.($i <= round($value) ? 'fill-amber-400 text-amber-400' : 'fill-zinc-200 text-zinc-200').'" stroke="currentColor" stroke-width="1"><path stroke-linejoin="round" d="'.$star.'" /></svg>'
    )->implode('');
@endphp

<section id="reviews" class="mt-14 scroll-mt-24">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <h2 class="text-2xl font-bold text-sf-heading">{{ __('Customer reviews') }}</h2>
        @if ($total)
            <p class="text-sm text-zinc-500">{{ trans_choice(':count review|:count reviews', $total, ['count' => $total]) }}</p>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-[20rem_1fr] lg:items-start">
        {{-- Summary + write a review --}}
        <div class="space-y-5 lg:sticky lg:top-24">
            <x-storefront.card>
                <div class="p-5">
                    @if ($total)
                        <div class="flex items-center gap-4">
                            <span class="text-5xl font-extrabold tabular-nums text-sf-heading">{{ number_format($average, 1) }}</span>
                            <div>
                                <div class="flex gap-0.5">{!! $stars($average, 'h-5 w-5') !!}</div>
                                <p class="mt-1 text-xs text-zinc-500">{{ trans_choice('Based on :count review|Based on :count reviews', $total, ['count' => $total]) }}</p>
                            </div>
                        </div>
                        <ul class="mt-5 space-y-1.5">
                            @foreach ($breakdown as $value => $count)
                                <li class="flex items-center gap-2.5 text-xs">
                                    <span class="w-3 font-semibold text-zinc-600">{{ $value }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-amber-400 text-amber-400" stroke="currentColor" stroke-width="1"><path stroke-linejoin="round" d="{{ $star }}" /></svg>
                                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100">
                                        <span class="block h-full rounded-full bg-amber-400" style="width: {{ $total ? round($count / $total * 100) : 0 }}%"></span>
                                    </span>
                                    <span class="w-6 text-right tabular-nums text-zinc-500">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm font-semibold text-sf-heading">{{ __('No reviews yet') }}</p>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('Bought this product? Be the first to share what you think.') }}</p>
                    @endif
                </div>
            </x-storefront.card>

            {{-- Who can write: only customers who bought it, once. --}}
            @guest
                <x-storefront.card>
                    <div class="p-5 text-sm">
                        <p class="text-zinc-600">{{ __('Bought this product? Sign in to write a review.') }}</p>
                        <a href="{{ route('login') }}" class="mt-3 inline-flex items-center gap-1.5 font-semibold text-brand hover:underline">{{ __('Sign in') }} →</a>
                    </div>
                </x-storefront.card>
            @else
                @if ($this->myReview)
                    <x-storefront.card>
                        <div class="flex items-start gap-3 p-5 text-sm">
                            <x-storefront.icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                            <p class="text-zinc-600">
                                @if ($this->myReview->status === 'approved')
                                    {{ __('Thanks — your review is published below.') }}
                                @elseif ($this->myReview->status === 'rejected')
                                    {{ __('Your review was not approved.') }}
                                @else
                                    {{ __('Thanks! Your review is awaiting approval and will appear here soon.') }}
                                @endif
                            </p>
                        </div>
                    </x-storefront.card>
                @elseif (! $this->hasPurchased)
                    <x-storefront.card>
                        <div class="flex items-start gap-3 p-5 text-sm text-zinc-600">
                            <x-storefront.icon name="bag" class="mt-0.5 h-5 w-5 shrink-0 text-zinc-400" />
                            <p>{{ __('Only customers who bought this product can review it.') }}</p>
                        </div>
                    </x-storefront.card>
                @else
                    <x-storefront.card :title="__('Write a review')">
                        <form wire:submit="submit" class="space-y-4 p-5"
                            x-data="{ rating: $wire.entangle('rating'), hover: 0 }">
                            <div>
                                <p class="mb-1.5 text-sm font-semibold text-sf-text">{{ __('Your rating') }}</p>
                                <div class="flex gap-1" @mouseleave="hover = 0" role="radiogroup" aria-label="{{ __('Your rating') }}">
                                    @foreach (range(1, 5) as $value)
                                        <button type="button" @click="rating = {{ $value }}" @mouseenter="hover = {{ $value }}"
                                            role="radio" :aria-checked="rating === {{ $value }}" aria-label="{{ trans_choice(':count star|:count stars', $value, ['count' => $value]) }}"
                                            class="rounded-md! p-0.5 transition hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-7 w-7" stroke="currentColor" stroke-width="1"
                                                :class="(hover || rating) >= {{ $value }} ? 'fill-amber-400 text-amber-400' : 'fill-zinc-200 text-zinc-200'">
                                                <path stroke-linejoin="round" d="{{ $star }}" />
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                                @error('rating') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <x-storefront.input name="title" :label="__('Title')" optional maxlength="120" :placeholder="__('Sum it up in a few words')" />
                            <x-storefront.input name="body" :label="__('Your review')" textarea :rows="4" maxlength="2000"
                                :placeholder="__('What did you like or dislike? How did you use it?')" />

                            <x-storefront.button icon="check-circle" loading="submit" :loading-text="__('Sending...')">
                                {{ __('Submit review') }}
                            </x-storefront.button>
                            <p class="text-center text-xs text-zinc-400">{{ __('Reviews are checked before they appear.') }}</p>
                        </form>
                    </x-storefront.card>
                @endif
            @endguest
        </div>

        {{-- The approved reviews --}}
        <div class="min-w-0">
            @if ($reviews->isEmpty())
                <div class="rounded-card border border-dashed border-zinc-300 bg-white px-6 py-12 text-center text-sm text-zinc-500">
                    {{ __('There are no reviews for this product yet.') }}
                </div>
            @else
                <ul class="space-y-4">
                    @foreach ($reviews as $review)
                        <li wire:key="review-{{ $review->id }}" class="rounded-card border border-zinc-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    @if ($review->user?->photo_url)
                                        <img src="{{ $review->user->photo_url }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                    @else
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-bold uppercase text-brand">
                                            {{ $review->user?->initials() ?: '?' }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-sf-heading">{{ $review->user?->name ?? __('Customer') }}</p>
                                        @if ($review->user_id && $verifiedBuyers->contains($review->user_id))
                                            <p class="flex items-center gap-1 text-xs font-medium text-emerald-600">
                                                <x-storefront.icon name="check-circle" class="h-3.5 w-3.5" />
                                                {{ __('Verified purchase') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <span class="shrink-0 text-xs text-zinc-400">{{ $review->created_at?->diffForHumans() }}</span>
                            </div>

                            <div class="mt-3 flex gap-0.5">{!! $stars($review->rating) !!}</div>
                            @if ($review->title)
                                <p class="mt-2 text-sm font-bold text-sf-heading">{{ $review->title }}</p>
                            @endif
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-zinc-600">{{ $review->body }}</p>
                        </li>
                    @endforeach
                </ul>

                @if ($reviews->count() < $total)
                    <div class="mt-5 text-center">
                        <button type="button" wire:click="showMore" wire:loading.attr="disabled"
                            class="rounded-full border border-zinc-300 bg-white px-5 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-brand hover:text-brand">
                            {{ __('Show more reviews') }}
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</section>
