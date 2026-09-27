{{-- One root element: Livewire requires exactly one, and anything outside it
     silently stops updating after the first render. See the same note in
     livewire/admin/services/index.blade.php.

     Model constants below are written fully qualified because a Blade view
     compiles into a file with no namespace, so a bare Booking:: would resolve
     to a global \Booking that does not exist. --}}
<div>

    {{-- Rendered in the component's own template rather than pushed to
         @stack('page-header-actions'), which is flushed into the layout on the
         initial load only — so the unread count here would freeze at whatever it
         was when the page first loaded. --}}
    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.bookings'])
        </div>

        {{-- No "New booking" button, and deliberately no create route: a
             booking is a visitor's message, so there is nothing here to author
             and an empty create form would be the only page on this screen with
             no purpose. --}}
    </div>

    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center gap-3 p-4 flex-wrap">
            <x-per-page-select :options="$this->perPageOptions()" />

            <select wire:model.live="statusFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">All ({{ $bookings->total() }})</option>
                <option value="new">New ({{ $newCount }})</option>
                <option value="completed">Completed</option>
            </select>

            <div class="relative max-w-xs ml-auto">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search bookings…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
            </div>
        </div>

        {{-- Table. Not the bulk-select + sticky-actions table the catalogue
             screens use: there is no batch action here worth having, and a
             selectable checkbox column that does nothing is worse than no column.
             Each booking is read and answered on its own. --}}
        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">From</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Service</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Message</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Received</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($bookings as $booking)
                            <tr wire:key="booking-{{ $booking->id }}" class="hover:bg-indigo-50/30 transition-colors">

                                <td class="px-4 py-3 text-xs text-zinc-500">
                                    <x-copy-text :text="$booking->id" class="text-xs text-zinc-500">{{ $booking->id }}</x-copy-text>
                                </td>

                                {{-- The visitor. The email is a real mailto rather
                                     than selectable text, because replying is the
                                     only thing anyone does on this row and copying an
                                     address by hand is a step that goes wrong. --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-zinc-900 leading-snug">
                                        {{ $booking->full_name }}
                                    </div>
                                    <a href="mailto:{{ $booking->email }}"
                                        class="text-xs text-indigo-600 hover:underline break-all">
                                        {{ $booking->email }}
                                    </a>
                                    @if (filled($booking->phone_number))
                                        <div class="mt-0.5">
                                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $booking->phone_number) }}"
                                                class="text-xs text-zinc-500 hover:text-indigo-600">
                                                {{ $booking->phone_number }}
                                            </a>
                                        </div>
                                    @endif
                                </td>

                                {{-- The service is a soft-deleted-away relation
                                     as often as not, so it is named when it is
                                     there and acknowledged when it is not — a
                                     blank cell would read as missing data rather
                                     than as a service that was retired. --}}
                                <td class="px-4 py-3 text-sm text-zinc-700">
                                    @if ($booking->service)
                                        <x-truncate :text="$booking->service->name" />
                                    @else
                                        <span class="text-zinc-400 italic">Deleted service</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm text-zinc-600 max-w-md">
                                    @if (filled($booking->message))
                                        {{-- Collapsed by default with a toggle: booking
                                             messages are a paragraph or three, so a
                                             table of them at full height is unreadable
                                             past the first two rows. --}}
                                        <div x-data="{ open: false }">
                                            <p class="line-clamp-2" x-bind:class="open && 'line-clamp-none'">
                                                {{ $booking->message }}
                                            </p>
                                            <button type="button" x-on:click="open = ! open"
                                                class="mt-1 text-xs font-medium text-indigo-600 hover:underline">
                                                <span x-show="! open">Show more</span>
                                                <span x-show="open" x-cloak>Show less</span>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-zinc-400">&mdash;</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm text-zinc-500 whitespace-nowrap">
                                    {{ $booking->created_at?->diffForHumans() }}
                                </td>

                                <td class="px-4 py-3">
                                    @if ($booking->status === \App\Models\Booking::STATUS_NEW)
                                        <button type="button" wire:click="toggleStatus({{ $booking->id }})"
                                            aria-label="Mark as completed"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600 border border-blue-200 cursor-pointer hover:bg-blue-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            New
                                        </button>
                                    @else
                                        <button type="button" wire:click="toggleStatus({{ $booking->id }})"
                                            aria-label="Mark as new"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Completed
                                        </button>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <x-admin-row-actions :actions="[
                                        ['wireClick' => 'confirmDelete(' . $booking->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                    ]" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <flux:icon.inbox class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                    <p class="text-sm text-zinc-600">No bookings yet.</p>
                                    <p class="mt-1 text-xs text-zinc-400">
                                        A visitor's request from a service card on the storefront lands here.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3">
            {{ $bookings->links() }}
        </div>
    </div>

    {{-- Delete confirmation --}}
    <flux:modal name="booking-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'booking-delete') $flux.modal('booking-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'booking-delete') $flux.modal('booking-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete booking?</flux:heading>
            </div>

            <p class="text-sm text-zinc-600">
                This removes the request from the list. It is soft-deleted, so it can be
                restored from the database — but it holds someone's name, email and possibly
                a phone number, so delete it once you no longer need it.
            </p>

            <flux:modal.close>
                <flux:button variant="ghost" size="sm">Cancel</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" size="sm" wire:click="delete">Delete booking</flux:button>
        </div>
    </flux:modal>
</div>
