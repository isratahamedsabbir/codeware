<div class="bg-white rounded-lg shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="flex gap-3 px-6 py-3 border-b border-zinc-100 flex-wrap items-center">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
        </select>
        <div class="relative flex-1 min-w-[180px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, email or subject…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200" style="table-layout:fixed">
                <colgroup>
                    <col style="width:16%">
                    <col style="width:18%">
                    <col style="width:16%">
                    <col style="width:24%">
                    <col style="width:12%">
                    <col style="width:14%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Contact Info</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Message</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($contacts as $contact)
                        <tr class="hover:bg-indigo-50/30 transition-colors">

                            {{-- Name --}}
                            <td class="px-4 py-3.5">
                                <span class="font-medium text-zinc-900 text-sm">{{ $contact->full_name }}</span>
                            </td>

                            {{-- Contact Info --}}
                            <td class="px-4 py-3.5">
                                <div class="text-sm text-zinc-600">{{ $contact->email }}</div>
                                @if ($contact->phone_number)
                                    <div class="text-xs text-zinc-400 mt-0.5">{{ $contact->phone_number }}</div>
                                @endif
                            </td>

                            {{-- Subject --}}
                            <td class="px-4 py-3.5">
                                <span class="text-sm text-zinc-600 truncate block">{{ $contact->subject }}</span>
                            </td>

                            {{-- Message --}}
                            <td class="px-4 py-3.5">
                                <p class="text-sm text-zinc-500 truncate mb-1.5">{{ $contact->message }}</p>
                                <button wire:click="viewMessage({{ $contact->id }})"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                    View full message
                                </button>
                            </td>

                            {{-- Date --}}
                            <td class="px-4 py-3.5">
                                <span class="text-sm text-zinc-500 whitespace-nowrap">{{ $contact->created_at->toDisplay('d M Y') }}</span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3.5">
                                <select wire:change="updateStatus({{ $contact->id }}, $event.target.value)"
                                    class="text-sm rounded-lg border border-zinc-200 bg-white py-1.5 pl-2.5 pr-7 text-zinc-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all appearance-none"
                                    style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 8px center">
                                    <option value="unread" @selected($contact->status === 'unread')>Unread</option>
                                    <option value="read" @selected($contact->status === 'read')>Read</option>
                                </select>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                                </svg>
                                <p class="text-sm text-zinc-600">No contacts found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $contacts->links() }}
    </div>

    {{-- View Message Modal --}}
    <flux:modal name="view-message" class="md:w-[600px]"
        x-on:open-modal.window="if ($event.detail.name === 'view-message') $flux.modal('view-message').show()">
        @if ($viewingMessageId)
            @php $contact = \App\Models\Contact::find($viewingMessageId); @endphp
            @if ($contact)
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                        <flux:heading>{{ $contact->subject ?: '(No subject)' }}</flux:heading>
                        <flux:modal.close>
                            <button wire:click="closeMessage" class="text-zinc-400 hover:text-zinc-600 transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </flux:modal.close>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-zinc-400">From:</span> <span class="text-zinc-900 font-medium">{{ $contact->full_name }}</span></div>
                        <div><span class="text-zinc-400">Email:</span> <span class="text-zinc-900">{{ $contact->email }}</span></div>
                        @if ($contact->phone_number)
                            <div><span class="text-zinc-400">Phone:</span> <span class="text-zinc-900">{{ $contact->phone_number }}</span></div>
                        @endif
                        <div><span class="text-zinc-400">Date:</span> <span class="text-zinc-900">{{ $contact->created_at->toDisplay('d M Y, h:i A') }}</span></div>
                    </div>
                    <div class="border-t border-zinc-100 pt-3">
                        <p class="text-sm text-zinc-700 leading-relaxed whitespace-pre-wrap">{{ $contact->message }}</p>
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>

</div>
